<?php
require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

require_admin();

$pdo = get_db();

$action = $_GET['action'] ?? 'list';
$me = auth_user();

function users_admin_count(PDO $pdo): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
}

if ($action === 'add') {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'user');
        if ($username === '') {
            $error = 'Username is required.';
        } elseif (strlen($username) > 50) {
            $error = 'Username is too long (max 50 characters).';
        } elseif ($password === '' || strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($role !== 'admin' && $role !== 'user') {
            $error = 'Invalid role.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
                $ins->execute([$username, $hash, $role]);
                redirect('/index.php?page=users');
            } catch (PDOException $e) {
                if ((int)$e->errorInfo[1] === 1062) {
                    $error = 'That username is already taken.';
                } else {
                    $error = 'Save failed: ' . $e->getMessage();
                }
            }
        }
    }
    ?>
    <div class="row row-toolbar">
        <h2>Add user</h2>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=users')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
    <?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="form-card">
        <form method="post" class="form">
            <label>Username *
                <input type="text" name="username" required maxlength="50" autocomplete="username">
            </label>
            <label>Password *
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
            </label>
            <label>Role
                <select name="role">
                    <option value="user">User</option>
                    <option value="admin">Administrator</option>
                </select>
            </label>
            <div class="form-actions">
                <button type="submit" class="btn ok"><?= icon('check') ?> Save user</button>
                <a class="btn secondary" href="<?= e(base_url('/index.php?page=users')) ?>">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    return;
}

if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        echo empty_state_html('User not found', 'This user may have been deleted or the link is invalid.', 'user');
        echo '<p class="form-actions"><a class="btn secondary" href="' . e(base_url('/index.php?page=users')) . '">' . icon('arrow-left') . ' Back to users</a></p>';
        return;
    }
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim((string)($_POST['username'] ?? ''));
        $role = (string)($_POST['role'] ?? 'user');
        $newPass = (string)($_POST['new_password'] ?? '');
        $newPass2 = (string)($_POST['new_password_confirm'] ?? '');
        if ($username === '') {
            $error = 'Username is required.';
        } elseif (strlen($username) > 50) {
            $error = 'Username is too long (max 50 characters).';
        } elseif ($role !== 'admin' && $role !== 'user') {
            $error = 'Invalid role.';
        } elseif ($row['role'] === 'admin' && $role === 'user' && users_admin_count($pdo) <= 1) {
            $error = 'Cannot remove the last administrator.';
        } elseif ($newPass !== '' && strlen($newPass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPass !== '' && $newPass !== $newPass2) {
            $error = 'New password fields do not match.';
        } else {
            $dup = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
            $dup->execute([$username, $id]);
            if ($dup->fetch()) {
                $error = 'That username is already taken.';
            } else {
                try {
                    $upd = $pdo->prepare('UPDATE users SET username = ?, role = ? WHERE id = ?');
                    $upd->execute([$username, $role, $id]);
                    if ($newPass !== '') {
                        $err = auth_admin_set_password($id, $newPass);
                        if ($err !== null) {
                            $error = $err;
                        }
                    }
                    if ($error === '') {
                        auth_refresh_session_if_user($id);
                        redirect('/index.php?page=users');
                    }
                } catch (Throwable $e) {
                    $error = 'Update failed: ' . $e->getMessage();
                }
            }
        }
        if ($error !== '') {
            $row['username'] = $username;
            $row['role'] = $role;
        }
    }
    ?>
    <div class="row row-toolbar">
        <h2>Edit user</h2>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=users')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
    <?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="form-card">
        <form method="post" class="form">
            <label>Username *
                <input type="text" name="username" required maxlength="50" value="<?= e($row['username']) ?>" autocomplete="username">
            </label>
            <label>Role
                <select name="role">
                    <option value="user" <?= $row['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
            </label>
            <p class="muted">Leave blank to keep the current password.</p>
            <label>New password (optional)
                <input type="password" name="new_password" minlength="8" autocomplete="new-password">
            </label>
            <label>Confirm new password
                <input type="password" name="new_password_confirm" minlength="8" autocomplete="new-password">
            </label>
            <div class="form-actions">
                <button type="submit" class="btn ok"><?= icon('check') ?> Save changes</button>
                <a class="btn secondary" href="<?= e(base_url('/index.php?page=users')) ?>">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    return;
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        echo empty_state_html('User not found', 'This user may have been deleted or the link is invalid.', 'user');
        echo '<p class="form-actions"><a class="btn secondary" href="' . e(base_url('/index.php?page=users')) . '">' . icon('arrow-left') . ' Back to users</a></p>';
        return;
    }
    $error = '';
    if ((int)$me['id'] === $id) {
        $error = 'You cannot delete your own account.';
    } elseif ($row['role'] === 'admin' && users_admin_count($pdo) <= 1) {
        $error = 'Cannot delete the last administrator.';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        redirect('/index.php?page=users');
    }
    ?>
    <div class="row row-toolbar">
        <h2>Delete user</h2>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=users')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
    <?php if ($error !== ''): ?>
        <div class="alert"><?= e($error) ?></div>
        <p class="form-actions"><a class="btn secondary" href="<?= e(base_url('/index.php?page=users')) ?>"><?= icon('arrow-left') ?> Back to users</a></p>
    <?php else: ?>
        <div class="form-card confirm-card">
            <p>Delete user <strong><?= e($row['username']) ?></strong> (<?= e($row['role']) ?>)? This cannot be undone.</p>
            <form method="post" class="form-actions">
                <button class="btn danger" type="submit"><?= icon('trash') ?> Delete user</button>
                <a class="btn secondary" href="<?= e(base_url('/index.php?page=users')) ?>">Cancel</a>
            </form>
        </div>
    <?php endif; ?>
<?php
    return;
}

$users = $pdo->query('SELECT id, username, role FROM users ORDER BY username ASC')->fetchAll();
?>
<div class="row row-toolbar">
    <h2>Users</h2>
    <a class="btn icon-btn btn-add" href="<?= e(base_url('/index.php?page=users&action=add')) ?>" aria-label="Add user" title="Add user"><?= icon('plus') ?></a>
</div>
<p class="muted page-intro">Manage accounts and roles. At least one administrator must always remain.</p>
<?php if (!$users): ?>
    <?= empty_state_html('No users found', 'Add a user account to get started.', 'users') ?>
<?php else: ?>
<table class="data-table">
    <thead>
    <tr>
        <th>Username</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td>
                <?= e($u['username']) ?>
                <?php if ((int)$u['id'] === (int)$me['id']): ?>
                    <span class="badge you">You</span>
                <?php endif; ?>
            </td>
            <td><?= e($u['role'] === 'admin' ? 'Administrator' : 'User') ?></td>
            <td class="actions">
                <div class="action-btns">
                    <a class="btn icon-btn secondary sm" href="<?= e(base_url('/index.php?page=users&action=edit&id=' . (int)$u['id'])) ?>" aria-label="Edit" title="Edit"><?= icon('pencil') ?></a>
                    <a class="btn icon-btn danger sm" href="<?= e(base_url('/index.php?page=users&action=delete&id=' . (int)$u['id'])) ?>" aria-label="Delete" title="Delete"><?= icon('trash') ?></a>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

