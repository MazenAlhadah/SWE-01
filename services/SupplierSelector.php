<?php
class SupplierSelector {
    public function runTieredSupplierSelection($suppliers) {
        if (empty($suppliers)) {
            return null;
        }

        $selectedSupplier = null;
        $bestScore = -INF;

        foreach ($suppliers as $supplier) {
            $score = $this->applySelectionRule(
                isset($supplier['unit_price']) ? (float)$supplier['unit_price'] : 100.0,
                isset($supplier['tier_rank']) ? (int)$supplier['tier_rank'] : 3,
                isset($supplier['accuracy_score']) ? (float)$supplier['accuracy_score'] : 0.0
            );

            if ($selectedSupplier === null || $score > $bestScore) {
                $bestScore = $score;
                $selectedSupplier = $supplier;
            }
        }

        return $selectedSupplier;
    }

    public function applySelectionRule($price, $tierRank, $accuracyScore) {
        $price = $price > 0 ? $price : 1.0;
        $tierRank = max(1, $tierRank);
        $accuracyScore = max(0.0, min(1.0, $accuracyScore));

        $tierWeight = 5 - min($tierRank, 4);
        return ($accuracyScore * 100.0) + ($tierWeight * 10.0) + (1000.0 / $price);
    }
}
