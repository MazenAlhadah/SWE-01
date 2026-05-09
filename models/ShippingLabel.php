<?php
require_once __DIR__ . '/../config/Database.php';

class ShippingLabel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function fetchByOrderId($orderId) {
        if (!$this->hasTable('SHIPPING_LABEL')) {
            return $_SESSION['shipping_labels'][$orderId] ?? [];
        }

        $stmt = $this->conn->prepare(
            "SELECT label_id, order_id, carrier_id, qr_code, generated_at, scanned_at
             FROM SHIPPING_LABEL
             WHERE order_id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : [];
    }

    public function storeLabel($orderId, $carrierId, $qrCode) {
        if (!$this->hasTable('SHIPPING_LABEL')) {
            $_SESSION['shipping_labels'][$orderId] = [
                'label_id' => $orderId,
                'order_id' => $orderId,
                'carrier_id' => $carrierId,
                'qr_code' => $qrCode,
                'generated_at' => date('Y-m-d H:i:s'),
                'scanned_at' => null
            ];
            return $orderId;
        }

        $existing = $this->fetchByOrderId($orderId);
        if (!empty($existing)) {
            $stmt = $this->conn->prepare(
                "UPDATE SHIPPING_LABEL
                 SET carrier_id = ?, qr_code = ?, generated_at = NOW(), scanned_at = NULL
                 WHERE order_id = ?"
            );
            $stmt->execute([$carrierId, $qrCode, $orderId]);
            return (int)$existing['label_id'];
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO SHIPPING_LABEL (order_id, carrier_id, qr_code, generated_at, scanned_at)
             VALUES (?, ?, ?, NOW(), NULL)"
        );
        $stmt->execute([$orderId, $carrierId, $qrCode]);
        return (int)$this->conn->lastInsertId();
    }

    public function confirmScanned($orderId, $qrCode) {
        if (!$this->hasTable('SHIPPING_LABEL')) {
            if (empty($_SESSION['shipping_labels'][$orderId])) {
                return false;
            }
            if ($_SESSION['shipping_labels'][$orderId]['qr_code'] !== $qrCode) {
                return false;
            }
            $_SESSION['shipping_labels'][$orderId]['scanned_at'] = date('Y-m-d H:i:s');
            return true;
        }

        $stmt = $this->conn->prepare(
            "UPDATE SHIPPING_LABEL
             SET scanned_at = NOW()
             WHERE order_id = ? AND qr_code = ?"
        );
        $stmt->execute([$orderId, $qrCode]);
        return $stmt->rowCount() > 0;
    }

    private function hasTable($table) {
        $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch() !== false;
    }
}
