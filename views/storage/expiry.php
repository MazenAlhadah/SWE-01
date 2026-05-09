<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="col-12">
    <h2 class="mb-1">Expiry Scan Results (UC-03)</h2>
    <a href="index.php?page=storage" class="btn btn-primary btn-sm mb-3">&larr; Back to Storage</a>

    <?php if (!empty($success)): ?>
        <div class="alert alert-<?= empty($expiring) ? 'success' : 'warning' ?>">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($expiring)): ?>
        <h5>Items Requiring FEFO Picking (sorted by expiry date)</h5>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>SKU</th><th>Name</th><th>Expiry Date</th>
                    <th>Qty Available</th><th>Zone</th><th>Bin</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($expiring as $e): ?>
                <tr class="table-warning">
                    <td><?= htmlspecialchars($e['sku']) ?></td>
                    <td><?= htmlspecialchars($e['name']) ?></td>
                    <td><?= htmlspecialchars($e['expiry_date']) ?></td>
                    <td><?= (int)$e['quantity_available'] ?></td>
                    <td><?= htmlspecialchars($e['zone_name']) ?></td>
                    <td><?= htmlspecialchars($e['location_code']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- UC-03: manager confirms FEFO instructions -->
        <form method="POST" action="index.php?page=storage&action=confirmFEFO">
            <button type="submit" class="btn btn-primary">
                Confirm FEFO Instructions (Dispatch to Floor Staff)
            </button>
        </form>
    <?php else: ?>
        <p class="text-muted">No items expiring within the next 7 days.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
