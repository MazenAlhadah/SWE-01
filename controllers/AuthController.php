<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/User.php';

class AuthController {

    /* GET → show form; POST → process */
    public function login() {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = "Email and password are required.";
            } else {
                $model = new User();
                $row   = $model->login($email, $password);

                if ($row) {
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['role']    = $row['role'];
                    $_SESSION['name']    = $row['name'];
                    header("Location: " . $this->getPostLoginUrl($row['role']));
                    exit();
                } else {
                    $error = "Invalid credentials or account inactive.";
                }
            }
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function register() {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name     = trim($_POST['name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role     = $_POST['role'] ?? '';

            $allowed = ['manager', 'picker', 'packer', 'supplier'];

            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                $error = "All fields are required.";
            } elseif (!in_array($role, $allowed)) {
                $error = "Invalid role selected.";
            } else {
                $model = new User();
                if ($model->emailExists($email)) {
                    $error = "Email already registered.";
                } else {
                    $model->create($name, $email, $password, $role);
                    header("Location: index.php?page=auth&action=login");
                    exit();
                }
            }
        }

        require_once __DIR__ . '/../views/auth/register.php';
    }

    public function logout() {
        $model = new User();
        $model->logout();
        header("Location: index.php?page=auth&action=login");
        exit();
    }

    private function getPostLoginUrl($role) {
        if ($role === 'manager') {
            return 'index.php?page=dashboard';
        }
        if ($role === 'picker') {
            return 'index.php?page=picking';
        }
        if ($role === 'supplier') {
            return 'index.php?page=supplier';
        }
        if ($role === 'packer') {
            return 'index.php?page=packing';
        }
        return 'index.php?page=auth&action=login';
    }
}
