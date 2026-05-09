<?php
require_once __DIR__ . '/../config/Database.php';

class Auth {

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function requireRole($role) {
        if (!self::isLoggedIn()) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== $role) {
            http_response_code(403);
            die("Access denied.");
        }
    }

    public static function currentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            'user_id' => $_SESSION['user_id'],
            'role'    => $_SESSION['role'],
            'name'    => $_SESSION['name'],
        ];
    }
}
