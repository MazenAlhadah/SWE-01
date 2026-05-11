<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="col-12">
    <h2 class="mb-1">Zonal Storage Optimizer</h2>
    <p class="text-muted mb-3">UC-02 — Smart zone assignment suggestions &amp; UC-03 expiry scan</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['approved'])): ?>
        <?php $approvedCount = (int)$_GET['approved']; ?>
        <div class="alert alert-success">
            <?= $approvedCount > 0
                ? "Approved {$approvedCount} zone suggestion(s). Cleared them from the pending list."
                : 'No pending suggestions were approved.' ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Zone assignment updated.</div>
    <?php endif; ?>
    <?php if (isset($_GET['crossdock_confirmed'])): ?>
        <div class="alert alert-success">Item confirmed delivered to packing station.</div>
    <?php endif; ?>

    <!-- UC-03 trigger -->
    <div class="mb-3">
        <a href="index.php?page=storage&action=expiryScan" class="btn btn-danger btn-sm">
            Run Expiry Scan (UC-03)
        </a>
    </div>

    <!-- UC-02/UC-15: Cross-docking alert (opt branch) -->
    <?php if (!empty($cross_dock)): ?>
    <div class="alert alert-warning">
        <strong>Cross-Docking Opportunity:</strong>
        <?= count($cross_dock) ?> incoming item(s) match open backorders and can be cross-docked.
        <ul class="mb-0 mt-1">
            <?php foreach ($cross_dock as $cd): ?>
                <li class="mb-2">SKU <strong><?= htmlspecialchars($cd['sku']) ?></strong>
                    — <?= htmlspecialchars($cd['name']) ?>
                    (backorder qty: <?= (int)$cd['quantity_needed'] ?>)
                    
                    <!-- UC-15 confirm cross-docking -->
                    <form method="POST" action="index.php?page=storage&action=crossDockConfirm" class="d-inline ms-2">
                        <input type="hidden" name="backorder_id" value="<?= (int)$cd['backorder_id'] ?>">
                        <input type="hidden" name="item_id" value="<?= (int)$cd['item_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success">Confirm Delivered to Packing</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- UC-02: Optimizer suggestions table -->
    <h5>Optimizer Suggestions</h5>
    <?php if (empty($suggestions)): ?>
        <div class="alert alert-info mb-3">No pending optimizer suggestions. Current assignments already match the suggested zones.</div>
    <?php else: ?>
        <table class="table table-bordered table-sm mb-2">
            <thead class="table-light">
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Velocity</th>
                    <th>Current Zone</th>
                    <th>Suggested Zone</th>
                    <th>Override Zone</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($suggestions as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['sku']) ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= number_format($s['velocity'], 1) ?></td>
                    <td><?= htmlspecialchars($s['current_zone_name'] ?? 'Unassigned') ?></td>
                    <td><?= htmlspecialchars($s['zone_name']) ?></td>
                    <td>
                        <form method="POST" action="index.php?page=storage&action=override"
                              class="d-inline">
                            <input type="hidden" name="item_id" value="<?= (int)$s['item_id'] ?>">
                            <select name="zone_id" class="form-control form-control-sm d-inline"
                                    style="width:auto">
                                <?php foreach ($zones as $z): ?>
                                    <option value="<?= (int)$z['zone_id'] ?>"
                                        <?= $z['zone_id'] == ($s['current_zone_id'] ?? $s['zone_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($z['zone_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Override</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <form method="POST" action="index.php?page=storage&action=approve">
            <?php foreach ($suggestions as $s): ?>
                <input type="hidden"
                       name="suggestions[<?= (int)$s['item_id'] ?>]"
                       value="<?= (int)$s['zone_id'] ?>">
            <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Approve All Suggestions</button>
        </form>
    <?php endif; ?>

    <!-- Zones summary -->
    <h5 class="mt-4">Warehouse Zones</h5>
    <table class="table table-bordered table-sm" style="max-width:650px">
        <thead class="table-light">
            <tr><th>Zone</th><th>Type</th><th>Used m³</th><th>Total m³</th><th>Temp</th><th>Humidity</th></tr>
        </thead>
        <tbody>
        <?php foreach ($zones as $z): ?>
            <tr>
                <td><?= htmlspecialchars($z['zone_name']) ?></td>
                <td><?= htmlspecialchars($z['zone_type']) ?></td>
                <td><?= number_format($z['current_occupancy_m3'], 2) ?></td>
                <td><?= number_format($z['total_capacity_m3'], 2) ?></td>
                <td><?= $z['temperature'] ?>°C</td>
                <td><?= $z['humidity'] ?>%</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
