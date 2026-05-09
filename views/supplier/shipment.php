<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Shipment Update</h3>
    <a href="index.php?page=supplier" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Portal</a>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($po)): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <strong>Dispatch Purchase Order #<?= (int)$po['po_id'] ?></strong>
            </div>
            <div class="card-body">
                <p><strong>Supplier:</strong> <?= htmlspecialchars($po['company_name']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($po['status']) ?></p>

                <form method="POST" action="index.php?page=supplier&action=submitDispatchDetails">
                    <input type="hidden" name="po_id" value="<?= (int)$po['po_id'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Dispatch Date</label>
                        <input type="date" name="dispatch_date" class="form-control" required>
                    </div>

                    <h5>Items</h5>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>Name</th>
                                <th>Ordered</th>
                                <th>Dispatch Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($po['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['sku']) ?></td>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= (int)$item['quantity_ordered'] ?></td>
                                    <td>
                                        <input type="number"
                                               name="items[<?= (int)$item['item_id'] ?>]"
                                               class="form-control"
                                               min="0"
                                               max="<?= (int)$item['quantity_ordered'] ?>"
                                               value="<?= (int)$item['quantity_ordered'] ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" class="btn btn-primary">Submit Dispatch Details</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($shipment)): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <strong>Shipment Saved</strong>
            </div>
            <div class="card-body">
                <p><strong>Shipment ID:</strong> <?= (int)$shipment['shipment_id'] ?></p>
                <p><strong>Tracking Number:</strong> <?= htmlspecialchars($shipment['tracking_number']) ?></p>
                <p><strong>Dispatch Date:</strong> <?= htmlspecialchars($shipment['dispatch_date']) ?></p>
                <p><strong>Estimated Arrival:</strong> <?= htmlspecialchars($shipment['estimated_arrival']) ?></p>
                <p><strong>State:</strong> <?= htmlspecialchars($shipment['state']) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($recommendedCarrier)): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <strong>Recommended Carrier</strong>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($recommendedCarrier['name']) ?></p>
                <p><strong>Estimated Delivery:</strong> <?= htmlspecialchars($recommendedCarrier['estimatedDelivery']) ?></p>
                <p><strong>Cost:</strong> $<?= number_format($recommendedCarrier['cost'], 2) ?></p>

                <form method="POST" action="index.php?page=supplier&action=confirmCarrier">
                    <input type="hidden" name="shipment_id" value="<?= (int)$recommendedCarrier['shipmentId'] ?>">
                    <input type="hidden" name="carrier_id" value="<?= (int)$recommendedCarrier['carrierId'] ?>">
                    <button type="submit" class="btn btn-primary">Confirm Carrier</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($shipment['backorderSummary']) && !empty($shipment['backorderSummary']['hasBackorders'])): ?>
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <strong>Backorder Processing Summary</strong>
            </div>
            <div class="card-body">
                <p><strong>Processed:</strong> <?= (int)$shipment['backorderSummary']['processed'] ?> backorder(s)</p>
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Backorder ID</th>
                            <th>Customer ID</th>
                            <th>Item ID</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shipment['backorderSummary']['items'] as $row): ?>
                            <tr>
                                <td><?= (int)$row['backorder_id'] ?></td>
                                <td><?= (int)$row['customer_id'] ?></td>
                                <td><?= (int)$row['item_id'] ?></td>
                                <td><?= (int)$row['quantity_needed'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif (!empty($shipment['backorderSummary']) && empty($shipment['backorderSummary']['hasBackorders'])): ?>
        <div class="alert alert-success">No open backorders were matched to this shipment.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
