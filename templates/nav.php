<?php
require_once dirname(__DIR__) . '/lib/auth.php';
$user = auth_user();
?>
<nav class="nav">
	<ul>
		<?php if ($user): ?>
			<?php $activePage = $activePage ?? ''; ?>
			<li><a class="<?= $activePage==='items'?'active':'' ?>" href="<?= e(base_url('/index.php?page=items')) ?>"><?= icon('package') ?> Items</a></li>
			<li><a class="<?= $activePage==='reports'?'active':'' ?>" href="<?= e(base_url('/index.php?page=reports')) ?>"><?= icon('bar-chart') ?> Reports</a></li>
			<?php if (auth_is_admin()): ?>
				<li><a class="<?= $activePage==='dashboard'?'active':'' ?>" href="<?= e(base_url('/index.php?page=dashboard')) ?>"><?= icon('layout-dashboard') ?> Dashboard</a></li>
				<li><a class="<?= $activePage==='users'?'active':'' ?>" href="<?= e(base_url('/index.php?page=users')) ?>"><?= icon('users') ?> Users</a></li>
			<?php endif; ?>
			<li><a class="<?= $activePage==='profile'?'active':'' ?>" href="<?= e(base_url('/index.php?page=profile')) ?>"><?= icon('user') ?> Profile</a></li>
		<?php else: ?>
			<li><a href="<?= e(base_url('/login.php')) ?>"><?= icon('login') ?> Sign in</a></li>
		<?php endif; ?>
	</ul>
	<?php if ($user): ?>
		<div class="nav-footer">
			<a href="<?= e(base_url('/logout.php')) ?>"><?= icon('log-out') ?> Sign out <span class="muted">(<?= e($user['username']) ?>)</span></a>
		</div>
	<?php endif; ?>
</nav>
