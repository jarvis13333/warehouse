<?php
session_start();
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

require_login();

$page = $_GET['page'] ?? 'items';
$activePage = $page;
$pageTitles = [
	'dashboard' => 'Dashboard',
	'items' => 'Items',
	'movements' => 'Movements',
	'reports' => 'Reports',
	'view' => 'Item details',
	'profile' => 'Profile',
	'users' => 'Users',
];
$title = ($pageTitles[$page] ?? 'Warehouse Management') . ' - Warehouse Management';

ob_start();
switch ($page) {
    case 'dashboard':
        require_admin();
        require_once dirname(__DIR__) . '/routes/dashboard.php';
        break;
    case 'items':
        require_once dirname(__DIR__) . '/routes/items.php';
        break;
    case 'movements':
        require_once dirname(__DIR__) . '/routes/movements.php';
        break;
    case 'reports':
        require_once dirname(__DIR__) . '/routes/reports.php';
        break;
    case 'view':
        require_once dirname(__DIR__) . '/routes/view.php';
        break;
    case 'profile':
        require_once dirname(__DIR__) . '/routes/profile.php';
        break;
    case 'users':
        require_admin();
        require_once dirname(__DIR__) . '/routes/users.php';
        break;
    default:
        echo '<p>Page not found.</p>';
        break;
}
$content = ob_get_clean();

include dirname(__DIR__) . '/templates/header.php';
echo $content;
include dirname(__DIR__) . '/templates/footer.php';


