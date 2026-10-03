<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/report_query.php';
require_login();

$pdo = db();
$teacher = current_teacher();
$classes = $pdo->query('SELECT id, class_name FROM classes ORDER BY class_name')->fetchAll();

$searchType = $_GET['search_type'] ?? 'student';
$studentId = trim((string) ($_GET['student_id'] ?? ''));
$classId = (int) ($_GET['class_id'] ?? 0);
$searched = isset($_GET['search']);
$records = [];
$reportLabel = '';

if ($searched) {
    [$records, $reportLabel] = fetch_attendance_report($pdo, $searchType, $studentId, $classId);
}

$queryString = report_query_string($searchType, $studentId, $classId);
$exportUrl = 'export_excel.php?' . $queryString;

$pageTitle = 'Attendance Report — ' . APP_NAME;
$activePage = 'report';
require __DIR__ . '/includes/header.php';
?>

<section class="panel report-panel">
  <div class="panel-heading report-heading">
    <div>
      <h1>Attendance Report</h1>
      <p class="lede">Search attendance records by Student ID or by Class. Print or export results to Excel.</p>
    </div>
    <?php if ($searched && $records !== []): ?>
      <div class="report-actions no-print">
        <button type="button" class="btn btn-secondary" id="print-report">Print</button>
        <a class="btn btn-primary" href="<?= e($exportUrl) ?>">Export to Excel</a>
      </div>
    <?php endif; ?>
  </div>

  <form method="get" class="filters report-filters no-print">
    <label>
      Search by
      <select name="search_type" id="search_type">
        <option value="student" <?= $searchType === 'student' ? 'selected' : '' ?>>Student ID</option>
        <option value="class" <?= $searchType === 'class' ? 'selected' : '' ?>>Class</option>
      </select>
    </label>

    <label id="student_field" class="<?= $searchType === 'student' ? '' : 'is-hidden' ?>">
      Student ID
      <input type="text" name="student_id" placeholder="e.g. STU-001" value="<?= e($studentId) ?>">
    </label>

    <label id="class_field" class="<?= $searchType === 'class' ? '' : 'is-hidden' ?>">
      Class
      <select name="class_id">
        <option value="">Select class</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?= (int) $class['id'] ?>" <?= $classId === (int) $class['id'] ? 'selected' : '' ?>>
            <?= e($class['class_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <button type="submit" name="search" value="1" class="btn btn-primary">Search</button>
  </form>

  <?php if ($searched): ?>
    <?php if ($records === []): ?>
      <div class="empty-state">
        <strong>No records found</strong>
        Try another Student ID (for example STU-001) or choose a different class.
      </div>
    <?php else: ?>
      <div class="print-meta">
        <h2>Attendance Report<?= $reportLabel !== '' ? ' — ' . e($reportLabel) : '' ?></h2>
        <p>
          Printed/exported by <?= e($teacher['full_name'] ?? '') ?>
          · <?= e(date('M j, Y g:i A')) ?>
          · <?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?>
        </p>
      </div>

      <div class="table-wrap" id="report-table">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Student ID</th>
              <th>Student Name</th>
              <th>Class</th>
              <th>Subject</th>
              <th>Status</th>
              <th>Marked by</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $row): ?>
              <tr>
                <td><?= e(date('M j, Y', strtotime($row['attendance_date']))) ?></td>
                <td><?= e($row['student_id']) ?></td>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e($row['class_name']) ?></td>
                <td><?= e($row['subject_name']) ?></td>
                <td>
                  <span class="badge badge-<?= strtolower($row['status']) ?>"><?= e($row['status']) ?></span>
                </td>
                <td><?= e($row['teacher_name']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="result-count no-print"><?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?> found</p>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty-state">
      <strong>Search attendance history</strong>
      Choose Student ID or Class above, then search to print or export marked records.
    </div>
  <?php endif; ?>
</section>

<script>
  const searchType = document.getElementById('search_type');
  const studentField = document.getElementById('student_field');
  const classField = document.getElementById('class_field');
  const printBtn = document.getElementById('print-report');

  function syncSearchFields() {
    const isStudent = searchType.value === 'student';
    studentField.classList.toggle('is-hidden', !isStudent);
    classField.classList.toggle('is-hidden', isStudent);
  }

  searchType.addEventListener('change', syncSearchFields);

  if (printBtn) {
    printBtn.addEventListener('click', () => window.print());
  }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
