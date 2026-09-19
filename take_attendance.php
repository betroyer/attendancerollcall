<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$teacher = current_teacher();
$message = '';
$error = '';

$classes = $pdo->query('SELECT id, class_name FROM classes ORDER BY class_name')->fetchAll();
$subjects = $pdo->query('SELECT id, subject_name FROM subjects ORDER BY subject_name')->fetchAll();

$selectedDate = $_POST['attendance_date'] ?? $_GET['date'] ?? date('Y-m-d');
$selectedClass = (int) ($_POST['class_id'] ?? $_GET['class_id'] ?? 0);
$selectedSubject = (int) ($_POST['subject_id'] ?? $_GET['subject_id'] ?? 0);
$students = [];
$existing = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $selectedDate = trim((string) ($_POST['attendance_date'] ?? ''));
    $selectedClass = (int) ($_POST['class_id'] ?? 0);
    $selectedSubject = (int) ($_POST['subject_id'] ?? 0);
    $statuses = $_POST['status'] ?? [];

    if ($selectedDate === '' || $selectedClass <= 0 || $selectedSubject <= 0) {
        $error = 'Please select date, class, and subject before saving.';
    } elseif (!is_array($statuses) || $statuses === []) {
        $error = 'No students found to save.';
    } else {
        $allowed = ['Present', 'Absent', 'Late'];
        $stmt = $pdo->prepare(
            'INSERT INTO attendance (student_id, class_id, subject_id, teacher_id, attendance_date, status)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               status = VALUES(status),
               teacher_id = VALUES(teacher_id),
               class_id = VALUES(class_id)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($statuses as $studentId => $status) {
                $studentId = (int) $studentId;
                $status = (string) $status;
                if ($studentId <= 0 || !in_array($status, $allowed, true)) {
                    continue;
                }
                $stmt->execute([
                    $studentId,
                    $selectedClass,
                    $selectedSubject,
                    $teacher['id'],
                    $selectedDate,
                    $status,
                ]);
            }
            $pdo->commit();
            $message = 'Attendance saved successfully.';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Could not save attendance. Please try again.';
        }
    }
}

if ($selectedClass > 0) {
    $stmt = $pdo->prepare(
        'SELECT id, student_id, full_name
         FROM students
         WHERE class_id = ?
         ORDER BY full_name'
    );
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll();

    if ($selectedSubject > 0 && $selectedDate !== '') {
        $stmt = $pdo->prepare(
            'SELECT student_id, status
             FROM attendance
             WHERE class_id = ? AND subject_id = ? AND attendance_date = ?'
        );
        $stmt->execute([$selectedClass, $selectedSubject, $selectedDate]);
        foreach ($stmt->fetchAll() as $row) {
            $existing[(int) $row['student_id']] = $row['status'];
        }
    }
}

$pageTitle = 'Take Attendance — ' . APP_NAME;
$activePage = 'take';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h1>Take Attendance</h1>
      <p class="lede">Choose the date, class, and subject, then mark each student Present, Absent, or Late.</p>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <div class="alert alert-success" role="status"><?= e($message) ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" class="filters">
    <label>
      Date
      <input type="date" name="attendance_date" required value="<?= e($selectedDate) ?>">
    </label>
    <label>
      Class
      <select name="class_id" required>
        <option value="">Select class</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?= (int) $class['id'] ?>" <?= $selectedClass === (int) $class['id'] ? 'selected' : '' ?>>
            <?= e($class['class_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Subject
      <select name="subject_id" required>
        <option value="">Select subject</option>
        <?php foreach ($subjects as $subject): ?>
          <option value="<?= (int) $subject['id'] ?>" <?= $selectedSubject === (int) $subject['id'] ? 'selected' : '' ?>>
            <?= e($subject['subject_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" name="load_students" value="1" class="btn btn-secondary">Load students</button>
  </form>

  <?php if ($selectedClass > 0 && $students === []): ?>
    <div class="empty-state">
      <strong>No students in this class</strong>
      Add students to this class in the database, then load the list again.
    </div>
  <?php elseif ($students === [] && $selectedClass === 0): ?>
    <div class="empty-state">
      <strong>Ready when you are</strong>
      Select a date, class, and subject, then load students to begin marking.
    </div>
  <?php elseif ($students !== []): ?>
    <form method="post">
      <input type="hidden" name="attendance_date" value="<?= e($selectedDate) ?>">
      <input type="hidden" name="class_id" value="<?= $selectedClass ?>">
      <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Student Name</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student): ?>
              <?php
                $sid = (int) $student['id'];
                $current = $existing[$sid] ?? 'Present';
              ?>
              <tr>
                <td><?= e($student['student_id']) ?></td>
                <td><?= e($student['full_name']) ?></td>
                <td>
                  <div class="status-group" role="group" aria-label="Attendance status for <?= e($student['full_name']) ?>">
                    <?php foreach (['Present', 'Absent', 'Late'] as $status): ?>
                      <label class="status-option status-<?= strtolower($status) ?>">
                        <input
                          type="radio"
                          name="status[<?= $sid ?>]"
                          value="<?= $status ?>"
                          <?= $current === $status ? 'checked' : '' ?>
                          required
                        >
                        <span><?= $status ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="form-actions">
        <button type="submit" name="save_attendance" value="1" class="btn btn-primary">Save attendance</button>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
