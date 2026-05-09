<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Performance Audit Report</h3>
    <a href="index.php?page=supplier" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Portal</a>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <strong>Your Performance Metrics</strong>
                </div>
                <div class="card-body text-center">
                    <h5 class="card-title">Accuracy Score</h5>
                    <p class="display-4 text-primary"><?= number_format($auditReport['accuracyScore'] * 100, 1) ?>%</p>
                    
                    <h5 class="card-title mt-4">Tier Rank</h5>
                    <p class="display-4 text-success">Tier <?= (int)$auditReport['tierRank'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <strong>Delivery History Summary</strong>
                </div>
                <div class="card-body">
                    <?php if (empty($auditReport['deliveryHistory'])): ?>
                        <p class="text-muted">No delivery history available.</p>
                    <?php else: ?>
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Quantity Ordered</th>
                                    <th>Quantity Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditReport['deliveryHistory'] as $delivery): ?>
                                    <tr>
                                        <td><?= (int)$delivery['quantity_ordered'] ?></td>
                                        <td><?= (int)$delivery['quantity_received'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
