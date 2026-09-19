<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();

$classes = $pdo->query('SELECT id, class_name FROM classes ORDER BY class_name')->fetchAll();

$searchType = $_GET['search_type'] ?? 'student';
$studentId = trim((string) ($_GET['student_id'] ?? ''));
$classId = (int) ($_GET['class_id'] ?? 0);
$records = [];
$searched = isset($_GET['search']);

if ($searched) {
    if ($searchType === 'student' && $studentId !== '') {
        $stmt = $pdo->prepare(
            'SELECT a.attendance_date, a.status, s.student_id, s.full_name,
                    c.class_name, sub.subject_name, t.full_name AS teacher_name
             FROM attendance a
             JOIN students s ON s.id = a.student_id
             JOIN classes c ON c.id = a.class_id
             JOIN subjects sub ON sub.id = a.subject_id
             JOIN teachers t ON t.id = a.teacher_id
             WHERE s.student_id = ?
             ORDER BY a.attendance_date DESC, sub.subject_name'
        );
        $stmt->execute([$studentId]);
        $records = $stmt->fetchAll();
    } elseif ($searchType === 'class' && $classId > 0) {
        $stmt = $pdo->prepare(
            'SELECT a.attendance_date, a.status, s.student_id, s.full_name,
                    c.class_name, sub.subject_name, t.full_name AS teacher_name
             FROM attendance a
             JOIN students s ON s.id = a.student_id
             JOIN classes c ON c.id = a.class_id
             JOIN subjects sub ON sub.id = a.subject_id
             JOIN teachers t ON t.id = a.teacher_id
             WHERE a.class_id = ?
             ORDER BY a.attendance_date DESC, s.full_name, sub.subject_name'
        );
        $stmt->execute([$classId]);
        $records = $stmt->fetchAll();
    }
}

$pageTitle = 'Attendance Report — ' . APP_NAME;
$activePage = 'report';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h1>Attendance Report</h1>
      <p class="lede">Search attendance records by Student ID or by Class.</p>
    </div>
  </div>

  <form method="get" class="filters report-filters">
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
      <div class="table-wrap">
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
      <p class="result-count"><?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?> found</p>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty-state">
      <strong>Search attendance history</strong>
      Choose Student ID or Class above, then search to see marked records.
    </div>
  <?php endif; ?>
</section>

<script>
  const searchType = document.getElementById('search_type');
  const studentField = document.getElementById('student_field');
  const classField = document.getElementById('class_field');

  function syncSearchFields() {
    const isStudent = searchType.value === 'student';
    studentField.classList.toggle('is-hidden', !isStudent);
    classField.classList.toggle('is-hidden', isStudent);
  }

  searchType.addEventListener('change', syncSearchFields);
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
