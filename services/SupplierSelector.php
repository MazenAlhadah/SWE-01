<?php
class SupplierSelector {
    public function runTieredSupplierSelection($suppliers) {
        if (empty($suppliers)) return null;

        $selectedSupplier = null;
        $bestScore = -1;

        foreach ($suppliers as $supplier) {
            // Trivial simulation of applying rules
            $score = $this->applySelectionRule(
                isset($supplier['unit_price']) ? $supplier['unit_price'] : 100,
                isset($supplier['deliverySpeed']) ? $supplier['deliverySpeed'] : 5
            );
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $selectedSupplier = $supplier;
            }
        }

        return $selectedSupplier;
    }

    public function applySelectionRule($price, $speed) {
        // Trivial: lower price is better, faster speed is better
        // A simple formula just for simulation
        $speed = $speed > 0 ? $speed : 1;
        $price = $price > 0 ? $price : 1;
        return (1000 / $price) + (100 / $speed);
    }
}
