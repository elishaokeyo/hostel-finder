<?php
// Shared helpers for every admin page: session, roles, login check, CSRF protection.
require __DIR__ . '/../config.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

db()->exec("CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
)");
ensure_column('admins', 'role', "role ENUM('admin','landlord') NOT NULL DEFAULT 'admin'");
ensure_column('admins', 'active', 'active TINYINT(1) NOT NULL DEFAULT 1');

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

function is_admin(): bool { return ($_SESSION['role'] ?? '') === 'admin'; }

function flash(string $msg): void { $_SESSION['flash'] = $msg; }

// Must be logged in, and the account must still exist and be active.
function require_login(): void {
    $ok = false;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT role, active FROM admins WHERE id = ?');
        $st->execute([(int)$_SESSION['uid']]);
        $u = $st->fetch();
        if ($u && $u['active']) { $_SESSION['role'] = $u['role']; $ok = true; }
    }
    if (!$ok) { session_destroy(); header('Location: login.php'); exit; }
    check_csrf();
}

function require_admin(): void {
    if (!is_admin()) { flash('That page is for administrators only.'); header('Location: index.php'); exit; }
}

// Admins can edit every hostel; landlords only the ones they own.
function can_edit_hostel(array $hostel): bool {
    return is_admin() || (int)($hostel['owner_id'] ?? 0) === (int)($_SESSION['uid'] ?? -1);
}

function default_place(): array {
    return db()->query('SELECT latitude, longitude FROM places ORDER BY is_default DESC, id LIMIT 1')->fetch()
        ?: ['latitude' => 0, 'longitude' => 0];
}
