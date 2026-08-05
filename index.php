<?php
// Root entry point - redirect to public directory (路径与 config.php 中 base_url 一致)
$config = require __DIR__ . '/config.php';
$base = rtrim($config['base_url'] ?? '', '/');
header('Location: ' . $base . '/login.php');
exit;

