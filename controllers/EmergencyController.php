<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'manager') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/EmergencyMode.php';

class EmergencyController {

    public function index() {
        $mode = EmergencyMode::getInstance();
        $success = $_SESSION['emergency_success'] ?? '';
        $error = $_SESSION['emergency_error'] ?? '';
        unset($_SESSION['emergency_success'], $_SESSION['emergency_error']);

        $isActive = $mode->isActive();
        $reason = $mode->getReason();
        $events = $_SESSION['emergency_events'] ?? [];

        require_once __DIR__ . '/../views/admin/emergency.php';
    }

    public function activateEmergencyMode() {
        $managerId = $_SESSION['user_id'] ?? 0;
        $reason = trim($_POST['reason'] ?? '');

        if (!$this->verifyManagerAuth($managerId)) {
            $_SESSION['emergency_error'] = 'Insufficient Permissions';
            header("Location: index.php?page=emergency");
            exit();
        }

        if ($reason === '') {
            $_SESSION['emergency_error'] = 'Emergency reason is required.';
            header("Location: index.php?page=emergency");
            exit();
        }

        $mode = EmergencyMode::getInstance();
        $mode->activateEmergencyMode($reason);

        $_SESSION['emergency_success'] = 'Emergency mode activated successfully.';
        header("Location: index.php?page=emergency");
        exit();
    }

    public function verifyManagerAuth($managerId) {
        return !empty($managerId) && ($_SESSION['role'] ?? '') === 'manager';
    }
}
