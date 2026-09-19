<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Enter your username and password to continue.';
    } elseif (attempt_login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'That username or password is incorrect. Try again.';
    }
}

$pageTitle = 'Login — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
  <div class="login-shell">
    <section class="login-intro">
      <h1>SFIT Student's<br>Attendance System</h1>
      <p class="lede">Teachers sign in to keep attendance secure, take daily records, run roll call, and review reports by student or class.</p>
      <ul class="feature-list">
        <li>User Login</li>
        <li>Take Attendance</li>
        <li>Roll Call</li>
        <li>View Attendance Report</li>
      </ul>
    </section>

    <section class="login-card">
      <h2>Teacher Login</h2>
      <p class="muted">Use your teacher account to open the system.</p>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" class="stack-form" autocomplete="username">
        <label>
          Username
          <input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username">
        </label>
        <label>
          Password
          <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary">Sign in</button>
      </form>

      <p class="hint">Demo account: <strong>teacher</strong> / <strong>teacher123</strong></p>
    </section>
  </div>
</body>
</html>
