<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Review Purchase Order</h3>
    <a href="index.php?page=procurement" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Dashboard</a>

    <?php if (!$po): ?>
        <div class="alert alert-danger">PO not found.</div>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-header">
                <strong>PO ID:</strong> <?= (int)$po['po_id'] ?> | <strong>Supplier:</strong> <?= htmlspecialchars($po['company_name']) ?>
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

                <div class="d-flex gap-2">
                    <?php if ($po['status'] === 'PENDING'): ?>
                        <form action="index.php?page=procurement&action=approvePO" method="POST">
                            <input type="hidden" name="po_id" value="<?= (int)$po['po_id'] ?>">
                            <button type="submit" class="btn btn-success">Approve PO & Send to Supplier</button>
                        </form>
                    <?php elseif ($po['status'] === 'MODIFICATION_REQUESTED'): ?>
                        <div class="alert alert-warning mb-0 py-2">
                            Supplier requested modification on this PO.
                        </div>
                    <?php endif; ?>

                    <a href="index.php?page=procurement&action=downloadPOPdf&id=<?= (int)$po['po_id'] ?>"
                       class="btn btn-outline-primary">Save PO as PDF</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
