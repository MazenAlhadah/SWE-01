<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="col-12">
    <h2 class="mb-1">Supplier Performance Analytics</h2>
    <p class="text-muted mb-3">UC-04 — Delivery accuracy, tier ranking &amp; contract versions</p>
    <a href="index.php?page=procurement" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Procurement</a>

    <?php if (isset($_GET['decision_recorded'])): ?>
        <div class="alert alert-success">
             Decision recorded in audit log. Preferred supplier has been set.
        </div>
    <?php endif; ?>

    <?php foreach ($supplierReport as $id => $report): ?>
        <?php
            $s           = $report['details'];
            $score       = $report['accuracyScore'];
            $history     = $report['deliveryHistory'];
            $contracts   = $report['contracts'];
            $scorePct    = number_format($score * 100, 1);
            $scoreClass  = $score >= 0.90 ? 'success' : ($score >= 0.75 ? 'warning' : 'danger');
        ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= htmlspecialchars($s['company_name']) ?></strong>
                    <span class="badge bg-secondary ms-2">Tier <?= (int)$s['tier_rank'] ?></span>
                    <span class="badge bg-<?= $scoreClass ?> ms-1">Accuracy: <?= $scorePct ?>%</span>
                </div>
                <form method="POST" action="index.php?page=supplier_analytics&action=recordDecision" class="d-inline">
                    <input type="hidden" name="supplier_id" value="<?= (int)$id ?>">
                    <button class="btn btn-sm btn-success">⭐ Set as Preferred</button>
                </form>
            </div>
            <div class="card-body">

                <!-- Delivery History -->
                <h6 class="mt-1">Delivery History (Fulfilled POs)</h6>
                <?php if (empty($history)): ?>
                    <p class="text-muted small">No fulfilled orders recorded.</p>
                <?php else: ?>
                    <table class="table table-bordered table-sm mb-3" style="max-width:600px">
                        <thead class="table-light">
                            <tr>
                                <th>PO #</th>
                                <th>Item</th>
                                <th>Ordered</th>
                                <th>Received</th>
                                <th>Match</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($history as $h): ?>
                            <?php $match = ($h['quantity_ordered'] > 0)
                                    ? round($h['quantity_received'] / $h['quantity_ordered'] * 100, 1)
                                    : 100; ?>
                            <tr class="<?= $match < 90 ? 'table-warning' : '' ?>">
                                <td><?= (int)$h['po_id'] ?></td>
                                <td><?= htmlspecialchars($h['item_name']) ?></td>
                                <td><?= (int)$h['quantity_ordered'] ?></td>
                                <td><?= (int)$h['quantity_received'] ?></td>
                                <td><?= $match ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Contract Versions -->
                <h6>Contract Versions</h6>
                <?php if (empty($contracts)): ?>
                    <p class="text-muted small">No contracts on file.</p>
                <?php else: ?>
                    <table class="table table-bordered table-sm" style="max-width:700px">
                        <thead class="table-light">
                            <tr>
                                <th>Contract #</th>
                                <th>Item</th>
                                <th>Ver.</th>
                                <th>Unit Price</th>
                                <th>Discount Qty</th>
                                <th>Discount %</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contracts as $c): ?>
                            <tr>
                                <td><?= (int)$c['contract_id'] ?></td>
                                <td><?= htmlspecialchars($c['item_name']) ?></td>
                                <td><?= (int)$c['version'] ?></td>
                                <td>$<?= number_format($c['unit_price'], 2) ?></td>
                                <td><?= (int)$c['discount_threshold'] ?></td>
                                <td><?= number_format($c['discount_percentage'], 1) ?>%</td>
                                <td><?= htmlspecialchars($c['valid_from'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($c['valid_to'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div><!-- /card-body -->
        </div><!-- /card -->
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
