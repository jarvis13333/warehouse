<?php
require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

$pdo = get_db();
$action = $_GET['action'] ?? 'list';

if ($action === 'add') {
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $itemCode = trim((string)$_POST['item_code'] ?? '');
        $itemName = trim((string)$_POST['item_name'] ?? '');
        $category = trim((string)$_POST['category'] ?? '');
        $location = trim((string)$_POST['location'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $unit = trim((string)$_POST['unit'] ?? '');
        $reorder = (int)($_POST['reorder_level'] ?? 10);
        $error = '';
        if ($itemCode === '' || $itemName === '' || $unit === '') {
            $error = 'Please fill in required fields (Code, Name, Unit).';
        }
        if ($error === '') {
            try {
                $stmt = $pdo->prepare('INSERT INTO items (item_code, item_name, category, location, quantity, unit, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$itemCode, $itemName, $category, $location, $quantity, $unit, $reorder]);
                redirect('/index.php?page=items');
            } catch (Throwable $e) {
                $error = 'Save failed: ' . $e->getMessage();
            }
        }
    }
    ?>
    <div class="row row-toolbar">
        <h2>Add item</h2>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=items')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
    <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="form-card form-card-wide">
        <form method="post" class="form">
            <div class="row">
                <label class="col">Code *<input type="text" name="item_code" required></label>
                <label class="col">Name *<input type="text" name="item_name" required></label>
            </div>
            <div class="row">
                <label class="col">Category<input type="text" name="category"></label>
                <label class="col">Location<input type="text" name="location"></label>
            </div>
            <div class="row">
                <label class="col">Quantity<input type="number" name="quantity" value="0" min="0"></label>
                <label class="col">Unit *<input type="text" name="unit" required></label>
                <label class="col">Reorder level<input type="number" name="reorder_level" value="10" min="0"></label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn ok"><?= icon('check') ?> Save item</button>
                <a class="btn secondary" href="<?= e(base_url('/index.php?page=items')) ?>">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    return;
}

if ($action === 'edit') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        echo empty_state_html('Item not found', 'This item may have been deleted or the link is invalid.', 'package');
        echo '<p class="form-actions"><a class="btn secondary" href="' . e(base_url('/index.php?page=items')) . '">' . icon('arrow-left') . ' Back to items</a></p>';
        return;
    }
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $itemCode = trim((string)$_POST['item_code'] ?? '');
        $itemName = trim((string)$_POST['item_name'] ?? '');
        $category = trim((string)$_POST['category'] ?? '');
        $location = trim((string)$_POST['location'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $unit = trim((string)$_POST['unit'] ?? '');
        $reorder = (int)($_POST['reorder_level'] ?? 10);
        if ($itemCode === '' || $itemName === '' || $unit === '') {
            $error = 'Please fill in required fields (Code, Name, Unit).';
        } else {
            try {
                $stmtU = $pdo->prepare('UPDATE items SET item_code=?, item_name=?, category=?, location=?, quantity=?, unit=?, reorder_level=? WHERE id=?');
                $stmtU->execute([$itemCode, $itemName, $category, $location, $quantity, $unit, $reorder, $id]);
                redirect('/index.php?page=items');
            } catch (Throwable $e) {
                $error = 'Update failed: ' . $e->getMessage();
            }
        }
        $item = array_merge($item, [
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'category' => $category,
            'location' => $location,
            'quantity' => $quantity,
            'unit' => $unit,
            'reorder_level' => $reorder,
        ]);
    }
    ?>
    <div class="row row-toolbar">
        <h2>Edit item</h2>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=items')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
    <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="form-card form-card-wide">
        <form method="post" class="form">
            <div class="row">
                <label class="col">Code *<input type="text" name="item_code" required value="<?= e($item['item_code']) ?>"></label>
                <label class="col">Name *<input type="text" name="item_name" required value="<?= e($item['item_name']) ?>"></label>
            </div>
            <div class="row">
                <label class="col">Category<input type="text" name="category" value="<?= e($item['category']) ?>"></label>
                <label class="col">Location<input type="text" name="location" value="<?= e($item['location']) ?>"></label>
            </div>
            <div class="row">
                <label class="col">Quantity<input type="number" name="quantity" min="0" value="<?= e((string)$item['quantity']) ?>"></label>
                <label class="col">Unit *<input type="text" name="unit" required value="<?= e($item['unit']) ?>"></label>
                <label class="col">Reorder level<input type="number" name="reorder_level" min="0" value="<?= e((string)$item['reorder_level']) ?>"></label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn ok"><?= icon('check') ?> Save changes</button>
                <a class="btn secondary" href="<?= e(base_url('/index.php?page=items')) ?>">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    return;
}

if ($action === 'delete') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, item_code, item_name FROM items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        echo empty_state_html('Item not found', 'This item may have been deleted or the link is invalid.', 'package');
        echo '<p class="form-actions"><a class="btn secondary" href="' . e(base_url('/index.php?page=items')) . '">' . icon('arrow-left') . ' Back to items</a></p>';
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
        redirect('/index.php?page=items');
    }
    ?>
    <div class="row row-toolbar">
        <h2>Delete item</h2>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=items')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
    <div class="form-card confirm-card">
        <p>Delete <strong><?= e($item['item_code']) ?></strong> — <?= e($item['item_name']) ?>? This action cannot be undone.</p>
        <form method="post" class="form-actions">
            <button class="btn danger" type="submit"><?= icon('trash') ?> Delete item</button>
            <a class="btn secondary" href="<?= e(base_url('/index.php?page=items')) ?>">Cancel</a>
        </form>
    </div>
    <?php
    return;
}

// List with search
$q_name = trim((string)($_GET['name'] ?? ''));
$q_code = trim((string)($_GET['code'] ?? ''));
$q_cat  = trim((string)($_GET['category'] ?? ''));
$where = [];
$params = [];
if ($q_name !== '') { $where[] = 'item_name LIKE ?'; $params[] = '%' . $q_name . '%'; }
if ($q_code !== '') { $where[] = 'item_code LIKE ?'; $params[] = '%' . $q_code . '%'; }
if ($q_cat  !== '') { $where[] = 'category LIKE ?';  $params[] = '%' . $q_cat  . '%'; }
$sql = 'SELECT * FROM items';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$countSql = 'SELECT COUNT(*) FROM items';
if ($where) {
    $countSql .= ' WHERE ' . implode(' AND ', $where);
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();

$perPage = 25;
$pageNum = max(1, int_param('p', 1));
$pagination = list_pagination_meta($totalItems, $pageNum, $perPage);

$sql .= ' ORDER BY item_code ASC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$listQuery = [
    'page' => 'items',
    'name' => $q_name,
    'code' => $q_code,
    'category' => $q_cat,
];
$hasFilters = $q_name !== '' || $q_code !== '' || $q_cat !== '';
?>
<div class="row row-toolbar">
    <h2>Items</h2>
    <?php if (auth_is_admin()): ?>
        <a class="btn icon-btn btn-add" href="<?= e(base_url('/index.php?page=items&action=add')) ?>" aria-label="Add item" title="Add item"><?= icon('plus') ?></a>
    <?php endif; ?>
</div>
<form class="search-bar filter-bar" method="get" action="<?= e(base_url('/index.php')) ?>">
    <input type="hidden" name="page" value="items">
    <div class="search-field">
        <span class="search-icon"><?= icon('search') ?></span>
        <input type="text" name="name" placeholder="Search by name" value="<?= e($q_name) ?>">
    </div>
    <input type="text" name="code" placeholder="Item code" value="<?= e($q_code) ?>">
    <input type="text" name="category" placeholder="Category" value="<?= e($q_cat) ?>">
    <button type="submit" class="btn sm" aria-label="Search" title="Search"><?= icon('search') ?> Search</button>
</form>
<?php if (!$items): ?>
    <?= empty_state_html(
        $hasFilters ? 'No items match your filters' : 'No items yet',
        $hasFilters ? 'Try different search terms or clear the filters.' : 'Add your first item to get started.',
        'package'
    ) ?>
<?php else: ?>
<table class="data-table data-table-items">
    <thead>
    <tr>
        <th class="col-code">Code</th>
        <th class="col-name">Name</th>
        <th class="col-muted">Category</th>
        <th class="col-muted">Location</th>
        <th class="col-num col-qty">Qty</th>
        <th class="col-unit">Unit</th>
        <th class="col-num">Reorder</th>
        <th class="col-actions">Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $it): ?>
        <tr>
            <td class="col-code"><?= e($it['item_code']) ?></td>
            <td class="col-name"><a href="<?= e(base_url('/index.php?page=view&id=' . (int)$it['id'])) ?>"><?= e($it['item_name']) ?></a></td>
            <td class="col-muted"><?= e($it['category']) ?></td>
            <td class="col-muted"><?= e($it['location']) ?></td>
            <td class="col-num col-qty">
                <div class="cell-qty">
                    <span class="tabular"><?= e((string)$it['quantity']) ?></span>
                    <?php if ($it['quantity'] < 10): ?><span class="badge low badge-compact">LOW</span><?php endif; ?>
                </div>
            </td>
            <td class="col-unit"><?= e($it['unit']) ?></td>
            <td class="col-num tabular"><?= e((string)$it['reorder_level']) ?></td>
            <td class="actions">
                <div class="action-btns">
                    <a class="btn icon-btn sm secondary" href="<?= e(base_url('/index.php?page=view&id=' . (int)$it['id'])) ?>" aria-label="Details" title="Details"><?= icon('eye') ?></a>
                    <a class="btn icon-btn sm" href="<?= e(base_url('/index.php?page=movements&action=list&item_id=' . (int)$it['id'])) ?>" aria-label="Movement history" title="Movement history"><?= icon('history') ?></a>
                    <?php if (auth_is_admin()): ?>
                        <a class="btn icon-btn sm ok" href="<?= e(base_url('/index.php?page=movements&action=add&item_id=' . (int)$it['id'])) ?>" aria-label="Adjust stock" title="Adjust stock"><?= icon('stock') ?></a>
                        <a class="btn icon-btn sm secondary" href="<?= e(base_url('/index.php?page=items&action=edit&id=' . (int)$it['id'])) ?>" aria-label="Edit" title="Edit"><?= icon('pencil') ?></a>
                        <a class="btn icon-btn sm danger" href="<?= e(base_url('/index.php?page=items&action=delete&id=' . (int)$it['id'])) ?>" aria-label="Delete" title="Delete"><?= icon('trash') ?></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?= list_footer_html($pagination, $listQuery) ?>
<?php endif; ?>

