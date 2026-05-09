<?php
/* Raw session check — manager only */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'manager') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/RBACService.php';

class UserManagementController {

    /* UC-08: list all users */
    public function index() {
        $model = new User();
        $users = $model->getAllUsers();
        $rbac = new RBACService();
        $roles = $rbac->getAvailableRoles();
        $error = $_SESSION['admin_user_error'] ?? '';
        $success = $_SESSION['admin_user_success'] ?? '';
        unset($_SESSION['admin_user_error'], $_SESSION['admin_user_success']);

        require_once __DIR__ . '/../views/admin/users.php';
    }

    /* UC-08: show single user details + role assignment panel */
    public function edit() {
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if (!$user_id) {
            header("Location: index.php?page=admin&action=users");
            exit();
        }

        $model  = new User();
        $rbac   = new RBACService();
        $user   = $model->findById($user_id);
        $roles  = $rbac->getAvailableRoles();
        $error  = '';
        $success = '';

        if (!$user) {
            $error = "User not found.";
        }

        require_once __DIR__ . '/../views/admin/user_edit.php';
    }

    /* UC-08: POST — update role */
    public function updateRole() {
        $user_id  = isset($_POST['user_id'])  ? (int)$_POST['user_id']  : 0;
        $new_role = $_POST['new_role'] ?? '';

        $rbac    = new RBACService();
        $allowed = $rbac->getAvailableRoles();

        if (!$user_id || !in_array($new_role, $allowed)) {
            header("Location: index.php?page=admin&action=users");
            exit();
        }

        /* applyRolePermissions → updateRoleRecord + logRoleChange */
        $rbac->applyRolePermissions($user_id, $new_role, $_SESSION['user_id']);

        header("Location: index.php?page=admin&action=editUser&user_id={$user_id}&updated=1");
        exit();
    }

    /* UC-08: POST — toggle active status */
    public function toggleActive() {
        $user_id   = isset($_POST['user_id'])   ? (int)$_POST['user_id']   : 0;
        $is_active = isset($_POST['is_active'])  ? (int)$_POST['is_active'] : 0;

        if (!$user_id) {
            header("Location: index.php?page=admin&action=users");
            exit();
        }

        $rbac = new RBACService();
        $rbac->setActive($user_id, $is_active);

        header("Location: index.php?page=admin&action=users");
        exit();
    }

    public function createUser() {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        $rbac = new RBACService();
        $roles = $rbac->getAvailableRoles();

        if ($name === '' || $email === '' || $password === '' || !in_array($role, $roles)) {
            $_SESSION['admin_user_error'] = 'Name, email, password, and a valid role are required.';
            header("Location: index.php?page=admin&action=users");
            exit();
        }

        $model = new User();
        if ($model->emailExists($email)) {
            $_SESSION['admin_user_error'] = 'That email is already registered.';
            header("Location: index.php?page=admin&action=users");
            exit();
        }

        $userId = $model->create($name, $email, $password, $role);
        $_SESSION['admin_user_success'] = "User {$userId} created successfully.";

        header("Location: index.php?page=admin&action=users");
        exit();
    }
}
