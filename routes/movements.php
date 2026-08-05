<?php
require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

$pdo = get_db();
$action = $_GET['action'] ?? 'list';
$itemId = (int)($_GET['item_id'] ?? 0);

$stmtItem = $pdo->prepare('SELECT * FROM items WHERE id = ?');
$stmtItem->execute([$itemId]);
$item = $stmtItem->fetch();
if (!$item) {
    echo empty_state_html('Item not found', 'This item may have been deleted or the link is invalid.', 'package');
    echo '<p class="form-actions"><a class="btn secondary" href="' . e(base_url('/index.php?page=items')) . '">' . icon('arrow-left') . ' Back to items</a></p>';
    return;
}

if ($action === 'add') {
    require_admin();
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $movementType = $_POST['movement_type'] === 'OUT' ? 'OUT' : 'IN';
        $qty = max(0, (int)($_POST['quantity'] ?? 0));
        $note = trim((string)($_POST['reference_note'] ?? ''));
        if ($qty <= 0) {
            $error = 'Quantity must be greater than 0.';
        } else {
            try {
                $pdo->beginTransaction();
                // Lock row to prevent race (best effort)
                $stmtLock = $pdo->prepare('SELECT quantity FROM items WHERE id = ? FOR UPDATE');
                $stmtLock->execute([$itemId]);
                $currentQty = (int)$stmtLock->fetchColumn();
                $newQty = $movementType === 'IN' ? $currentQty + $qty : $currentQty - $qty;
                if ($newQty < 0) {
                    throw new RuntimeException('Resulting stock would be negative. Operation rejected.');
                }
                $stmtIns = $pdo->prepare('INSERT INTO stock_movements (item_id, movement_type, quantity, reference_note) VALUES (?, ?, ?, ?)');
                $stmtIns->execute([$itemId, $movementType, $qty, $note !== '' ? $note : null]);
                $stmtUpd = $pdo->prepare('UPDATE items SET quantity = ? WHERE id = ?');
                $stmtUpd->execute([$newQty, $itemId]);
                $pdo->commit();
                redirect('/index.php?page=movements&action=list&item_id=' . $itemId);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Save failed: ' . $e->getMessage();
            }
        }
    }
    ?>
    <div class="row row-toolbar">
        <h2>Adjust stock</h2>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=movements&action=list&item_id=' . $itemId)) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
    <p class="muted page-intro"><?= e($item['item_code']) ?> — <?= e($item['item_name']) ?> · Current: <strong><?= e((string)$item['quantity']) ?> <?= e($item['unit']) ?></strong></p>
    <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="form-card">
        <form method="post" class="form">
            <div class="row">
                <label class="col">Type
                    <select name="movement_type">
                        <option value="IN">IN</option>
                        <option value="OUT">OUT</option>
                    </select>
                </label>
                <label class="col">Quantity
                    <input type="number" name="quantity" min="1" required>
                </label>
            </div>
            <label>Note
                <input type="text" name="reference_note" placeholder="Optional reference note">
            </label>
            <div class="form-actions">
                <button type="submit" class="btn ok"><?= icon('check') ?> Save adjustment</button>
                <a class="btn secondary" href="<?= e(base_url('/index.php?page=movements&action=list&item_id=' . $itemId)) ?>">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    return;
}

// list
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$type = $_GET['type'] ?? '';
$where = ['item_id = ?'];
$params = [$itemId];
if ($type === 'IN' || $type === 'OUT') {
    $where[] = 'movement_type = ?';
    $params[] = $type;
}
if ($from !== '') {
    $where[] = 'date >= ?';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = 'date <= ?';
    $params[] = $to . ' 23:59:59';
}
$sql = 'SELECT * FROM stock_movements WHERE ' . implode(' AND ', $where) . ' ORDER BY date DESC, id DESC LIMIT 500';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movs = $stmt->fetchAll();

$totalIn = 0; $totalOut = 0;
foreach ($movs as $m) {
    if ($m['movement_type'] === 'IN') $totalIn += (int)$m['quantity'];
    if ($m['movement_type'] === 'OUT') $totalOut += (int)$m['quantity'];
}
?>
<div class="row row-toolbar">
    <h2>Movement history</h2>
    <div class="actions">
        <?php if (auth_is_admin()): ?>
            <a class="btn ok sm" href="<?= e(base_url('/index.php?page=movements&action=add&item_id=' . $itemId)) ?>"><?= icon('stock') ?> Adjust stock</a>
        <?php endif; ?>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=items')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
</div>
<p class="muted page-intro"><?= e($item['item_code']) ?> — <?= e($item['item_name']) ?></p>
<form class="search-bar filter-bar" method="get" action="<?= e(base_url('/index.php')) ?>">
    <input type="hidden" name="page" value="movements">
    <input type="hidden" name="action" value="list">
    <input type="hidden" name="item_id" value="<?= e((string)$itemId) ?>">
    <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
    <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
    <select name="type">
        <option value="">All</option>
        <option value="IN" <?= $type==='IN'?'selected':'' ?>>IN</option>
        <option value="OUT" <?= $type==='OUT'?'selected':'' ?>>OUT</option>
    </select>
    <button type="submit" class="btn sm secondary"><?= icon('filter') ?> Filter</button>
</form>
<div class="row">
    <div class="badge">Total IN: <?= e((string)$totalIn) ?></div>
    <div class="badge">Total OUT: <?= e((string)$totalOut) ?></div>
    <div class="badge">Current stock: <?= e((string)$item['quantity']) ?> <?= e($item['unit']) ?></div>
</div>
<?php if (!$movs): ?>
    <?= empty_state_html('No movements found', 'Adjust stock or change the date filters to see records.', 'history') ?>
<?php else: ?>
<table class="data-table">
    <thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Note</th></tr></thead>
    <tbody>
    <?php foreach ($movs as $m): ?>
        <tr>
            <td><?= e($m['date']) ?></td>
            <td><?= e($m['movement_type']) ?></td>
            <td><?= e((string)$m['quantity']) ?></td>
            <td><?= e($m['reference_note']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

