<?php
require_once __DIR__ . '/../config/Database.php';

class TierRankingService {
    
    public function updateSupplierTierRanking($supplierId, $score) {
        $allScores = $this->fetchAllSupplierScores();
        
        // Ensure the current supplier's score is updated in the fetched list for recomputation
        $found = false;
        foreach ($allScores as &$s) {
            if ($s['supplier_id'] == $supplierId) {
                $s['accuracy_score'] = $score;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $allScores[] = ['supplier_id' => $supplierId, 'accuracy_score' => $score];
        }

        $rankings = $this->recomputeTierRankings($allScores);
        $this->updateTierRankings($rankings);
    }

    public function fetchAllSupplierScores() {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT supplier_id, accuracy_score FROM SUPPLIER");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recomputeTierRankings($scores) {
        // Trivial simulation: Top 30% are Tier 1, next 40% Tier 2, bottom 30% Tier 3
        // For simplicity, let's just use thresholds:
        // >= 0.90 -> 1
        // >= 0.70 -> 2
        // else -> 3
        
        $rankings = [];
        foreach ($scores as $s) {
            $tier = 3;
            if ($s['accuracy_score'] >= 0.90) {
                $tier = 1;
            } elseif ($s['accuracy_score'] >= 0.70) {
                $tier = 2;
            }
            $rankings[] = [
                'supplier_id' => $s['supplier_id'],
                'tier_rank' => $tier
            ];
        }
        return $rankings;
    }

    public function updateTierRankings($rankings) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("UPDATE SUPPLIER SET tier_rank = ? WHERE supplier_id = ?");
        foreach ($rankings as $r) {
            $stmt->execute([$r['tier_rank'], $r['supplier_id']]);
        }
    }
}
