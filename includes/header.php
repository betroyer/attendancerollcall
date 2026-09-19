<?php
declare(strict_types=1);

$teacher = current_teacher();
$pageTitle = $pageTitle ?? APP_NAME;
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
<?php if ($teacher): ?>
  <header class="site-header">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true">SFIT</span>
      <div>
        <strong>Student's Attendance System</strong>
        <small>Signed in as <?= e($teacher['full_name']) ?></small>
      </div>
    </div>
    <nav class="main-nav" aria-label="Main">
      <a class="<?= $activePage === 'home' ? 'active' : '' ?>" href="index.php" <?= $activePage === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
      <a class="<?= $activePage === 'take' ? 'active' : '' ?>" href="take_attendance.php" <?= $activePage === 'take' ? 'aria-current="page"' : '' ?>>Take Attendance</a>
      <a class="<?= $activePage === 'rollcall' ? 'active' : '' ?>" href="rollcall.php" <?= $activePage === 'rollcall' ? 'aria-current="page"' : '' ?>>Roll Call</a>
      <a class="<?= $activePage === 'report' ? 'active' : '' ?>" href="report.php" <?= $activePage === 'report' ? 'aria-current="page"' : '' ?>>Attendance Report</a>
      <a class="logout" href="logout.php">Logout</a>
    </nav>
  </header>
<?php endif; ?>
  <main class="page" id="main">
