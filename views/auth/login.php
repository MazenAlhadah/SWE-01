<?php require_once __DIR__ . '/../../views/layout/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <h2 class="mb-4">Login</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=auth&action=login">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <p class="mt-3 text-center">
            No account? <a href="index.php?page=auth&action=register">Register</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
