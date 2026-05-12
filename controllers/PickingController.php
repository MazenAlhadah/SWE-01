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
require_once __DIR__ . '/../models/EmergencyMode.php';
require_once __DIR__ . '/../services/BatchPickingSystem.php';

class PickingController {

    public function index() {
        $pickerId = $this->resolvePickerId();
        $activePickList = $this->loadExistingPickList($pickerId);
        $error = '';
        $success = '';

        if (EmergencyMode::getInstance()->isActive()) {
            $error = 'Picking is paused while emergency mode is active.';
            $pickList = $activePickList;
            require_once __DIR__ . '/../views/picking/index.php';
            return;
        }

        if (!empty($activePickList['items'])) {
            $pickList = $activePickList;
            require_once __DIR__ . '/../views/picking/index.php';
            return;
        }

        $this->requestBatchPickList($pickerId);
    }

    public function requestBatchPickList($pickerId) {
        $orderModel = new Order();
        $batch = new BatchPickingSystem();

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
        if (EmergencyMode::getInstance()->isActive()) {
            header("Location: index.php?page=picking&error=emergency");
            exit();
        }

        $barcode = $_POST['barcode'] ?? '';
        $pickerId = $this->resolvePickerId();

        if (empty($barcode)) {
            header("Location: index.php?page=picking&error=scan");
            exit();
        }

        $orderModel = new Order();
        $pickListModel = new PickList();

        $picklistId = $_SESSION['active_picklist_id'] ?? 0;
        $items = $_SESSION['active_picklist_items'] ?? [];
        $matchedItem = [];

        foreach ($items as $row) {
            if ($row['sku'] === $barcode && empty($row['is_picked'])) {
                $matchedItem = $row;
                break;
            }
        }

        if (empty($matchedItem)) {
            header("Location: index.php?page=picking&error=scan");
            exit();
        }

        $orderModel->markOrderLinePicked($matchedItem['order_line_id']);

        if ($picklistId) {
            $pickListModel->confirmPick($picklistId, $barcode);
        }

        foreach ($items as $i => $row) {
            if ((int)$row['order_line_id'] === (int)$matchedItem['order_line_id']) {
                $items[$i]['is_picked'] = 1;
                $items[$i]['picked_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        $_SESSION['active_picklist_items'] = $items;

        if ($this->allItemsPicked($items)) {
            if ($picklistId) {
                $pickListModel->setCompleted($picklistId);
            }

            foreach (($_SESSION['active_picklist_order_ids'] ?? []) as $orderId) {
                $orderModel->setOrderState($orderId, 'PICKING', $pickerId);
            }

            unset(
                $_SESSION['active_picklist_id'],
                $_SESSION['active_picklist_route'],
                $_SESSION['active_picklist_index'],
                $_SESSION['active_picklist_items'],
                $_SESSION['active_picklist_order_ids']
            );

            header("Location: index.php?page=picking&done=1");
            exit();
        }

        header("Location: index.php?page=picking&picked=1");
        exit();
    }

    public function nextBinInRoute() {
        if (EmergencyMode::getInstance()->isActive()) {
            header("Location: index.php?page=picking&error=emergency");
            exit();
        }

        $route = $_SESSION['active_picklist_route'] ?? [];
        $index = isset($_SESSION['active_picklist_index']) ? (int)$_SESSION['active_picklist_index'] : 0;

        if (!empty($route) && $index < count($route) - 1) {
            $_SESSION['active_picklist_index'] = $index + 1;
            header("Location: index.php?page=picking&next=1");
            exit();
        }

        $items = $_SESSION['active_picklist_items'] ?? [];
        if (!$this->allItemsPicked($items)) {
            header("Location: index.php?page=picking&error=incomplete");
            exit();
        }

        header("Location: index.php?page=picking&next=1");
        exit();
    }

    private function resolvePickerId() {
        $pickListModel = new PickList();
        return $pickListModel->resolvePickerId($_SESSION['user_id']);
    }

    private function loadExistingPickList($pickerId) {
        $pickListModel = new PickList();

        if (!empty($_SESSION['active_picklist_id']) && !empty($_SESSION['active_picklist_items'])) {
            return [
                'picklist_id' => $_SESSION['active_picklist_id'],
                'route' => $_SESSION['active_picklist_route'] ?? [],
                'items' => $_SESSION['active_picklist_items'] ?? [],
                'order_ids' => $_SESSION['active_picklist_order_ids'] ?? []
            ];
        }

        $row = $pickListModel->findOpenPickListByPicker($pickerId);
        if (empty($row)) {
            return [];
        }

        $items = $pickListModel->getPickListItems($row['picklist_id']);
        $route = empty($row['optimized_route']) ? [] : explode(' -> ', $row['optimized_route']);
        $orderIds = array_values(array_unique(array_column($items, 'order_id')));

        $_SESSION['active_picklist_id'] = (int)$row['picklist_id'];
        $_SESSION['active_picklist_route'] = $route;
        $_SESSION['active_picklist_index'] = 0;
        $_SESSION['active_picklist_items'] = $items;
        $_SESSION['active_picklist_order_ids'] = $orderIds;

        return [
            'picklist_id' => (int)$row['picklist_id'],
            'route' => $route,
            'items' => $items,
            'order_ids' => $orderIds
        ];
    }

    private function allItemsPicked($items) {
        if (empty($items)) {
            return false;
        }

        foreach ($items as $row) {
            if (empty($row['is_picked'])) {
                return false;
            }
        }

        return true;
    }
}
