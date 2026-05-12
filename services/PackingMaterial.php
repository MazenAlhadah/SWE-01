<?php
require_once __DIR__ . '/../models/Order.php';

class PackingMaterial {
    private $order;

    public function __construct() {
        $this->order = new Order();
    }

    public function suggestBoxSize($items) {
        $total = 0;
        $standardBoxes = [
            ['name' => 'Small Box', 'capacity' => 4000],
            ['name' => 'Medium Box', 'capacity' => 12000],
            ['name' => 'Large Box', 'capacity' => 24000],
        ];

        foreach ($items as $row) {
            $height = (int)($row['height_cm'] ?? 0);
            $width = (int)($row['width_cm'] ?? 0);
            $depth = (int)($row['depth_cm'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            $total += ($height * $width * $depth * max($qty, 1));
        }

        foreach ($standardBoxes as $box) {
            if ($total <= $box['capacity']) {
                return $box['name'];
            }
        }

        return 'Oversized Parcel';
    }

    public function recommendBoxSize($orderId) {
        $items = $this->order->fetchItemDimensions($orderId);
        return $this->suggestBoxSize($items);
    }
}
