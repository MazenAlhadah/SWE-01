<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid py-3">
    <h3>Order Tracker</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <strong>Active Orders</strong>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <p class="text-muted mb-0">No active orders are available right now.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Urgency</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $row): ?>
                                        <tr class="<?= !empty($details) && (int)$details['order_id'] === (int)$row['order_id'] ? 'table-primary' : '' ?>">
                                            <td>#<?= (int)$row['order_id'] ?></td>
                                            <td><?= htmlspecialchars($row['status']) ?></td>
                                            <td><?= htmlspecialchars($row['urgency']) ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-primary" href="index.php?page=orders&action=getOrderDetails&order_id=<?= (int)$row['order_id'] ?>">Track</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>State Machine</strong>
                </div>
                <div class="card-body">
                    <?php if (empty($details)): ?>
                        <p class="text-muted mb-0">Select an order to view its current and next allowed state.</p>
                    <?php else: ?>
                        <p><strong>Order:</strong> #<?= (int)$details['order_id'] ?></p>
                        <p><strong>Current State:</strong> <?= htmlspecialchars($stateMachineData['currentState']) ?></p>
                        <p><strong>Next Allowed State:</strong> <?= htmlspecialchars($stateMachineData['nextAllowedState'] ?: 'Final State Reached') ?></p>
                        <p class="mb-0"><strong>Flow:</strong> PROCESSING -> PICKING -> PACKING -> SHIPPED -> DELIVERED</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($details)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <strong>Order Details</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Shipping Address:</strong> <?= htmlspecialchars($details['shipping_address']) ?></p>
                        <p><strong>Total Amount:</strong> <?= htmlspecialchars((string)$details['total_amount']) ?></p>
                        <p><strong>Created:</strong> <?= htmlspecialchars($details['created_at']) ?></p>
                        <?php if (!empty($details['shipped_at'])): ?>
                            <p><strong>Shipped At:</strong> <?= htmlspecialchars($details['shipped_at']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($details['delivered_at'])): ?>
                            <p><strong>Delivered At:</strong> <?= htmlspecialchars($details['delivered_at']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <strong>Items</strong>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>SKU</th>
                                        <th>Name</th>
                                        <th>Qty</th>
                                        <th>Bin</th>
                                        <th>Zone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($details['items'] as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['sku']) ?></td>
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= (int)$row['quantity'] ?></td>
                                            <td><?= htmlspecialchars($row['location_code'] ?: 'Unassigned Bin') ?></td>
                                            <td><?= htmlspecialchars($row['zone_name'] ?: 'Unknown') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Transition Order</strong>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stateMachineData['nextAllowedState'])): ?>
                            <p class="text-muted mb-0">This order is already in the final delivered state.</p>
                        <?php else: ?>
                            <form method="POST" action="index.php?page=orders&action=requestStateTransition" class="row g-2 align-items-end">
                                <input type="hidden" name="order_id" value="<?= (int)$details['order_id'] ?>">
                                <div class="col-md-8">
                                    <label class="form-label">Next State</label>
                                    <input type="text" name="next_state" class="form-control" value="<?= htmlspecialchars($stateMachineData['nextAllowedState']) ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">Confirm Transition</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
