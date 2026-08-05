<?php
require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/lib/utils.php';
require_once dirname(__DIR__) . '/lib/auth.php';

$pdo = get_db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    echo empty_state_html('Item not found', 'This item may have been deleted or the link is invalid.', 'package');
    echo '<p class="form-actions"><a class="btn secondary" href="' . e(base_url('/index.php?page=items')) . '">' . icon('arrow-left') . ' Back to items</a></p>';
    return;
}

$stmtMov = $pdo->prepare('SELECT * FROM stock_movements WHERE item_id = ? ORDER BY date DESC, id DESC LIMIT 100');
$stmtMov->execute([$id]);
$movs = $stmtMov->fetchAll();
$isLow = (int)$item['quantity'] < 10;
?>
<div class="row row-toolbar">
    <h2><?= e($item['item_name']) ?></h2>
    <div class="actions">
        <?php if (auth_is_admin()): ?>
            <a class="btn ok sm" href="<?= e(base_url('/index.php?page=movements&action=add&item_id=' . (int)$item['id'])) ?>"><?= icon('stock') ?> Adjust stock</a>
            <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=items&action=edit&id=' . (int)$item['id'])) ?>"><?= icon('pencil') ?> Edit</a>
        <?php endif; ?>
        <a class="btn secondary sm" href="<?= e(base_url('/index.php?page=items')) ?>"><?= icon('arrow-left') ?> Back</a>
    </div>
</div>

<div class="detail-card">
    <dl class="detail-list">
        <div class="detail-row">
            <dt>Code</dt>
            <dd><?= e($item['item_code']) ?></dd>
        </div>
        <div class="detail-row">
            <dt>Name</dt>
            <dd><?= e($item['item_name']) ?></dd>
        </div>
        <div class="detail-row">
            <dt>Category</dt>
            <dd><?= e($item['category'] ?: '—') ?></dd>
        </div>
        <div class="detail-row">
            <dt>Location</dt>
            <dd><?= e($item['location'] ?: '—') ?></dd>
        </div>
        <div class="detail-row">
            <dt>Quantity</dt>
            <dd>
                <span class="tabular"><?= e((string)$item['quantity']) ?> <?= e($item['unit']) ?></span>
                <?php if ($isLow): ?><span class="badge low badge-compact">LOW</span><?php endif; ?>
            </dd>
        </div>
        <div class="detail-row">
            <dt>Reorder level</dt>
            <dd class="tabular"><?= e((string)$item['reorder_level']) ?></dd>
        </div>
    </dl>
</div>

<h3 class="section-heading">Recent movements</h3>
<?php if (!$movs): ?>
    <?= empty_state_html('No movements yet', 'Stock adjustments for this item will appear here.', 'history') ?>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th class="col-num">Qty</th>
            <th>Note</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($movs as $m): ?>
            <tr>
                <td><?= e($m['date']) ?></td>
                <td><?= e($m['movement_type']) ?></td>
                <td class="col-num tabular"><?= e((string)$m['quantity']) ?></td>
                <td class="col-muted"><?= e($m['reference_note'] ?: '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
