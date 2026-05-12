<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php
$activeOrderId = (int)($_SESSION['active_packing_order_id'] ?? 0);
$confirmedBins = $_SESSION['packing_confirmed_bins'] ?? [];
$weightResult = $_SESSION['packing_weight_result'] ?? [];
?>

<div class="container-fluid py-3">
    <h3>Packing Station</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <strong>Packing Queue</strong>
                </div>
                <div class="card-body">
                    <?php if (empty($queue)): ?>
                        <p class="text-muted mb-0">No orders are currently ready for packing.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Units</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queue as $row): ?>
                                        <tr class="<?= $activeOrderId === (int)$row['order_id'] ? 'table-primary' : '' ?>">
                                            <td>#<?= (int)$row['order_id'] ?></td>
                                            <td><?= htmlspecialchars($row['status']) ?></td>
                                            <td><?= (int)$row['total_units'] ?></td>
                                            <td>
                                                <form method="POST" action="index.php?page=packing&action=selectOrder">
                                                    <input type="hidden" name="order_id" value="<?= (int)$row['order_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <?= $activeOrderId === (int)$row['order_id'] ? 'Open' : 'Select' ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Active Order</strong>
                </div>
                <div class="card-body">
                    <?php if (!$activeOrderId): ?>
                        <p class="text-muted mb-0">Select an order to start sort-to-light packing guidance.</p>
                    <?php else: ?>
                        <p><strong>Order:</strong> #<?= $activeOrderId ?></p>
                        <p><strong>Recommended Box:</strong> <?= htmlspecialchars($recommendedBoxSize ?: 'Pending') ?></p>
                        <?php if (!empty($weightCheck)): ?>
                            <p class="mb-0"><strong>Expected Weight:</strong> <?= htmlspecialchars((string)$weightCheck['expected_weight']) ?> kg</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($sortGuidance)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <strong>Sort-to-Light Guidance</strong>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>SKU</th>
                                        <th>Source Bin</th>
                                        <th>Target Bin</th>
                                        <th>Placed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sortGuidance as $row): ?>
                                        <tr class="<?= isset($confirmedBins[$row['order_line_id']]) ? 'table-success' : '' ?>">
                                            <td><?= htmlspecialchars($row['sku']) ?></td>
                                            <td><?= htmlspecialchars($row['source_bin']) ?></td>
                                            <td><?= htmlspecialchars($row['target_bin']) ?></td>
                                            <td><?= isset($confirmedBins[$row['order_line_id']]) ? 'Yes' : 'No' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="index.php?page=packing&action=placeItemInBin" class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Item</label>
                                <select name="order_line_id" class="form-select" required>
                                    <option value="">Choose item</option>
                                    <?php foreach ($sortGuidance as $row): ?>
                                        <option value="<?= (int)$row['order_line_id'] ?>">
                                            <?= htmlspecialchars($row['sku'] . ' - ' . $row['target_bin']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Target Bin</label>
                                <input type="text" name="bin_id" class="form-control" placeholder="PACK-BIN-1" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Confirm Placement</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <strong>Weight Validation</strong>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($weightResult)): ?>
                            <div class="alert <?= !empty($weightResult['approved']) ? 'alert-success' : 'alert-warning' ?>">
                                Actual <?= htmlspecialchars((string)$weightResult['actual_weight']) ?> kg,
                                expected <?= htmlspecialchars((string)$weightResult['expected_weight']) ?> kg,
                                deviation <?= htmlspecialchars((string)$weightResult['deviation']) ?> kg.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="index.php?page=packing&action=confirmPacked" class="row g-2 align-items-end mb-2">
                            <div class="col-md-8">
                                <label class="form-label">Actual Weight (kg)</label>
                                <input type="number" step="0.01" min="0" name="actual_weight" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">Confirm Packed</button>
                            </div>
                        </form>

                        <form method="POST" action="index.php?page=packing&action=resubmitAfterRecheck" class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">Recheck Weight (kg)</label>
                                <input type="number" step="0.01" min="0" name="actual_weight" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-outline-secondary w-100">Resubmit After Recheck</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($labelPreview)): ?>
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Shipping Label</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Carrier:</strong> <?= htmlspecialchars($labelPreview['carrier_name']) ?></p>
                        <p><strong>Ship To:</strong> <?= htmlspecialchars($labelPreview['shipping_address']) ?></p>
                        <p><strong>QR Code:</strong> <code><?= htmlspecialchars($labelPreview['qr_code']) ?></code></p>

                        <form method="POST" action="index.php?page=packing&action=printLabel" class="mb-3">
                            <input type="hidden" name="label_id" value="<?= (int)$labelPreview['label_id'] ?>">
                            <button type="submit" class="btn btn-primary">Print Label</button>
                        </form>

                        <form method="POST" action="index.php?page=packing&action=confirmLabelScanned" class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">Scan Label QR</label>
                                <input type="text" name="qr_code" class="form-control" placeholder="<?= htmlspecialchars($labelPreview['qr_code']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-success w-100">Confirm Label Scanned</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
