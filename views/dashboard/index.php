<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<!-- UC-06 capacity alert banner — injected by alerts.js on breach -->
<div id="capacity-alert-banner" class="alert alert-warning d-none mx-3 mt-2" role="alert">
    <strong>&#9888; Capacity Alert:</strong> Warehouse occupancy exceeds 90%.
    <span id="capacity-alert-detail"></span>
</div>

<div class="col-12">
    <h2 class="mb-1">Inventory Health Dashboard</h2>
    <p class="text-muted mb-3">Welcome, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></p>

    <!-- Occupancy summary bar -->
    <?php
        $occ_pct_display = ($occ['total'] > 0) ? round($occ['used'] / $occ['total'] * 100, 1) : 0;
        $bar_class = $occ_pct_display > 90 ? 'bg-danger' : ($occ_pct_display > 75 ? 'bg-warning' : 'bg-success');
    ?>
    <div class="mb-4">
        <h5>Warehouse Occupancy</h5>
        <div class="progress" style="height:22px;">
            <div class="progress-bar <?= $bar_class ?>"
                 style="width:<?= $occ_pct_display ?>%">
                <?= $occ_pct_display ?>% used
                (<?= number_format($occ['used'], 2) ?> / <?= number_format($occ['total'], 2) ?> m³)
            </div>
        </div>

        <?php if ($occ_pct > 0.9 && !empty($zone_detail)): ?>
            <h6 class="mt-3">Zone Breakdown</h6>
            <table class="table table-bordered table-sm" style="max-width:600px">
                <thead class="table-light">
                    <tr><th>Zone</th><th>Type</th><th>Used m³</th><th>Total m³</th><th>%</th></tr>
                </thead>
                <tbody>
                <?php foreach ($zone_detail as $z): ?>
                    <tr class="<?= $z['pct'] > 90 ? 'table-danger' : '' ?>">
                        <td><?= htmlspecialchars($z['zone_name']) ?></td>
                        <td><?= htmlspecialchars($z['zone_type']) ?></td>
                        <td><?= number_format($z['current_occupancy_m3'], 2) ?></td>
                        <td><?= number_format($z['total_capacity_m3'], 2) ?></td>
                        <td><?= $z['pct'] ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Environmental alerts -->
    <?php if (!empty($env_alerts)): ?>
    <div class="mb-4">
        <h5>Environmental Alerts</h5>
        <div class="alert alert-warning">
            <?php foreach ($env_alerts as $a): ?>
                <div>Zone <strong><?= htmlspecialchars($a['zone_name']) ?></strong>:
                    Temp <?= $a['temperature'] ?>°C,
                    Humidity <?= $a['humidity'] ?>%
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Expiry warnings -->
    <?php if (!empty($expiring)): ?>
    <div class="mb-4">
        <h5>Items Expiring Within 7 Days</h5>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr><th>SKU</th><th>Name</th><th>Expiry Date</th><th>Qty Available</th><th>Bin</th></tr>
            </thead>
            <tbody>
            <?php foreach ($expiring as $e): ?>
                <tr class="table-warning">
                    <td><?= htmlspecialchars($e['sku']) ?></td>
                    <td><?= htmlspecialchars($e['name']) ?></td>
                    <td><?= htmlspecialchars($e['expiry_date']) ?></td>
                    <td><?= (int)$e['quantity_available'] ?></td>
                    <td><?= htmlspecialchars($e['location_code']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Stock levels -->
    <div class="mb-4">
        <h5>Stock Levels</h5>
        <?php if (empty($stock)): ?>
            <p class="text-muted">No inventory records found.</p>
        <?php else: ?>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>SKU</th><th>Name</th><th>Zone</th><th>Bin</th>
                    <th>Available</th><th>Reserved</th><th>Safety Stock</th><th>State</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stock as $row): ?>
                <?php $low = $row['quantity_available'] < $row['safety_stock_qty']; ?>
                <tr class="<?= $low ? 'table-danger' : '' ?>">
                    <td><?= htmlspecialchars($row['sku']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['zone_name']) ?></td>
                    <td><?= htmlspecialchars($row['location_code']) ?></td>
                    <td><?= (int)$row['quantity_available'] ?></td>
                    <td><?= (int)$row['quantity_reserved'] ?></td>
                    <td><?= (int)$row['safety_stock_qty'] ?></td>
                    <td><?= htmlspecialchars($row['state']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Sensor readings -->
    <div class="mb-4">
        <h5>IoT Sensor Readings</h5>
        <?php if (empty($sensors)): ?>
            <p class="text-muted">No sensor data available.</p>
        <?php else: ?>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr><th>Zone</th><th>Bin</th><th>Type</th><th>Reading</th><th>Expected kg</th><th>Deviation</th><th>Discrepancy</th><th>Timestamp</th></tr>
            </thead>
            <tbody>
            <?php foreach ($sensors as $s): ?>
                <tr class="<?= $s['has_discrepancy'] ? 'table-warning' : '' ?>">
                    <td><?= htmlspecialchars($s['zone_name']) ?></td>
                    <td><?= htmlspecialchars($s['location_code']) ?></td>
                    <td><?= htmlspecialchars($s['type']) ?></td>
                    <td><?= $s['last_reading'] ?></td>
                    <td><?= $s['expected_weight_kg'] ?></td>
                    <td><?= $s['deviation_kg'] ?></td>
                    <td><?= $s['has_discrepancy'] ? '<span class="badge bg-warning text-dark">Yes</span>' : '<span class="badge bg-success">No</span>' ?></td>
                    <td><?= htmlspecialchars($s['timestamp']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- UC-06: load AJAX polling script on manager dashboard only -->
<script src="assets/js/alerts.js"></script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
