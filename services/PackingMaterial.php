<?php
require_once __DIR__ . '/../models/Order.php';

class PackingMaterial {
    private $order;

    public function __construct() {
        $this->order = new Order();
    }

    public function suggestBoxSize($items) {
        $total = 0;

        foreach ($items as $row) {
            $height = (int)($row['height_cm'] ?? 0);
            $width = (int)($row['width_cm'] ?? 0);
            $depth = (int)($row['depth_cm'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            $total += ($height * $width * $depth * max($qty, 1));
        }

        if ($total <= 4000) {
            return 'Small Box';
        }
        if ($total <= 12000) {
            return 'Medium Box';
        }
        return 'Large Box';
    }

    public function recommendBoxSize($orderId) {
        $items = $this->order->fetchItemDimensions($orderId);
        return $this->suggestBoxSize($items);
    }
}
