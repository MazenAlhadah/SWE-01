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
        return $rankings;
    }

    public function fetchAllSupplierScores() {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT supplier_id, accuracy_score FROM SUPPLIER");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recomputeTierRankings($scores) {
        usort($scores, function($a, $b) {
            $scoreCompare = (float)$b['accuracy_score'] <=> (float)$a['accuracy_score'];
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return (int)$a['supplier_id'] <=> (int)$b['supplier_id'];
        });

        $rankings = [];
        $currentRank = 0;
        $lastScore = null;

        foreach ($scores as $index => $s) {
            $score = (float)$s['accuracy_score'];
            if ($lastScore === null || $score !== $lastScore) {
                $currentRank = $index + 1;
                $lastScore = $score;
            }

            $rankings[] = [
                'supplier_id' => $s['supplier_id'],
                'tier_rank' => $currentRank
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
