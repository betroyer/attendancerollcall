<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$teacher = current_teacher();

$stats = [
    'students' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'classes' => (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn(),
    'today' => (int) $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE()")->fetchColumn(),
];

$pageTitle = 'Home — ' . APP_NAME;
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-panel">
  <div>
    <h1>Hello, <?= e($teacher['full_name']) ?></h1>
    <p class="lede">Mark attendance, run roll call, or look up records — whichever your class needs next.</p>
  </div>
  <div class="stat-grid" aria-label="Quick counts">
    <div class="stat">
      <span><?= $stats['students'] ?></span>
      <small>Students</small>
    </div>
    <div class="stat">
      <span><?= $stats['classes'] ?></span>
      <small>Classes</small>
    </div>
    <div class="stat">
      <span><?= $stats['today'] ?></span>
      <small>Marked today</small>
    </div>
  </div>
</section>

<section class="feature-grid feature-grid-3" aria-label="Main actions">
  <a class="feature-tile" href="take_attendance.php">
    <h2>Take Attendance</h2>
    <p>Select date, class, and subject, then mark each student Present, Absent, or Late.</p>
  </a>
  <a class="feature-tile" href="rollcall.php">
    <h2>Roll Call</h2>
    <p>Call students one by one in class and mark Present, Absent, or Late as you go.</p>
  </a>
  <a class="feature-tile" href="report.php">
    <h2>Attendance Report</h2>
    <p>Search attendance history by Student ID or by Class.</p>
  </a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
