<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Shipment.php';
require_once __DIR__ . '/../models/NotificationService.php';

class BackorderController {

    public function triggerBackorderCheck($shipmentId) {
        $backorderedItems = $this->detectBackorderedItemsInShipment($shipmentId);
        if (empty($backorderedItems)) {
            return [
                'hasBackorders' => false,
                'processed' => 0,
                'items' => []
            ];
        }

        $shipmentModel = new Shipment();
        $backorders = $shipmentModel->fetchOpenBackorders($backorderedItems);
        if (empty($backorders)) {
            return [
                'hasBackorders' => false,
                'processed' => 0,
                'items' => []
            ];
        }

        $prioritizedList = $this->sortByWaitingTime($backorders);
        $processedItems = [];

        foreach ($prioritizedList as $row) {
            $this->allocateStockToCustomer($row['customer_id'], $row['item_id'], $row['quantity_needed']);
            $this->updateBackorderRecord($row['backorder_id'], 'FULFILLED');
            $this->dispatchCustomerNotification($row['customer_id'], $row['item_id']);
            $this->dispatchFloorStaffNotification($row['item_id'], 'Packing Station A');
            $processedItems[] = $row;
        }

        $this->updateInventory($backorderedItems);

        return $this->backordersProcessed([
            'hasBackorders' => true,
            'processed' => count($processedItems),
            'items' => $processedItems
        ]);
    }

    public function detectBackorderedItemsInShipment($shipmentId) {
        $shipmentModel = new Shipment();
        return $shipmentModel->detectBackorderedItemsInShipment($shipmentId);
    }

    public function sortByWaitingTime($backorders) {
        usort($backorders, function($a, $b) {
            return strcmp($a['created_at'], $b['created_at']);
        });
        return $backorders;
    }

    public function allocateStockToCustomer($customerId, $itemId, $qty) {
        $shipmentModel = new Shipment();
        $shipmentModel->allocateStockToCustomer($customerId, $itemId, $qty);
    }

    public function updateBackorderRecord($id, $status) {
        $shipmentModel = new Shipment();
        $shipmentModel->updateBackorderRecord($id, $status);
    }

    public function dispatchCustomerNotification($customerId, $itemId) {
        $ns = NotificationService::getInstance();
        $ns->dispatchCustomerNotification($customerId, $itemId);
    }

    public function dispatchFloorStaffNotification($itemId, $station) {
        $ns = NotificationService::getInstance();
        $ns->dispatchFloorStaffNotification($itemId, $station);
    }

    public function updateInventory($items) {
        $shipmentModel = new Shipment();
        $shipmentModel->updateInventory($items);
    }

    public function backordersProcessed($summary) {
        return $summary;
    }
}
