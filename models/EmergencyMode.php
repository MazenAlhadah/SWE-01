<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/NotificationService.php';

class EmergencyMode {
    private static $instance = null;
    private $conn;
    private $observers;

    private function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->observers = [];
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new EmergencyMode();
        }
        return self::$instance;
    }

    public function activateEmergencyMode($reason) {
        $_SESSION['emergency_mode_active'] = 1;
        $_SESSION['emergency_mode_reason'] = $reason;

        $this->pauseAllPickLists();
        $this->lockDockDoors();
        $this->persistEvent($reason);
        $this->broadcastAlert('allActiveUsers');
        $this->notify();
    }

    public function pauseAllPickLists() {
        if ($this->hasTable('PICK_LIST')) {
            $stmt = $this->conn->prepare(
                "UPDATE PICK_LIST SET status = 'PAUSED'"
            );
            $stmt->execute();
        }

        $_SESSION['picklists_paused'] = 1;
    }

    public function lockDockDoors() {
        $_SESSION['dock_doors_locked'] = 1;
    }

    public function broadcastAlert($devices) {
        $ns = NotificationService::getInstance();
        $reason = $_SESSION['emergency_mode_reason'] ?? 'Emergency activated';
        $ns->update("Emergency alert sent to {$devices}: {$reason}");
    }

    public function attach($o) {
        $this->observers[] = $o;
    }

    public function detach($o) {
        foreach ($this->observers as $i => $observer) {
            if ($observer === $o) {
                unset($this->observers[$i]);
            }
        }
    }

    public function notify() {
        foreach ($this->observers as $observer) {
            if (is_object($observer) && method_exists($observer, 'update')) {
                $observer->update('Emergency mode activated');
            }
        }
    }

    public function isActive() {
        return !empty($_SESSION['emergency_mode_active']);
    }

    public function getReason() {
        return $_SESSION['emergency_mode_reason'] ?? '';
    }

    private function persistEvent($reason) {
        if ($this->hasTable('EMERGENCY_EVENT')) {
            $stmt = $this->conn->prepare(
                "INSERT INTO EMERGENCY_EVENT (user_id, reason, timestamp, resolved)
                 VALUES (?, ?, NOW(), 0)"
            );
            $stmt->execute([$_SESSION['user_id'] ?? null, $reason]);
            return;
        }

        $_SESSION['emergency_events'][] = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'reason' => $reason,
            'timestamp' => date('Y-m-d H:i:s'),
            'resolved' => 0
        ];
    }

    private function hasTable($table) {
        $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch() !== false;
    }
}
