<?php
require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

$user = auth_user();
if (!$user) {
    return;
}

$error = '';
$success = isset($_GET['ok']) && $_GET['ok'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'Please fill in all password fields.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $err = auth_change_password((int)$user['id'], $current, $new);
        if ($err !== null) {
            $error = $err;
        } else {
            redirect('/index.php?page=profile&ok=1');
        }
    }
}

$roleLabel = $user['role'] === 'admin' ? 'Administrator' : 'User';
?>
<div class="row row-toolbar">
    <h2>Profile</h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">Password updated successfully.</div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="page-stack">
    <div class="detail-card">
        <h3>Account</h3>
        <dl class="detail-list">
            <div class="detail-row">
                <dt>Username</dt>
                <dd><?= e($user['username']) ?></dd>
            </div>
            <div class="detail-row">
                <dt>Role</dt>
                <dd><?= e($roleLabel) ?></dd>
            </div>
        </dl>
        <p class="muted page-intro">Username cannot be changed here. Contact an administrator if you need a different account.</p>
    </div>

    <div class="form-card">
        <h3>Change password</h3>
        <form method="post" class="form">
            <label>Current password
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>
            <label>New password
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
            </label>
            <label>Confirm new password
                <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
            </label>
            <p class="muted">At least 8 characters.</p>
            <div class="form-actions">
                <button type="submit" class="btn"><?= icon('key') ?> Update password</button>
            </div>
        </form>
    </div>
</div>
