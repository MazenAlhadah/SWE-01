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
        $rankings = $trs->updateSupplierTierRanking($supplierId, $accuracyScore);
        
        $auditReport = [
            'accuracyScore' => $accuracyScore,
            'tierRank' => $this->fetchTierRank($supplierId, $rankings),
            'deliveryHistory' => $deliveryHistory
        ];

        require_once __DIR__ . '/../views/supplier/performance.php';
    }

    public function calculateAccuracyScore($history) {
        if (empty($history)) return 1.0; 
        $ordered = 0;
        $received = 0;
        foreach ($history as $h) {
            $ordered += (int)$h['quantity_ordered'];
            $received += (int)$h['quantity_received'];
        }
        if ($ordered == 0) return 1.0;
        return round($received / $ordered, 4);
    }

    public function persistUpdatedAccuracyScore($supplierId, $score) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("UPDATE SUPPLIER SET accuracy_score = ? WHERE supplier_id = ?");
        $stmt->execute([$score, $supplierId]);
    }

    private function fetchTierRank($supplierId, $rankings = null) {
        if (is_array($rankings)) {
            foreach ($rankings as $ranking) {
                if ((int)$ranking['supplier_id'] === (int)$supplierId) {
                    return (int)$ranking['tier_rank'];
                }
            }
        }

        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT tier_rank FROM SUPPLIER WHERE supplier_id = ?");
        $stmt->execute([$supplierId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['tier_rank'] : 1;
    }
}
