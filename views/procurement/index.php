<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Procurement Dashboard</h3>
    
    <?php if (isset($_GET['approved'])): ?>
        <div class="alert alert-success">Purchase Order approved and sent to supplier!</div>
    <?php endif; ?>
    <?php if (isset($_GET['decision_recorded'])): ?>
        <div class="alert alert-success">Preferred supplier recorded.</div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-warning">
            <strong>Reorder Alerts</strong>
        </div>
        <div class="card-body">
            <?php if (!empty($affectedSKUs)): ?>
                <p>The following items have dropped below safety stock levels:</p>
                <form action="index.php?page=procurement&action=requestReorderDetails" method="POST">
                    <ul class="list-group mb-3">
                        <?php foreach ($affectedSKUs as $sku): ?>
                            <li class="list-group-item">
                                <input class="form-check-input me-2" type="checkbox" name="skus[]" value="<?= htmlspecialchars($sku) ?>" checked>
                                SKU: <strong><?= htmlspecialchars($sku) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="submit" class="btn btn-primary">Review Reorder Recommendation</button>
                    <!-- Extension point UC-04 -->
                    <a href="index.php?page=supplier_analytics" class="btn btn-secondary">Evaluate Before Order</a>
                </form>
            <?php else: ?>
                <p class="mb-0">All stock levels are optimal. No reorder needed.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <h4>Current Stock Levels</h4>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Available</th>
                <th>Safety Stock</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stockData as $item): ?>
                <?php $isLow = $item['quantity_available'] < $item['safety_stock_qty']; ?>
                <tr class="<?= $isLow ? 'table-danger' : '' ?>">
                    <td><?= htmlspecialchars($item['sku']) ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= (int)$item['quantity_available'] ?></td>
                    <td><?= (int)$item['safety_stock_qty'] ?></td>
                    <td>
                        <?php if ($isLow): ?>
                            <span class="badge bg-danger">Low Stock</span>
                        <?php else: ?>
                            <span class="badge bg-success">Optimal</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
