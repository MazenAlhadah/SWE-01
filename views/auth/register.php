<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <h2 class="mb-4">Register</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=auth&action=register">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">-- Select Role --</option>
                    <option value="manager">Manager</option>
                    <option value="picker">Picker</option>
                    <option value="packer">Packer</option>
                    <option value="supplier">Supplier</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <p class="mt-3 text-center">
            Already have an account? <a href="index.php?page=auth&action=login">Login</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
