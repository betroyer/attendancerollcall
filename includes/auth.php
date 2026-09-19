<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['teacher_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_teacher(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['teacher_id'],
        'username' => (string) $_SESSION['teacher_username'],
        'full_name' => (string) $_SESSION['teacher_name'],
    ];
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT id, username, password_hash, full_name FROM teachers WHERE username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $teacher = $stmt->fetch();

    if (!$teacher || !password_verify($password, $teacher['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['teacher_id'] = (int) $teacher['id'];
    $_SESSION['teacher_username'] = $teacher['username'];
    $_SESSION['teacher_name'] = $teacher['full_name'];

    return true;
}

function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
