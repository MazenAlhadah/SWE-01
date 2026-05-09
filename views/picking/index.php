<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Batch Picking</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['picked'])): ?>
        <div class="alert alert-success">Item marked as picked.</div>
    <?php endif; ?>
    <?php if (isset($_GET['next'])): ?>
        <div class="alert alert-success">Moved to the next bin in the route.</div>
    <?php endif; ?>
    <?php if (isset($_GET['done'])): ?>
        <div class="alert alert-success">All items collected. Proceed to packing.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'scan'): ?>
        <div class="alert alert-danger">Please scan or enter a valid item barcode.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'emergency'): ?>
        <div class="alert alert-danger">Picking is paused because emergency mode is active.</div>
    <?php endif; ?>

    <?php
    $route = $_SESSION['active_picklist_route'] ?? ($pickList['route'] ?? []);
    $items = $_SESSION['active_picklist_items'] ?? ($pickList['items'] ?? []);
    $index = isset($_SESSION['active_picklist_index']) ? (int)$_SESSION['active_picklist_index'] : 0;
    $currentBin = !empty($route) && isset($route[$index]) ? $route[$index] : '';
    ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Current Route</strong>
        </div>
        <div class="card-body">
            <?php if (empty($route)): ?>
                <p class="text-muted mb-0">No active pick route.</p>
            <?php else: ?>
                <p><strong>Current Bin:</strong> <?= htmlspecialchars($currentBin) ?></p>
                <p class="mb-0"><strong>Route:</strong> <?= htmlspecialchars(implode(' -> ', $route)) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($items)): ?>
        <div class="row">
            <div class="col-md-7">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Qty</th>
                            <th>Bin</th>
                            <th>Zone</th>
                            <th>Picked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row): ?>
                            <tr class="<?= !empty($row['is_picked']) ? 'table-success' : '' ?>">
                                <td><?= (int)$row['order_id'] ?></td>
                                <td><?= htmlspecialchars($row['sku']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= (int)$row['quantity'] ?></td>
                                <td><?= htmlspecialchars($row['location_code'] ?: 'Unassigned Bin') ?></td>
                                <td><?= htmlspecialchars($row['zone_name'] ?: 'Unknown') ?></td>
                                <td><?= !empty($row['is_picked']) ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>Scan Picked Item</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="index.php?page=picking&action=confirmItemPicked">
                            <div class="mb-3">
                                <label class="form-label">Barcode / SKU</label>
                                <input type="text" name="barcode" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Confirm Item Picked</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Navigation</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="index.php?page=picking&action=nextBinInRoute" class="d-inline">
                            <button type="submit" class="btn btn-primary">Next Bin In Route</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
