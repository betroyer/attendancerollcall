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

$selectedDate = trim((string) ($_POST['attendance_date'] ?? $_GET['date'] ?? date('Y-m-d')));
$selectedClass = (int) ($_POST['class_id'] ?? $_GET['class_id'] ?? 0);
$selectedSubject = (int) ($_POST['subject_id'] ?? $_GET['subject_id'] ?? 0);
$currentIndex = (int) ($_POST['current_index'] ?? $_GET['i'] ?? 0);

$students = [];
$existing = [];
$className = '';
$subjectName = '';
$sessionActive = false;

function load_roll_students(PDO $pdo, int $classId): array
{
    if ($classId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT id, student_id, full_name
         FROM students
         WHERE class_id = ?
         ORDER BY full_name'
    );
    $stmt->execute([$classId]);
    return $stmt->fetchAll();
}

function load_existing_statuses(PDO $pdo, int $classId, int $subjectId, string $date): array
{
    $map = [];
    if ($classId <= 0 || $subjectId <= 0 || $date === '') {
        return $map;
    }

    $stmt = $pdo->prepare(
        'SELECT student_id, status
         FROM attendance
         WHERE class_id = ? AND subject_id = ? AND attendance_date = ?'
    );
    $stmt->execute([$classId, $subjectId, $date]);
    foreach ($stmt->fetchAll() as $row) {
        $map[(int) $row['student_id']] = $row['status'];
    }
    return $map;
}

function save_one_status(
    PDO $pdo,
    int $studentId,
    int $classId,
    int $subjectId,
    int $teacherId,
    string $date,
    string $status
): void {
    $allowed = ['Present', 'Absent', 'Late'];
    if ($studentId <= 0 || !in_array($status, $allowed, true)) {
        throw new InvalidArgumentException('Invalid attendance status.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO attendance (student_id, class_id, subject_id, teacher_id, attendance_date, status)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           status = VALUES(status),
           teacher_id = VALUES(teacher_id),
           class_id = VALUES(class_id)'
    );
    $stmt->execute([$studentId, $classId, $subjectId, $teacherId, $date, $status]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'start') {
        if ($selectedDate === '' || $selectedClass <= 0 || $selectedSubject <= 0) {
            $error = 'Please select date, class, and subject to start Roll Call.';
        } else {
            $students = load_roll_students($pdo, $selectedClass);
            if ($students === []) {
                $error = 'No students found in this class. Choose another class or add students first.';
            } else {
                $sessionActive = true;
                $currentIndex = 0;
            }
        }
    }

    if (in_array($action, ['mark', 'skip', 'back'], true)) {
        $students = load_roll_students($pdo, $selectedClass);
        $sessionActive = $students !== [] && $selectedClass > 0 && $selectedSubject > 0 && $selectedDate !== '';

        if (!$sessionActive) {
            $error = 'Roll Call session is incomplete. Please start again.';
        } elseif ($action === 'mark') {
            $studentId = (int) ($_POST['student_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');

            try {
                save_one_status(
                    $pdo,
                    $studentId,
                    $selectedClass,
                    $selectedSubject,
                    (int) $teacher['id'],
                    $selectedDate,
                    $status
                );
                $currentIndex++;
                $message = '';
            } catch (Throwable $e) {
                $error = 'Could not save this mark. Check your connection and try again.';
            }
        } elseif ($action === 'skip') {
            $currentIndex++;
            $message = '';
        } elseif ($action === 'back') {
            $currentIndex = max(0, $currentIndex - 1);
            $message = '';
        }
    }
}

if ($sessionActive || ($selectedClass > 0 && $selectedSubject > 0 && $selectedDate !== '' && isset($_GET['start']))) {
    if ($students === []) {
        $students = load_roll_students($pdo, $selectedClass);
    }
    if ($students !== []) {
        $sessionActive = true;
    }
}

if ($selectedClass > 0) {
    foreach ($classes as $class) {
        if ((int) $class['id'] === $selectedClass) {
            $className = $class['class_name'];
            break;
        }
    }
}

if ($selectedSubject > 0) {
    foreach ($subjects as $subject) {
        if ((int) $subject['id'] === $selectedSubject) {
            $subjectName = $subject['subject_name'];
            break;
        }
    }
}

if ($sessionActive) {
    $existing = load_existing_statuses($pdo, $selectedClass, $selectedSubject, $selectedDate);
}

$total = count($students);
$finished = $sessionActive && $total > 0 && $currentIndex >= $total;
$currentStudent = (!$finished && $sessionActive && $total > 0)
    ? $students[$currentIndex]
    : null;

$markedCount = 0;
foreach ($students as $student) {
    if (isset($existing[(int) $student['id']])) {
        $markedCount++;
    }
}

$pageTitle = 'Roll Call — ' . APP_NAME;
$activePage = 'rollcall';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h1>Roll Call</h1>
      <p class="lede">Call students one by one and mark Present, Absent, or Late as you go.</p>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <div class="alert alert-success" role="status"><?= e($message) ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!$sessionActive): ?>
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
      <button type="submit" name="action" value="start" class="btn btn-primary">Start Roll Call</button>
    </form>
    <div class="empty-state">
      <strong>Start a live roll call</strong>
      Pick today’s date, the class, and the subject, then call each student in order.
    </div>
  <?php else: ?>
    <div class="roll-meta">
      <div>
        <strong><?= e($className) ?></strong>
        <span>· <?= e($subjectName) ?></span>
        <span>· <?= e(date('M j, Y', strtotime($selectedDate))) ?></span>
      </div>
      <a class="btn btn-secondary" href="rollcall.php">Change class</a>
    </div>

    <div class="roll-progress">
      <div class="roll-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?= $total ?>" aria-valuenow="<?= $markedCount ?>" aria-label="Roll call progress">
        <?php $progress = $total > 0 ? max(0, min(1, $markedCount / $total)) : 0; ?>
        <span style="transform: scaleX(<?= number_format($progress, 4, '.', '') ?>)"></span>
      </div>
      <p><?= $markedCount ?> of <?= $total ?> marked · Step <?= min($currentIndex + 1, max($total, 1)) ?> / <?= $total ?></p>
    </div>

    <?php if ($finished): ?>
      <div class="roll-complete">
        <h2>Roll Call complete</h2>
        <p class="lede">All students in this class have been called. You can review the report or start again.</p>
        <div class="form-actions roll-complete-actions">
          <a class="btn btn-primary" href="report.php?search_type=class&class_id=<?= $selectedClass ?>&search=1">View report</a>
          <a class="btn btn-secondary" href="rollcall.php">New Roll Call</a>
          <a class="btn btn-secondary" href="take_attendance.php?date=<?= e(urlencode($selectedDate)) ?>&class_id=<?= $selectedClass ?>&subject_id=<?= $selectedSubject ?>">Edit all at once</a>
        </div>
      </div>
    <?php elseif ($currentStudent): ?>
      <?php
        $sid = (int) $currentStudent['id'];
        $prior = $existing[$sid] ?? null;
      ?>
      <div class="roll-card" id="roll-card">
        <p class="roll-id"><?= e($currentStudent['student_id']) ?></p>
        <h2 class="roll-name"><?= e($currentStudent['full_name']) ?></h2>
        <?php if ($prior): ?>
          <p class="roll-prior">Already marked: <span class="badge badge-<?= strtolower($prior) ?>"><?= e($prior) ?></span></p>
        <?php endif; ?>

        <form method="post" class="roll-actions">
          <input type="hidden" name="attendance_date" value="<?= e($selectedDate) ?>">
          <input type="hidden" name="class_id" value="<?= $selectedClass ?>">
          <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
          <input type="hidden" name="current_index" value="<?= $currentIndex ?>">
          <input type="hidden" name="student_id" value="<?= $sid ?>">
          <input type="hidden" name="action" value="mark">

          <button type="submit" name="status" value="Present" class="btn roll-btn present">Present</button>
          <button type="submit" name="status" value="Absent" class="btn roll-btn absent">Absent</button>
          <button type="submit" name="status" value="Late" class="btn roll-btn late">Late</button>
        </form>

        <div class="roll-nav">
          <form method="post">
            <input type="hidden" name="attendance_date" value="<?= e($selectedDate) ?>">
            <input type="hidden" name="class_id" value="<?= $selectedClass ?>">
            <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
            <input type="hidden" name="current_index" value="<?= $currentIndex ?>">
            <button type="submit" name="action" value="back" class="btn btn-secondary" <?= $currentIndex === 0 ? 'disabled' : '' ?>>Previous</button>
          </form>
          <form method="post">
            <input type="hidden" name="attendance_date" value="<?= e($selectedDate) ?>">
            <input type="hidden" name="class_id" value="<?= $selectedClass ?>">
            <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
            <input type="hidden" name="current_index" value="<?= $currentIndex ?>">
            <button type="submit" name="action" value="skip" class="btn btn-secondary">Skip</button>
          </form>
        </div>

        <p class="hint">Keyboard: <kbd>1</kbd> Present · <kbd>2</kbd> Absent · <kbd>3</kbd> Late · <kbd>→</kbd> Skip</p>
      </div>
    <?php endif; ?>

    <?php if ($students !== []): ?>
      <div class="roll-roster">
        <h3>Class roster</h3>
        <ul>
          <?php foreach ($students as $index => $student): ?>
            <?php
              $status = $existing[(int) $student['id']] ?? null;
              $isCurrent = !$finished && $index === $currentIndex;
            ?>
            <li class="<?= $isCurrent ? 'is-current' : '' ?> <?= $status ? 'is-marked' : '' ?>">
              <span><?= e($student['full_name']) ?></span>
              <?php if ($status): ?>
                <span class="badge badge-<?= strtolower($status) ?>"><?= e($status) ?></span>
              <?php elseif ($isCurrent): ?>
                <span class="roster-calling">Calling…</span>
              <?php else: ?>
                <span class="roster-pending">Pending</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php if ($sessionActive && $currentStudent): ?>
<script>
  document.addEventListener('keydown', (event) => {
    if (event.target.matches('input, select, textarea')) return;

    const form = document.querySelector('.roll-actions');
    if (!form) return;

    const map = { '1': 'Present', '2': 'Absent', '3': 'Late' };
    if (map[event.key]) {
      event.preventDefault();
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'status';
      input.value = map[event.key];
      form.appendChild(input);
      form.submit();
      return;
    }

    if (event.key === 'ArrowRight') {
      event.preventDefault();
      const skip = document.querySelector('button[value="skip"]');
      if (skip) skip.click();
    }
  });
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
