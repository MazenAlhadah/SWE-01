<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="col-12">
    <h2 class="mb-1">Procurement Dashboard</h2>
    <p class="text-muted mb-3">UC-05 — Stock monitoring &amp; PO generation</p>

    <?php
        $modificationRequests = array_values(array_filter(
            $purchaseOrders ?? [],
            static fn ($po) => ($po['status'] ?? '') === 'MODIFICATION_REQUESTED'
        ));
    ?>

    <?php if (isset($_GET['approved'])): ?>
        <?php
            // Verify dispatch: fetch the AUDIT_LOG entry written by sendPOToSupplier()
            $verifyConn = Database::getInstance()->getConnection();
            $verifyStmt = $verifyConn->prepare(
                "SELECT event_detail, timestamp FROM AUDIT_LOG
                 WHERE event_type = 'PO_SENT'
                 ORDER BY timestamp DESC LIMIT 1"
            );
            $verifyStmt->execute();
            $sentLog = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="alert alert-success">
            <strong> Purchase Order approved and sent to supplier.</strong>
            <?php if ($sentLog): ?>
                <br><small class="text-muted">
                    📋 Audit confirmed: <em><?= htmlspecialchars($sentLog['event_detail']) ?></em>
                    at <?= htmlspecialchars($sentLog['timestamp']) ?>
                </small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['notifications'])): ?>
        <?php foreach ($_SESSION['notifications'] as $n): ?>
            <div class="alert alert-info"><?= htmlspecialchars($n) ?></div>
        <?php endforeach; unset($_SESSION['notifications']); ?>
    <?php endif; ?>
    <?php if (!empty($modificationRequests)): ?>
        <?php foreach ($modificationRequests as $po): ?>
            <div class="alert alert-warning">
                Supplier requested modification on PO #<?= (int)$po['po_id'] ?>.
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Purchase Orders</strong>
        </div>
        <div class="card-body">
            <?php if (!empty($purchaseOrders)): ?>
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>PO ID</th>
                            <th>Supplier</th>
                            <th>Generated At</th>
                            <th>Status</th>
                            <th>Shipment</th>
                            <th>Total Cost</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($purchaseOrders as $po): ?>
                        <tr class="<?= $po['status'] === 'MODIFICATION_REQUESTED' ? 'table-warning' : '' ?>">
                            <td><?= (int)$po['po_id'] ?></td>
                            <td><?= htmlspecialchars($po['company_name']) ?></td>
                            <td><?= htmlspecialchars($po['generated_at']) ?></td>
                            <td>
                                <?php if ($po['status'] === 'PENDING'): ?>
                                    <span class="badge bg-secondary">PENDING</span>
                                <?php elseif ($po['status'] === 'CONFIRMED'): ?>
                                    <span class="badge bg-success">CONFIRMED</span>
                                <?php elseif ($po['status'] === 'MODIFICATION_REQUESTED'): ?>
                                    <span class="badge bg-warning text-dark">MODIFICATION_REQUESTED</span>
                                <?php elseif ($po['status'] === 'FULFILLED'): ?>
                                    <span class="badge bg-primary">FULFILLED</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($po['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($po['shipment_id'])): ?>
                                    <div><span class="badge bg-primary"><?= htmlspecialchars($po['shipment_state'] ?: 'EXPECTED') ?></span></div>
                                    <?php if (!empty($po['estimated_arrival'])): ?>
                                        <small class="text-muted">ETA <?= htmlspecialchars($po['estimated_arrival']) ?></small>
                                    <?php endif; ?>
                                <?php elseif ($po['status'] === 'CONFIRMED'): ?>
                                    <span class="text-muted">Awaiting supplier dispatch</span>
                                <?php else: ?>
                                    <span class="text-muted">Not started</span>
                                <?php endif; ?>
                            </td>
                            <td>$<?= number_format($po['total_cost'], 2) ?></td>
                            <td>
                                <a href="index.php?page=procurement&action=reviewPO&id=<?= (int)$po['po_id'] ?>"
                                   class="btn btn-sm btn-outline-primary">View PO</a>
                                <?php if (!empty($po['shipment_id']) && !empty($po['shipment_state']) && $po['shipment_state'] !== 'STORED' && $po['status'] === 'CONFIRMED'): ?>
                                    <form method="POST"
                                          action="index.php?page=procurement&action=advanceShipmentState"
                                          class="d-inline ms-1">
                                        <input type="hidden" name="shipment_id" value="<?= (int)$po['shipment_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <?php if ($po['shipment_state'] === 'EXPECTED'): ?>
                                                Mark AT_DOCK
                                            <?php elseif ($po['shipment_state'] === 'AT_DOCK'): ?>
                                                Start Inspection
                                            <?php else: ?>
                                                Store Goods
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="mb-0 text-muted">No purchase orders found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reorder Alerts -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <strong>⚠ Reorder Alerts</strong>
        </div>
        <div class="card-body">
            <?php if (!empty($affectedSKUs)): ?>
                <p class="mb-2">The following items are below safety stock. Click <strong>Proceed with Reorder</strong> to generate a PO for that item individually.</p>
                <table class="table table-bordered table-sm" style="max-width:750px">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Available</th>
                            <th>Safety Stock</th>
                            <th>Reorder Qty</th>
                            <th>Open PO?</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($affectedSKUs as $row): ?>
                        <tr class="table-danger">
                            <td><?= htmlspecialchars($row['sku']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= (int)$row['quantity_available'] ?></td>
                            <td><?= (int)$row['safety_stock_qty'] ?></td>
                            <td><?= (int)$row['reorder_qty'] ?></td>
                            <td>
                                <?php if ($row['has_pending_po']): ?>
                                    <span class="badge bg-warning text-dark">PO Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['has_pending_po']): ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="A PO has already been generated — awaiting manager approval">
                                        ⏳ Awaiting Approval
                                    </button>
                                <?php else: ?>
                                <form method="POST"
                                      action="index.php?page=procurement&action=requestReorderDetails"
                                      class="d-inline">
                                    <input type="hidden" name="sku"         value="<?= htmlspecialchars($row['sku']) ?>">
                                    <input type="hidden" name="reorder_qty" value="<?= (int)$row['reorder_qty'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Proceed with Reorder
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="index.php?page=supplier_analytics" class="btn btn-sm btn-outline-secondary">
                    Evaluate Suppliers First (UC-04)
                </a>
            <?php else: ?>
                <p class="mb-0 text-success"> All stock levels are optimal. No reorder needed.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current Stock Levels -->
    <h5>Current Stock Levels</h5>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Zone</th>
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
                    <td><?= htmlspecialchars($item['zone_name']) ?></td>
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
