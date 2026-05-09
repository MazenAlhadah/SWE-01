<?php
require_once __DIR__ . '/../config/Database.php';

class ShippingCarrier {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function setState($state) {
        // Placeholder for PUML alignment
    }

    public function fetchOrderDetails($orderId) {
        if ($orderId) {
            $stmt = $this->conn->prepare(
                "SELECT order_id, shipping_address, urgency
                 FROM CUSTOMER_ORDER
                 WHERE order_id = ?"
            );
            $stmt->execute([$orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }

        return [
            'order_id' => 0,
            'shipping_address' => 'Default Address',
            'urgency' => 'NORMAL'
        ];
    }

    public function fetchAvailableCarriers() {
        $stmt = $this->conn->prepare(
            "SELECT carrier_id, carrier_name, delivery_speed_days, base_cost, coverage_regions
             FROM SHIPPING_CARRIER
             ORDER BY delivery_speed_days ASC, base_cost ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function linkCarrierToOrder($orderId, $carrierId) {
        if (!$orderId) {
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE CUSTOMER_ORDER SET carrier_id = ? WHERE order_id = ?"
        );
        $stmt->execute([$carrierId, $orderId]);
    }
}
