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

require_once __DIR__ . '/../services/ArchiveService.php';

class ArchiveController {

    public function index() {
        $service = new ArchiveService();
        $success = $_SESSION['archive_success'] ?? '';
        $error = $_SESSION['archive_error'] ?? '';
        unset($_SESSION['archive_success'], $_SESSION['archive_error']);

        $archivedOrder = [];
        if (!empty($_GET['order_id'])) {
            $archivedOrder = $service->fetchFromArchive((int)$_GET['order_id']);
            if (empty($archivedOrder)) {
                $error = 'Archived order not found.';
            }
        }

        require_once __DIR__ . '/../views/admin/archive.php';
    }

    public function runArchiveJob() {
        $service = new ArchiveService();
        $result = $service->runScheduledArchive();

        if ($result['archivedCount'] > 0) {
            $_SESSION['archive_success'] = "{$result['archivedCount']} orders archived successfully.";
        } else {
            $_SESSION['archive_success'] = 'No eligible orders found for archiving.';
        }

        header("Location: index.php?page=archive");
        exit();
    }

    public function requestArchivedOrder() {
        $orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
        if (!$orderId) {
            $_SESSION['archive_error'] = 'Please enter an archived order ID.';
            header("Location: index.php?page=archive");
            exit();
        }

        header("Location: index.php?page=archive&order_id={$orderId}");
        exit();
    }
}
