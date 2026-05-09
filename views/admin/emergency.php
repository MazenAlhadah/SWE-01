<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="col-12">
    <h2 class="mb-3">Emergency Mode</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Current Status</strong>
        </div>
        <div class="card-body">
            <p><strong>Emergency Active:</strong> <?= $isActive ? 'Yes' : 'No' ?></p>
            <p><strong>Dock Doors Locked:</strong> <?= !empty($_SESSION['dock_doors_locked']) ? 'Yes' : 'No' ?></p>
            <p class="mb-0"><strong>Picking Tasks Paused:</strong> <?= !empty($_SESSION['picklists_paused']) ? 'Yes' : 'No' ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Activate Emergency Mode</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?page=emergency&action=activate">
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control" rows="3" required><?= htmlspecialchars($reason) ?></textarea>
                </div>
                <button type="submit" class="btn btn-danger">Activate Emergency Mode</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <strong>Emergency Log</strong>
        </div>
        <div class="card-body">
            <?php if (empty($events)): ?>
                <p class="text-muted mb-0">No emergency events logged in this session.</p>
            <?php else: ?>
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Manager</th>
                            <th>Reason</th>
                            <th>Timestamp</th>
                            <th>Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$row['user_id']) ?></td>
                                <td><?= htmlspecialchars($row['reason']) ?></td>
                                <td><?= htmlspecialchars($row['timestamp']) ?></td>
                                <td><?= !empty($row['resolved']) ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
