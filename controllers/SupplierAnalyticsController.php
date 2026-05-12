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

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../services/ContractService.php';

class SupplierAnalyticsController {

    public function index() {
        $this->requestSupplierAnalytics();
    }

    public function requestSupplierAnalytics() {
        $suppliers = $this->fetchAllSuppliers();
        $supplierModel = new Supplier();
        $supplierReport = [];

        foreach ($suppliers as $supplier) {
            $deliveryHistory = $supplierModel->fetchDeliveryHistory($supplier['supplier_id']);
            $accuracyScore = $this->calculateAccuracyScore($deliveryHistory);

            $supplierReport[$supplier['supplier_id']] = [
                'details'         => $supplier,
                'accuracyScore'   => $accuracyScore,
                'deliveryHistory' => $deliveryHistory,
                'contracts'       => []
            ];
        }

        // Group contracts by supplier_id for per-supplier rendering
        $allContracts = $this->fetchSupplierContracts();
        foreach ($allContracts as $c) {
            $sid = $c['supplier_id'];
            if (isset($supplierReport[$sid])) {
                $supplierReport[$sid]['contracts'][] = $c;
            }
        }

        require_once __DIR__ . '/../views/procurement/analytics.php';
    }

    public function fetchAllSuppliers() {
        $supplierModel = new Supplier();
        return $supplierModel->fetchAllSuppliers();
    }

    public function calculateAccuracyScore($history) {
        if (empty($history)) return 1.0;
        $ordered  = 0;
        $received = 0;
        foreach ($history as $h) {
            $ordered  += $h['quantity_ordered'];
            $received += $h['quantity_received'];
        }
        if ($ordered == 0) return 1.0;
        return round($received / $ordered, 2);
    }

    public function fetchSupplierContracts() {
        $contractService = new ContractService();
        return $contractService->fetchSupplierContracts();
    }

    public function recordManagerDecision() {
        $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $managerId  = $_SESSION['user_id'];

        if ($supplierId) {
            $conn = Database::getInstance()->getConnection();

            // Resolve company name for a meaningful audit entry
            $nameStmt = $conn->prepare("SELECT company_name FROM SUPPLIER WHERE supplier_id = ?");
            $nameStmt->execute([$supplierId]);
            $row         = $nameStmt->fetch(PDO::FETCH_ASSOC);
            $companyName = $row ? $row['company_name'] : "Supplier #$supplierId";

            $stmt = $conn->prepare(
                "INSERT INTO AUDIT_LOG
                    (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
                 VALUES (?, NULL, 'SUPPLIER_PREFERRED', ?, 'Manager set preferred supplier after analytics review', 0, NOW())"
            );
            $stmt->execute([$managerId, "Manager marked '$companyName' (ID $supplierId) as preferred supplier"]);
        }

        header("Location: index.php?page=supplier_analytics&decision_recorded=1");
        exit();
    }
}
