<?php
require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/lib/utils.php';

$pdo = get_db();

// Total items
$total = (int)$pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();

// Low stock items (quantity < 10)
$lowStockStmt = $pdo->query('SELECT id, item_code, item_name, quantity, reorder_level FROM items WHERE quantity < 10 ORDER BY quantity ASC LIMIT 50');
$lowStocks = $lowStockStmt->fetchAll();
?>
<div class="row row-toolbar">
	<h2>Dashboard</h2>
</div>
<div class="row">
	<div class="col">
		<div class="card">
			<h3>Total items</h3>
			<p class="stat-big"><?= e((string)$total) ?></p>
		</div>
	</div>
	<div class="col">
		<div class="card">
			<h3>Low stock (&lt; 10)</h3>
			<?php if (!$lowStocks): ?>
				<?= empty_state_html('All stocked up', 'No items are currently below the low-stock threshold.', 'package') ?>
			<?php else: ?>
				<table class="data-table">
					<thead><tr><th>Code</th><th>Name</th><th>Qty</th><th>Actions</th></tr></thead>
					<tbody>
					<?php foreach ($lowStocks as $row): ?>
						<tr>
							<td><?= e($row['item_code']) ?></td>
							<td><?= e($row['item_name']) ?></td>
							<td><span class="badge low"><?= e((string)$row['quantity']) ?></span></td>
							<td class="actions">
								<div class="action-btns">
									<a class="btn icon-btn ok sm" href="<?= e(base_url('/index.php?page=movements&action=add&item_id=' . (int)$row['id'])) ?>" aria-label="Adjust stock" title="Adjust stock"><?= icon('stock') ?></a>
									<a class="btn icon-btn secondary sm" href="<?= e(base_url('/index.php?page=items&action=edit&id=' . (int)$row['id'])) ?>" aria-label="Edit" title="Edit"><?= icon('pencil') ?></a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>


