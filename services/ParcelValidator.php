<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../config/Database.php';

class ParcelValidator {
    private $order;
    private $conn;
    private $lastResult;

    public function __construct() {
        $this->order = new Order();
        $this->conn = Database::getInstance()->getConnection();
        $this->lastResult = false;
    }

    public function initiateWeightCheck($orderId) {
        return [
            'expected_weight' => $this->order->fetchExpectedWeight($orderId),
            'tolerance' => 0.5
        ];
    }

    public function validateWeight($actual, $expected) {
        $diff = abs((float)$actual - (float)$expected);
        $this->lastResult = $diff <= 0.5;

        return [
            'approved' => $this->lastResult,
            'expected_weight' => (float)$expected,
            'actual_weight' => (float)$actual,
            'deviation' => round($diff, 2),
            'tolerance' => 0.5
        ];
    }

    public function revalidate($orderId, $actual) {
        $data = $this->initiateWeightCheck($orderId);
        $result = $this->validateWeight($actual, $data['expected_weight']);
        $this->logWeightValidation($orderId, $actual, $result['approved'] ? 'PASSED' : 'FAILED', $result['deviation']);
        return $result;
    }

    public function weightOK() {
        return $this->lastResult;
    }

    public function submitActualWeight($orderId, $actual) {
        $data = $this->initiateWeightCheck($orderId);
        $result = $this->validateWeight($actual, $data['expected_weight']);
        $this->logWeightValidation($orderId, $actual, $result['approved'] ? 'PASSED' : 'FAILED', $result['deviation']);
        return $result;
    }

    private function logWeightValidation($orderId, $actual, $status, $deviation) {
        if (!$this->hasTable('AUDIT_LOG')) {
            return;
        }

        $detail = "Order {$orderId} actual weight {$actual} kg";
        if ($deviation > 0) {
            $detail .= " deviation {$deviation} kg";
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (?, NULL, 'WEIGHT_VALIDATION', ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $detail,
            $status,
            $deviation
        ]);
    }

    private function hasTable($table) {
        $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch() !== false;
    }
}
