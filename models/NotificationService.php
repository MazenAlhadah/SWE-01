<?php
/* Singleton — Observer in class_diagram_v3.puml */
class NotificationService {

    private static $instance = null;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new NotificationService();
        }
        return self::$instance;
    }

    /* Observer update hook — called by Subject::notify() */
    public function update($event) {
        /* Simulated: log the event to a session message queue */
        $_SESSION['notifications'][] = $event;
    }

    /* Notify manager about a shipment asynchronously (simulated) */
    public function notifyManagerAsync($date, $shipment_id) {
        $_SESSION['notifications'][] = "Shipment {$shipment_id} expected on {$date}";
    }

    /* Notify customer that a backordered item has been fulfilled */
    public function dispatchCustomerNotification($customer_id, $item_id) {
        $_SESSION['notifications'][] = "Customer {$customer_id}: item {$item_id} now available";
    }

    /* Notify floor staff to collect item at a station */
    public function dispatchFloorStaffNotification($item_id, $station) {
        $_SESSION['notifications'][] = "Floor staff: collect item {$item_id} at station {$station}";
    }
}
