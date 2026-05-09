<?php
class POGenerator {
    public function generatePurchaseOrder($supplier, $skus, $qtys, $prices) {
        $totalCost = 0;
        $unitPricesArray = [];
        $itemIdsArray = [];
        
        foreach ($skus as $i => $sku) {
            $price = 0;
            $itemId = 0;
            foreach ($prices as $p) {
                if ($p['sku'] === $sku) {
                    $price = $p['unit_price'] ?? 0;
                    $itemId = $p['item_id'];
                    break;
                }
            }
            $unitPricesArray[] = $price;
            $itemIdsArray[] = $itemId;
            $totalCost += $price * $qtys[$i];
        }

        $poData = $this->buildPO($skus, $qtys, $unitPricesArray, $totalCost);
        $poData['item_ids'] = $itemIdsArray;
        $poData['supplierId'] = $supplier['supplier_id'];
        $poData['contractId'] = $supplier['contract_id'] ?? null;
        
        $signature = $this->applyDigitalSignature();
        $poData['signature'] = $signature;

        return $poData;
    }

    public function buildPO($skus, $qtys, $prices, $total) {
        return [
            'skus' => $skus,
            'quantities' => $qtys,
            'unitPrices' => $prices,
            'totalCost' => $total
        ];
    }

    public function applyDigitalSignature() {
        // Trivial simulation of digital signature
        return "SIG-" . strtoupper(bin2hex(random_bytes(8)));
    }
}
