<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'supplier' && $_SESSION['role'] !== 'manager') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Shipment.php';
require_once __DIR__ . '/../models/NotificationService.php';
require_once __DIR__ . '/../services/BackorderService.php';

class BackorderController {

    public function triggerBackorderCheck($shipmentId) {
        $shipmentModel = new Shipment();
        $shipment = $shipmentModel->getShipmentById($shipmentId);
        $state = $shipment['state'] ?? '';

        if ($state !== 'STORED') {
            return [
                'hasBackorders' => false,
                'processed' => 0,
                'items' => [],
                'deferred' => true
            ];
        }

        $backorderedItems = $this->detectBackorderedItemsInShipment($shipmentId);
        if (empty($backorderedItems)) {
            return [
                'hasBackorders' => false,
                'processed' => 0,
                'items' => []
            ];
        }

        $service = new BackorderService();
        $backorders = $service->fetchAndMatchBackorders($backorderedItems);
        if (empty($backorders)) {
            return [
                'hasBackorders' => false,
                'processed' => 0,
                'items' => []
            ];
        }

        $prioritizedList = $this->sortByWaitingTime($backorders);
        $processedItems = [];
        $remainingByItem = [];

        foreach ($backorderedItems as $item) {
            $remainingByItem[(int)$item['item_id']] = (int)$item['quantity_received'];
        }

        foreach ($prioritizedList as $row) {
            $itemId = (int)$row['item_id'];
            $requiredQty = (int)$row['quantity_needed'];
            if (($remainingByItem[$itemId] ?? 0) < $requiredQty) {
                continue;
            }

            $this->allocateStockToCustomer($row['customer_id'], $row['item_id'], $row['quantity_needed']);
            $service->updateBackorderRecord($row['backorder_id'], 'FULFILLED');
            $this->dispatchCustomerNotification($row['customer_id'], $row['item_id']);
            $this->dispatchFloorStaffNotification($row['item_id'], 'Packing Station A');
            $remainingByItem[$itemId] -= $requiredQty;
            $processedItems[] = $row;
        }

        return $this->backordersProcessed([
            'hasBackorders' => !empty($processedItems),
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
        $service = new BackorderService();
        $service->updateBackorderRecord($id, $status);
    }

    public function dispatchCustomerNotification($customerId, $itemId) {
        $ns = NotificationService::getInstance();
        $ns->dispatchCustomerNotification($customerId, $itemId);
    }

    public function dispatchFloorStaffNotification($itemId, $station) {
        $ns = NotificationService::getInstance();
        $ns->dispatchFloorStaffNotification($itemId, $station);
    }

    public function backordersProcessed($summary) {
        return $summary;
    }
}
