<?php
require_once __DIR__ . '/../models/Order.php';

class SortToLight {
    private $order;

    public function __construct() {
        $this->order = new Order();
    }

    public function initSortToLight($orderId) {
        $items = $this->order->fetchOrderItems($orderId);
        return $this->assignItemsToBins($items);
    }

    public function scanItemsIntoBins() {
        return [];
    }

    public function assignItemsToBins($items) {
        $result = [];
        $binNo = 1;

        foreach ($items as $row) {
            $result[] = [
                'order_line_id' => $row['order_line_id'],
                'item_id' => $row['item_id'],
                'sku' => $row['sku'],
                'name' => $row['name'],
                'quantity' => $row['quantity'],
                'source_bin' => $row['location_code'] ?: 'Unassigned Bin',
                'target_bin' => 'PACK-BIN-' . $binNo
            ];
            $binNo++;
        }

        return $result;
    }

    public function confirmPlacement($assignments, $orderLineId, $binId) {
        return $this->validateBinAssignment($assignments, $orderLineId, $binId);
    }

    public function validateBinAssignment($assignments, $orderLineId, $binId) {
        foreach ($assignments as $row) {
            if ((int)$row['order_line_id'] === (int)$orderLineId && $row['target_bin'] === $binId) {
                return true;
            }
        }
        return false;
    }
}
