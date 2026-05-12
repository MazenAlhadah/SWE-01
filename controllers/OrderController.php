<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'picker' && $_SESSION['role'] !== 'packer') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/NotificationService.php';
require_once __DIR__ . '/../services/FulfillmentStateMachine.php';

class OrderController {

    public function index() {
        $this->fetchActiveOrders($_SESSION['user_id']);
    }

    public function fetchActiveOrders($staffId) {
        $order = new Order();
        $orders = $order->getOrdersForStaff($staffId);

        $selectedOrderId = (int)($_GET['order_id'] ?? ($_SESSION['active_tracked_order_id'] ?? 0));
        $details = [];
        $stateMachineData = [
            'currentState' => '',
            'nextAllowedState' => ''
        ];

        if ($selectedOrderId) {
            $_SESSION['active_tracked_order_id'] = $selectedOrderId;
            $details = $this->getOrderDetailsData($selectedOrderId);
            $sm = new FulfillmentStateMachine();
            $stateMachineData = $sm->getCurrentState($selectedOrderId);
        }

        $success = $_SESSION['order_tracker_success'] ?? '';
        $error = $_SESSION['order_tracker_error'] ?? '';
        unset($_SESSION['order_tracker_success'], $_SESSION['order_tracker_error']);

        require_once __DIR__ . '/../views/orders/index.php';
    }

    public function getOrderDetails() {
        $orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
        if (!$orderId) {
            $_SESSION['order_tracker_error'] = 'Please select an order to track.';
            header("Location: index.php?page=orders");
            exit();
        }

        header("Location: index.php?page=orders&order_id={$orderId}");
        exit();
    }

    public function requestStateTransition() {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $nextState = trim($_POST['next_state'] ?? '');

        if (!$orderId || $nextState === '') {
            $_SESSION['order_tracker_error'] = 'Order and next state are required.';
            header("Location: index.php?page=orders");
            exit();
        }

        $sm = new FulfillmentStateMachine();
        $currentState = $sm->fetchOrderState($orderId);
        $validation = $sm->validateTransition($currentState, $nextState);

        if (!$validation['valid']) {
            $_SESSION['order_tracker_error'] = $validation['reason'];
            header("Location: index.php?page=orders&order_id={$orderId}");
            exit();
        }

        if (!$this->canRoleTransition($_SESSION['role'] ?? '', $currentState, $nextState)) {
            $_SESSION['order_tracker_error'] = 'Your role cannot perform that transition.';
            header("Location: index.php?page=orders&order_id={$orderId}");
            exit();
        }

        $updated = $sm->updateOrderState($orderId, $nextState);
        if (!$updated) {
            $_SESSION['order_tracker_error'] = 'Order state could not be updated.';
            header("Location: index.php?page=orders&order_id={$orderId}");
            exit();
        }

        $this->notifyNextActor($orderId, $nextState);
        $_SESSION['order_tracker_success'] = "Order {$orderId} moved to {$nextState}.";

        header("Location: index.php?page=orders&order_id={$orderId}");
        exit();
    }

    public function notifyNextActor($orderId, $newState) {
        $ns = NotificationService::getInstance();

        if ($newState === 'PICKING') {
            $ns->update("Order {$orderId} is ready for picker action.");
            return;
        }
        if ($newState === 'PACKING') {
            $ns->update("Order {$orderId} is ready for packer action.");
            return;
        }
        if ($newState === 'SHIPPED') {
            $ns->update("Order {$orderId} has shipped and is in transit.");
            return;
        }
        if ($newState === 'DELIVERED') {
            $ns->update("Order {$orderId} has been delivered.");
        }
    }

    private function getOrderDetailsData($orderId) {
        $order = new Order();
        return $order->getOrderDetails($orderId);
    }

    private function canRoleTransition($role, $currentState, $nextState) {
        if ($role === 'manager') {
            return ($currentState === 'PACKING' && $nextState === 'SHIPPED')
                || ($currentState === 'SHIPPED' && $nextState === 'DELIVERED');
        }

        if ($role === 'picker') {
            return $currentState === 'PROCESSING' && $nextState === 'PICKING';
        }

        if ($role === 'packer') {
            return ($currentState === 'PICKING' && $nextState === 'PACKING')
                || ($currentState === 'PACKING' && $nextState === 'SHIPPED');
        }

        return false;
    }
}
