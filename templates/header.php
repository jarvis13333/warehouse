<?php
require_once dirname(__DIR__) . '/lib/utils.php';
$title = $title ?? 'Warehouse Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= e($title) ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="<?= e(base_url('/styles.css')) ?>">
</head>
<body>
<div class="container">
	<div class="app-shell">
		<aside class="sidebar">
			<div class="sidebar-header">
				<div class="brand"><?= icon('warehouse') ?> Warehouse</div>
			</div>
			<?php include __DIR__ . '/nav.php'; ?>
		</aside>
		<div class="main">
			<div class="workspace-card">
				<main class="content">
