<?php
$role = $_SESSION['role'] ?? '';
$name = $_SESSION['name'] ?? '';
?>
<?php if ($role === 'manager'): ?>
<!-- Manager: sidebar nav -->
<div class="col-md-2 bg-light min-vh-100 p-0">
    <div class="p-3 border-bottom">
        <strong>WMS</strong><br>
        <small class="text-muted"><?= htmlspecialchars($name) ?></small>
    </div>
    <ul class="list-group list-group-flush">
        <li class="list-group-item">
            <a href="index.php?page=dashboard" class="text-decoration-none">Dashboard</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=storage" class="text-decoration-none">Storage</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=procurement" class="text-decoration-none">Procurement</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=supplier_analytics" class="text-decoration-none">Supplier Analytics</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=orders" class="text-decoration-none">Order Tracker</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=emergency" class="text-decoration-none">Emergency</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=archive" class="text-decoration-none">Archive</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=admin&action=users" class="text-decoration-none">Users</a>
        </li>
        <li class="list-group-item">
            <a href="index.php?page=auth&action=logout" class="text-decoration-none text-danger">Logout</a>
        </li>
    </ul>
</div>
<div class="col-md-10 p-4">

<?php elseif ($role === 'picker'): ?>
<!-- Picker: top navbar -->
<nav class="navbar navbar-expand navbar-light bg-light border-bottom px-3 w-100">
    <span class="navbar-brand">WMS – Picker</span>
    <div class="navbar-nav">
        <a class="nav-link" href="index.php?page=picking">Picking</a>
        <a class="nav-link" href="index.php?page=orders">Order Tracker</a>
        <a class="nav-link text-danger" href="index.php?page=auth&action=logout">Logout</a>
    </div>
</nav>
<div class="col-12 p-4">

<?php elseif ($role === 'packer'): ?>
<!-- Packer: top navbar -->
<nav class="navbar navbar-expand navbar-light bg-light border-bottom px-3 w-100">
    <span class="navbar-brand">WMS – Packer</span>
    <div class="navbar-nav">
        <a class="nav-link" href="index.php?page=packing">Packing Station</a>
        <a class="nav-link" href="index.php?page=orders">Order Tracker</a>
        <a class="nav-link text-danger" href="index.php?page=auth&action=logout">Logout</a>
    </div>
</nav>
<div class="col-12 p-4">

<?php elseif ($role === 'supplier'): ?>
<!-- Supplier: top navbar -->
<nav class="navbar navbar-expand navbar-light bg-light border-bottom px-3 w-100">
    <span class="navbar-brand">WMS – Supplier</span>
    <div class="navbar-nav">
        <a class="nav-link" href="index.php?page=supplier&action=portal">Portal</a>
        <a class="nav-link" href="index.php?page=supplier&action=performance">Performance</a>
        <a class="nav-link text-danger" href="index.php?page=auth&action=logout">Logout</a>
    </div>
</nav>
<div class="col-12 p-4">

<?php else: ?>
<!-- Guest: no nav, full width -->
<div class="col-12">
<?php endif; ?>
