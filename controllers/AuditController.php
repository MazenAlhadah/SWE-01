<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../services/TierRankingService.php';

class AuditController {

    public function requestPerformanceAudit() {
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findByUserId($_SESSION['user_id']);
        if (!$supplier) {
            $auditReport = [
                'accuracyScore' => 1.0,
                'tierRank' => 3,
                'deliveryHistory' => []
            ];
            require_once __DIR__ . '/../views/supplier/performance.php';
            return;
        }
        $supplierId = $supplier['supplier_id'];
        $deliveryHistory = $supplierModel->fetchDeliveryHistory($supplierId);
        
        $accuracyScore = $this->calculateAccuracyScore($deliveryHistory);
        $this->persistUpdatedAccuracyScore($supplierId, $accuracyScore);
        
        $trs = new TierRankingService();
        $trs->updateSupplierTierRanking($supplierId, $accuracyScore);
        
        // Re-fetch supplier to get updated tier
        $auditReport = [
            'accuracyScore' => $accuracyScore,
            'tierRank' => $this->fetchTierRank($supplierId),
            'deliveryHistory' => $deliveryHistory
        ];

        require_once __DIR__ . '/../views/supplier/performance.php';
    }

    public function calculateAccuracyScore($history) {
        if (empty($history)) return 1.0; 
        $ordered = 0;
        $received = 0;
        foreach ($history as $h) {
            $ordered += $h['quantity_ordered'];
            $received += $h['quantity_received'];
        }
        if ($ordered == 0) return 1.0;
        return round($received / $ordered, 2);
    }

    public function persistUpdatedAccuracyScore($supplierId, $score) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("UPDATE SUPPLIER SET accuracy_score = ? WHERE supplier_id = ?");
        $stmt->execute([$score, $supplierId]);
    }

    private function fetchTierRank($supplierId) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT tier_rank FROM SUPPLIER WHERE supplier_id = ?");
        $stmt->execute([$supplierId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['tier_rank'] : 3;
    }
}
