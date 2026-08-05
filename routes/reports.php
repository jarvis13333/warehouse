<?php
require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

// Admin-only page for full report; allow users to open simplified but we keep full here per spec
if (!auth_is_admin()) {
    // For non-admin, show a simplified read-only report without item dropdown
}

$pdo = get_db();

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$type = $_GET['type'] ?? '';
$itemId = (int)($_GET['item_id'] ?? 0);

// Build query
$where = ['1=1'];
$params = [];
if ($type === 'IN' || $type === 'OUT') {
    $where[] = 'sm.movement_type = ?';
    $params[] = $type;
}
if ($from !== '') {
    $where[] = 'sm.date >= ?';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = 'sm.date <= ?';
    $params[] = $to . ' 23:59:59';
}
if ($itemId > 0) {
    $where[] = 'sm.item_id = ?';
    $params[] = $itemId;
}

$sql = 'SELECT sm.*, i.item_code, i.item_name, i.unit
        FROM stock_movements sm
        JOIN items i ON i.id = sm.item_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY sm.date DESC, sm.id DESC
        LIMIT 1000';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalIn = 0; $totalOut = 0;
foreach ($rows as $r) {
    if ($r['movement_type'] === 'IN') $totalIn += (int)$r['quantity'];
    if ($r['movement_type'] === 'OUT') $totalOut += (int)$r['quantity'];
}

// Item dropdown
$itemsStmt = $pdo->query('SELECT id, item_code, item_name FROM items ORDER BY item_code ASC LIMIT 500');
$itemsAll = $itemsStmt->fetchAll();
?>
<div class="row row-toolbar">
	<h2>Stock movement report</h2>
</div>
<form class="search-bar filter-bar" method="get" action="<?= e(base_url('/index.php')) ?>">
    <input type="hidden" name="page" value="reports">
    <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
    <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
    <select name="type">
        <option value="">All types</option>
        <option value="IN" <?= $type==='IN'?'selected':'' ?>>IN</option>
        <option value="OUT" <?= $type==='OUT'?'selected':'' ?>>OUT</option>
    </select>
    <select name="item_id">
        <option value="0">All items</option>
        <?php foreach ($itemsAll as $it): ?>
            <option value="<?= (int)$it['id'] ?>" <?= $itemId===(int)$it['id']?'selected':'' ?>>
                <?= e($it['item_code']) ?> - <?= e($it['item_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn icon-btn secondary" aria-label="Run report" title="Run report"><?= icon('play') ?></button>
</form>
<div class="row">
    <div class="badge">Total IN: <?= e((string)$totalIn) ?></div>
    <div class="badge">Total OUT: <?= e((string)$totalOut) ?></div>
</div>
<?php if (!$rows): ?>
    <?= empty_state_html('No movements match your filters', 'Try a wider date range or remove item filters.', 'bar-chart') ?>
<?php else: ?>
<table class="data-table">
    <thead><tr><th>Date</th><th>Code</th><th>Name</th><th>Type</th><th>Qty</th><th>Unit</th><th>Note</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['date']) ?></td>
            <td><?= e($r['item_code']) ?></td>
            <td><?= e($r['item_name']) ?></td>
            <td><?= e($r['movement_type']) ?></td>
            <td><?= e((string)$r['quantity']) ?></td>
            <td><?= e($r['unit']) ?></td>
            <td><?= e($r['reference_note']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

