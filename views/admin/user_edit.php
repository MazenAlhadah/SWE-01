<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="col-12">
    <h2 class="mb-3">Edit User Role</h2>
    <a href="index.php?page=admin&action=users" class="btn btn-sm btn-primary mb-3">&larr; Back to Users</a>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($user): ?>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Role updated successfully.</div>
        <?php endif; ?>

        <!-- User details -->
        <table class="table table-bordered table-sm mb-4" style="max-width:500px">
            <tr><th>ID</th><td><?= (int)$user['user_id'] ?></td></tr>
            <tr><th>Name</th><td><?= htmlspecialchars($user['name']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
            <tr><th>Current Role</th><td><?= htmlspecialchars($user['role']) ?></td></tr>
            <tr>
                <th>Status</th>
                <td>
                    <?php if ($user['is_active'] == 1): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Role assignment panel — UC-08 showRoleAssignmentPanel -->
        <h5>Assign New Role</h5>
        <form method="POST" action="index.php?page=admin&action=updateRole" style="max-width:300px">
            <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
            <div class="mb-3">
                <label for="new_role" class="form-label">New Role</label>
                <select id="new_role" name="new_role" class="form-control" required>
                    <option value="">-- Select Role --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>"
                            <?= ($user['role'] === $r) ? 'selected' : '' ?>>
                            <?= ucfirst($r) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Role</button>
        </form>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
