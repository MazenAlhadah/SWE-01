<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'picker') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/PickList.php';
require_once __DIR__ . '/../services/BatchPickingSystem.php';

class PickingController {

    public function index() {
        $pickerId = $this->resolvePickerId();
        $this->requestBatchPickList($pickerId);
    }

    public function requestBatchPickList($pickerId) {
        $orderModel = new Order();
        $batch = new BatchPichingSystem();

        $openOrders = $orderModel->getOrdersReadyForPicking();
        $pickList = $batch->requestPickList($openOrders);
        $pickList['current_index'] = 0;

        $pickListModel = new PickList();
        $picklistId = $pickListModel->generatePickList($pickerId, $pickList);

        $_SESSION['active_picklist_id'] = $picklistId;
        $_SESSION['active_picklist_route'] = $pickList['route'];
        $_SESSION['active_picklist_index'] = 0;
        $_SESSION['active_picklist_items'] = $pickList['items'];
        $_SESSION['active_picklist_order_ids'] = $pickList['order_ids'];

        $success = empty($pickList['items']) ? '' : 'Pick list generated successfully.';
        $error = empty($pickList['items']) ? 'No orders are currently ready for picking.' : '';

        require_once __DIR__ . '/../views/picking/index.php';
    }

    public function confirmItemPicked() {
        $barcode = $_POST['barcode'] ?? '';
        $pickerId = $this->resolvePickerId();

        if (empty($barcode)) {
            header("Location: index.php?page=picking&error=scan");
            exit();
        }

        $orderModel = new Order();
        $pickListModel = new PickList();
        $orderModel->markItemPickedInOrder($barcode);

        $picklistId = $_SESSION['active_picklist_id'] ?? 0;
        if ($picklistId) {
            $pickListModel->confirmPick($picklistId, $barcode);
        }

        $items = $_SESSION['active_picklist_items'] ?? [];
        foreach ($items as $i => $row) {
            if ($row['sku'] === $barcode && empty($row['is_picked'])) {
                $items[$i]['is_picked'] = 1;
                break;
            }
        }
        $_SESSION['active_picklist_items'] = $items;

        foreach (($_SESSION['active_picklist_order_ids'] ?? []) as $orderId) {
            $orderModel->setOrderState($orderId, 'PICKING', $pickerId);
        }

        header("Location: index.php?page=picking&picked=1");
        exit();
    }

    public function nextBinInRoute() {
        $route = $_SESSION['active_picklist_route'] ?? [];
        $index = isset($_SESSION['active_picklist_index']) ? (int)$_SESSION['active_picklist_index'] : 0;

        if (!empty($route) && $index < count($route) - 1) {
            $_SESSION['active_picklist_index'] = $index + 1;
            header("Location: index.php?page=picking&next=1");
            exit();
        }

        $picklistId = $_SESSION['active_picklist_id'] ?? 0;
        if ($picklistId) {
            $pickListModel = new PickList();
            $pickListModel->setCompleted($picklistId);
        }

        header("Location: index.php?page=picking&done=1");
        exit();
    }

    private function resolvePickerId() {
        $pickListModel = new PickList();
        return $pickListModel->resolvePickerId($_SESSION['user_id']);
    }
}
