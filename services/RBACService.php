<?php
require_once __DIR__ . '/../config/Database.php';

class RBACService {

    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /* Return the four valid system roles */
    public function getAvailableRoles() {
        return ['manager', 'picker', 'packer', 'supplier'];
    }

    /* Update the role column for a user and log the change */
    public function applyRolePermissions($user_id, $new_role, $manager_id) {
        /* updateRoleRecord */
        $stmt = $this->conn->prepare("UPDATE USER SET role = ? WHERE user_id = ?");
        $stmt->execute([$new_role, $user_id]);

        /* logRoleChange → AUDIT_LOG (append-only, no UPDATE/DELETE) */
        $detail = "Role changed to {$new_role}";
        $stmt2  = $this->conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (?, NULL, 'ROLE_CHANGE', ?, ?, 0, NOW())"
        );
        $stmt2->execute([$manager_id, $detail, "Manager {$manager_id} assigned role to user {$user_id}"]);
    }

    /* Toggle is_active for a user account */
    public function setActive($user_id, $is_active) {
        $stmt = $this->conn->prepare("UPDATE USER SET is_active = ? WHERE user_id = ?");
        $stmt->execute([$is_active, $user_id]);
    }
}
