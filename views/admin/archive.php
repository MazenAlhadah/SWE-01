<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="col-12">
    <h2 class="mb-3">Archive & Retain Data</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Run Archive Job</strong>
        </div>
        <div class="card-body">
            <p class="text-muted">Archives delivered orders older than 12 months and keeps them read-only for retrieval.</p>
            <form method="POST" action="index.php?page=archive&action=run">
                <button type="submit" class="btn btn-primary">Run Archive Job</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Retrieve Archived Order</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?page=archive&action=request">
                <div class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Order ID</label>
                        <input type="number" name="order_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Fetch Archived Order</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($archivedOrder)): ?>
        <?php $data = $archivedOrder['archive_data'] ?? []; ?>
        <div class="card">
            <div class="card-header bg-light">
                <strong>Archived Record</strong>
            </div>
            <div class="card-body">
                <p><strong>Order ID:</strong> <?= htmlspecialchars((string)$archivedOrder['order_id']) ?></p>
                <p><strong>Archived At:</strong> <?= htmlspecialchars($archivedOrder['archived_at'] ?? '') ?></p>
                <?php if (!empty($data) && is_array($data)): ?>
                    <p><strong>Status:</strong> <?= htmlspecialchars($data['status'] ?? '') ?></p>
                    <p><strong>Shipping Address:</strong> <?= htmlspecialchars($data['shipping_address'] ?? '') ?></p>
                    <p><strong>Total Amount:</strong> <?= htmlspecialchars((string)($data['total_amount'] ?? '')) ?></p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($data['items'] ?? []) as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['sku'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars((string)($row['quantity'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['unit_price'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
