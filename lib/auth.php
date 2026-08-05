<?php
// Authentication and authorization helpers

require_once dirname(__DIR__) . '/db/connection.php';
require_once __DIR__ . '/utils.php';

function auth_login(string $username, string $password): bool {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() == '42S02') {
            // Table doesn't exist
            if (php_sapi_name() !== 'cli') {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
                die("
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Database tables not initialized</title>
                    <style>
                        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                        .error { background: #fee; border: 2px solid #f00; padding: 20px; border-radius: 5px; }
                        .site-footer { margin-top: 24px; text-align: center; color: #6b7280; font-size: 13px; }
                    </style>
                </head>
                <body>
                    <div class='error'>
                        <h2>Database tables not initialized</h2>
                        <p>The required database tables are missing.</p>
                        <p>Import <strong>db/rebuild_full_en.sql</strong> via phpMyAdmin to create the schema and demo data.</p>
                    </div>
                    " . copyright_footer_html() . "
                </body>
                </html>
                ");
            } else {
                die("Error: database tables do not exist. Import db/rebuild_full_en.sql via phpMyAdmin.\n");
            }
        }
        throw $e;
    }
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function auth_is_admin(): bool {
    $u = auth_user();
    return $u && $u['role'] === 'admin';
}

function require_login(): void {
    if (!auth_user()) {
        redirect('/login.php');
    }
}

function require_admin(): void {
    require_login();
    if (!auth_is_admin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

/** @return string|null Error message, or null on success */
function auth_change_password(int $userId, string $currentPassword, string $newPassword): ?string {
    if (strlen($newPassword) < 8) {
        return 'New password must be at least 8 characters.';
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return 'User not found.';
    }
    if (!password_verify($currentPassword, (string)$row['password_hash'])) {
        return 'Current password is incorrect.';
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upd->execute([$hash, $userId]);
    return null;
}

/** Admin-only: set password without current password check. @return string|null error */
function auth_admin_set_password(int $userId, string $newPassword): ?string {
    if (strlen($newPassword) < 8) {
        return 'Password must be at least 8 characters.';
    }
    $pdo = get_db();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upd->execute([$hash, $userId]);
    if ($upd->rowCount() === 0) {
        return 'User not found.';
    }
    return null;
}

/** Reload id/username/role into session after the logged-in row was updated */
function auth_refresh_session_if_user(int $userId): void {
    $me = auth_user();
    if (!$me || (int)$me['id'] !== $userId) {
        return;
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $_SESSION['user'] = [
            'id' => (int)$row['id'],
            'username' => $row['username'],
            'role' => $row['role'],
        ];
    }
}


