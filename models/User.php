<?php
require_once __DIR__ . '/../config/Database.php';

class User {

    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /* ── PUML-aligned methods ───────────────────────────────── */

    /* login(): verify credentials, return row or false */
    public function login($email, $password) {
        $row = $this->findByEmail($email);
        if ($row && $row['is_active'] == 1 && password_verify($password, $row['password_hash'])) {
            return $row;
        }
        return false;
    }

    /* logout(): destroy caller's session (called via AuthController) */
    public function logout() {
        session_destroy();
    }

    /* resetPassword(): update password_hash for a given user_id */
    public function resetPassword($user_id, $new_password) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE USER SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$hash, $user_id]);
    }

    /* update(): generic field update — used by UC-08 role assignment */
    public function update($user_id, $field, $value) {
        /* Only safe, whitelisted fields are passed from controllers */
        $stmt = $this->conn->prepare("UPDATE USER SET {$field} = ? WHERE user_id = ?");
        $stmt->execute([$value, $user_id]);
    }

    /* ── Query helpers ──────────────────────────────────────── */

    /* Find a single user row by email */
    public function findByEmail($email) {
        $stmt = $this->conn->prepare(
            "SELECT user_id, name, email, password_hash, role, is_active FROM USER WHERE email = ?"
        );
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* Find a single user row by user_id */
    public function findById($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT user_id, name, email, role, is_active, created_at FROM USER WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* Return all users — UC-08 list screen */
    public function getAllUsers() {
        $stmt = $this->conn->prepare(
            "SELECT user_id, name, email, role, is_active, created_at FROM USER ORDER BY created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Insert a new user, return new user_id */
    public function create($name, $email, $password, $role) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare(
            "INSERT INTO USER (name, email, password_hash, role, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$name, $email, $hash, $role]);
        return $this->conn->lastInsertId();
    }

    /* Check whether an email already exists */
    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT user_id FROM USER WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    /* Toggle is_active — UC-08 activate/deactivate */
    public function setActive($user_id, $is_active) {
        $stmt = $this->conn->prepare("UPDATE USER SET is_active = ? WHERE user_id = ?");
        $stmt->execute([$is_active, $user_id]);
    }
}
