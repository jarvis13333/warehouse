<?php
session_start();
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

if (auth_user()) {
    redirect('/index.php');
}

$error = '';
$username = 'admin';
$password = 'admin123';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        if (auth_login($username, $password)) {
            redirect('/index.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sign in - Warehouse Management</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="<?= e(base_url('/styles.css')) ?>">
</head>
<body class="auth-body">
<div class="auth-layout">
	<div class="container auth-page">
		<div class="auth-brand"><?= icon('warehouse') ?></div>
		<h1 class="auth-product-title">Warehouse System</h1>
		<h2 class="auth-form-title">Sign in to your account</h2>
		<?php if ($error): ?>
			<div class="alert"><?= e($error) ?></div>
		<?php endif; ?>
		<form method="post" class="form">
			<label>Username
				<input type="text" name="username" required value="<?= e($username) ?>" autocomplete="username" placeholder="Enter your username">
			</label>
			<label>Password
				<input type="password" name="password" required value="<?= e($password) ?>" autocomplete="current-password" placeholder="Enter your password">
			</label>
			<button type="submit" class="btn btn-full" aria-label="Sign in">
				<span>Sign in</span>
				<?= icon('login') ?>
			</button>
		</form>
	</div>
	<?= copyright_footer_html() ?>
</div>
</body>
</html>
