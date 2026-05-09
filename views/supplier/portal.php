<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Supplier Portal</h3>

    <?php if (isset($_GET['confirmed'])): ?>
        <div class="alert alert-success">Purchase Order confirmed! Procurement continues.</div>
    <?php endif; ?>
    <?php if (isset($_GET['modification'])): ?>
        <div class="alert alert-info">Modification request sent. Awaiting manager review.</div>
    <?php endif; ?>
    <?php if (isset($_GET['shipment_error'])): ?>
        <div class="alert alert-danger">Shipment update could not be completed.</div>
    <?php endif; ?>
    <?php if (isset($_GET['carrier_assigned'])): ?>
        <div class="alert alert-success">Carrier assigned and label generation initiated.</div>
    <?php endif; ?>

    <?php if (isset($po) && $po): ?>
        <!-- UC-16: PO Details View -->
        <a href="index.php?page=supplier" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Portal</a>
        
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <strong>Purchase Order Details (ID: <?= (int)$po['po_id'] ?>)</strong>
            </div>
            <div class="card-body">
                <p><strong>Status:</strong> <?= htmlspecialchars($po['status']) ?></p>
                <p><strong>Generated At:</strong> <?= htmlspecialchars($po['generated_at']) ?></p>
                <p><strong>Digital Signature:</strong> <span class="font-monospace"><?= htmlspecialchars($po['digital_signature']) ?></span></p>

                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($po['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['sku']) ?></td>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= (int)$item['quantity_ordered'] ?></td>
                                <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                <td>$<?= number_format($item['quantity_ordered'] * $item['unit_price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total Cost:</th>
                            <th>$<?= number_format($po['total_cost'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($po['status'] === 'CONFIRMED'): ?>
                    <p class="text-success mt-2"><strong>You have confirmed this order.</strong></p>
                    <a href="index.php?page=supplier&action=openShipmentUpdate&id=<?= (int)$po['po_id'] ?>"
                       class="btn btn-primary mt-2">Open Shipment Update</a>
                <?php else: ?>
                    <div class="mt-3">
                        <form action="index.php?page=supplier&action=submitConfirmation" method="POST" class="d-inline me-2">
                            <input type="hidden" name="po_id" value="<?= (int)$po['po_id'] ?>">
                            <button type="submit" class="btn btn-success">Confirm PO</button>
                        </form>
                        
                        <form action="index.php?page=supplier&action=submitModificationRequest" method="POST" class="d-inline mt-2">
                            <input type="hidden" name="po_id" value="<?= (int)$po['po_id'] ?>">
                            <div class="input-group" style="max-width: 400px; display: inline-flex;">
                                <input type="text" name="details" class="form-control" placeholder="Modification details..." required>
                                <button type="submit" class="btn btn-warning">Request Modification</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Supplier Dashboard List -->
        <h5>Your Purchase Orders</h5>
        <?php if (!empty($pos)): ?>
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>PO ID</th>
                        <th>Generated At</th>
                        <th>Status</th>
                        <th>Total Cost</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pos as $order): ?>
                        <tr>
                            <td><?= (int)$order['po_id'] ?></td>
                            <td><?= htmlspecialchars($order['generated_at']) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>$<?= number_format($order['total_cost'], 2) ?></td>
                            <td>
                                <a href="index.php?page=supplier&action=fetchPODetails&id=<?= (int)$order['po_id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No purchase orders found.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
