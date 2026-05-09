<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Supplier Performance Analytics</h3>
    <a href="index.php?page=procurement" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Procurement</a>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <strong>Supplier Delivery Performance & Contracts</strong>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Supplier ID</th>
                                <th>Company Name</th>
                                <th>Tier Rank</th>
                                <th>Accuracy Score</th>
                                <th>Delivery History</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplierReport as $id => $report): ?>
                                <tr>
                                    <td><?= (int)$id ?></td>
                                    <td><?= htmlspecialchars($report['details']['company_name']) ?></td>
                                    <td><?= (int)$report['details']['tier_rank'] ?></td>
                                    <td><?= number_format($report['accuracyScore'] * 100, 1) ?>%</td>
                                    <td>
                                        <?php if (empty($report['deliveryHistory'])): ?>
                                            No history
                                        <?php else: ?>
                                            <?= count($report['deliveryHistory']) ?> fulfilled orders
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="index.php?page=supplier_analytics&action=recordDecision">
                                            <input type="hidden" name="supplier_id" value="<?= (int)$id ?>">
                                            <button class="btn btn-sm btn-success">Select Preferred Supplier</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
