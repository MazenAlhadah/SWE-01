<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'packer') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../services/SortToLight.php';
require_once __DIR__ . '/../services/PackingMaterial.php';
require_once __DIR__ . '/../services/ParcelValidator.php';
require_once __DIR__ . '/../services/LabelService.php';
require_once __DIR__ . '/../services/FulfillmentStateMachine.php';
require_once __DIR__ . '/../models/NotificationService.php';

class PackingController {

    public function index() {
        $packerId = $this->resolvePackerId();
        if ($packerId === null) {
            $_SESSION['packing_error'] = 'This user is not linked to a PACKER record yet.';
            $packerId = 0;
        }
        $this->requestPackingQueue($packerId);
    }

    public function requestPackingQueue($packerId) {
        $orderModel = new Order();
        $queue = $orderModel->fetchPickedOrders($packerId);

        $activeOrderId = isset($_SESSION['active_packing_order_id']) ? (int)$_SESSION['active_packing_order_id'] : 0;
        $sortGuidance = [];
        $recommendedBoxSize = '';
        $weightCheck = [];
        $labelPreview = [];

        if ($activeOrderId) {
            $sort = new SortToLight();
            $box = new PackingMaterial();
            $validator = new ParcelValidator();

            $sortGuidance = $_SESSION['packing_sort_guidance'] ?? $sort->initSortToLight($activeOrderId);
            $_SESSION['packing_sort_guidance'] = $sortGuidance;
            $recommendedBoxSize = $box->recommendBoxSize($activeOrderId);
            $weightCheck = $validator->initiateWeightCheck($activeOrderId);
            $labelPreview = $_SESSION['packing_label_preview'] ?? [];
        }

        $success = $_SESSION['packing_success'] ?? '';
        $error = $_SESSION['packing_error'] ?? '';
        unset($_SESSION['packing_success'], $_SESSION['packing_error']);

        require_once __DIR__ . '/../views/packing/index.php';
    }

    public function selectOrder() {
        $orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
        if (!$orderId) {
            $_SESSION['packing_error'] = 'Please choose an order from the packing queue.';
            header("Location: index.php?page=packing");
            exit();
        }

        $packerId = $this->resolvePackerId();
        if ($packerId === null) {
            $_SESSION['packing_error'] = 'This user is not linked to a PACKER record yet.';
            header("Location: index.php?page=packing");
            exit();
        }
        $orderModel = new Order();
        $sort = new SortToLight();

        $orderModel->assignPacker($orderId, $packerId);
        $orderModel->setPackingState($orderId, $packerId);

        $_SESSION['active_packing_order_id'] = $orderId;
        $_SESSION['packing_sort_guidance'] = $sort->initSortToLight($orderId);
        $_SESSION['packing_confirmed_bins'] = [];
        $_SESSION['packing_label_preview'] = [];
        $_SESSION['packing_weight_result'] = [];
        $_SESSION['packing_success'] = "Order {$orderId} opened at the packing station.";

        header("Location: index.php?page=packing");
        exit();
    }

    public function placeItemInBin() {
        $orderId = (int)($_SESSION['active_packing_order_id'] ?? 0);
        $orderLineId = (int)($_POST['order_line_id'] ?? 0);
        $binId = trim($_POST['bin_id'] ?? '');

        if (!$orderId || !$orderLineId || $binId === '') {
            $_SESSION['packing_error'] = 'Item placement needs an active order, item, and target bin.';
            header("Location: index.php?page=packing");
            exit();
        }

        $sort = new SortToLight();
        $assignments = $_SESSION['packing_sort_guidance'] ?? $sort->initSortToLight($orderId);
        $valid = $sort->confirmPlacement($assignments, $orderLineId, $binId);

        if (!$valid) {
            $_SESSION['packing_error'] = 'The scanned item does not match that packing bin assignment.';
            header("Location: index.php?page=packing");
            exit();
        }

        $confirmed = $_SESSION['packing_confirmed_bins'] ?? [];
        $confirmed[$orderLineId] = $binId;
        $_SESSION['packing_confirmed_bins'] = $confirmed;
        $_SESSION['packing_success'] = "Order line {$orderLineId} placed in {$binId}.";

        header("Location: index.php?page=packing");
        exit();
    }

    public function confirmPacked() {
        $orderId = (int)($_SESSION['active_packing_order_id'] ?? 0);
        $actualWeight = (float)($_POST['actual_weight'] ?? 0);

        if (!$orderId) {
            $_SESSION['packing_error'] = 'Select an order before confirming packing.';
            header("Location: index.php?page=packing");
            exit();
        }

        $assignments = $_SESSION['packing_sort_guidance'] ?? [];
        $confirmed = $_SESSION['packing_confirmed_bins'] ?? [];
        foreach ($assignments as $row) {
            if (empty($confirmed[$row['order_line_id']])) {
                $_SESSION['packing_error'] = 'All items must be assigned to their sort-to-light bins before weight validation.';
                header("Location: index.php?page=packing");
                exit();
            }
        }

        $validator = new ParcelValidator();
        $result = $validator->submitActualWeight($orderId, $actualWeight);
        $_SESSION['packing_weight_result'] = $result;

        if (!$result['approved']) {
            $_SESSION['packing_error'] = 'Weight validation failed. Recheck contents and resubmit.';
            header("Location: index.php?page=packing");
            exit();
        }

        $label = new LabelService();
        $_SESSION['packing_label_preview'] = $label->generateLabel($orderId);
        $_SESSION['packing_success'] = 'Parcel approved. Shipping label generated and ready to print.';

        header("Location: index.php?page=packing");
        exit();
    }

    public function resubmitAfterRecheck() {
        $orderId = (int)($_SESSION['active_packing_order_id'] ?? 0);
        $actualWeight = (float)($_POST['actual_weight'] ?? 0);

        if (!$orderId) {
            $_SESSION['packing_error'] = 'There is no active order to recheck.';
            header("Location: index.php?page=packing");
            exit();
        }

        $validator = new ParcelValidator();
        $result = $validator->revalidate($orderId, $actualWeight);
        $_SESSION['packing_weight_result'] = $result;

        if (!$result['approved']) {
            $_SESSION['packing_error'] = 'Weight still does not match expected contents.';
            header("Location: index.php?page=packing");
            exit();
        }

        $label = new LabelService();
        $_SESSION['packing_label_preview'] = $label->generateLabel($orderId);
        $_SESSION['packing_success'] = 'Weight recheck passed. Shipping label generated.';

        header("Location: index.php?page=packing");
        exit();
    }

    public function printLabel() {
        $labelId = (int)($_POST['label_id'] ?? 0);
        $label = new LabelService();

        if (!$label->triggerPrint($labelId)) {
            $_SESSION['packing_error'] = 'Label print could not be started.';
            header("Location: index.php?page=packing");
            exit();
        }

        $_SESSION['packing_success'] = 'Label print job sent. Attach and scan the label.';
        header("Location: index.php?page=packing");
        exit();
    }

    public function confirmLabelScanned() {
        $orderId = (int)($_SESSION['active_packing_order_id'] ?? 0);
        $qrCode = trim($_POST['qr_code'] ?? '');

        if (!$orderId || $qrCode === '') {
            $_SESSION['packing_error'] = 'Scan the generated label to finish the order.';
            header("Location: index.php?page=packing");
            exit();
        }

        $label = new LabelService();
        if (!$label->confirmLabelAttached($orderId, $qrCode)) {
            $_SESSION['packing_error'] = $label->getLastError() ?: 'Wrong label, reprint required';
            header("Location: index.php?page=packing");
            exit();
        }

        $sm = new FulfillmentStateMachine();
        $currentState = $sm->fetchOrderState($orderId);
        $validation = $sm->validateTransition($currentState, 'PACKING');

        if (!$validation['valid']) {
            $_SESSION['packing_error'] = $validation['reason'];
            header("Location: index.php?page=packing");
            exit();
        }

        if (!$sm->updateOrderState($orderId, 'PACKING')) {
            $_SESSION['packing_error'] = 'Order could not be moved to PACKING after label confirmation.';
            header("Location: index.php?page=packing");
            exit();
        }

        NotificationService::getInstance()->update("Order {$orderId} packed and labeled successfully.");

        $_SESSION['packing_success'] = "Order {$orderId} packed and labeled successfully.";
        unset(
            $_SESSION['active_packing_order_id'],
            $_SESSION['packing_sort_guidance'],
            $_SESSION['packing_confirmed_bins'],
            $_SESSION['packing_label_preview'],
            $_SESSION['packing_weight_result']
        );

        header("Location: index.php?page=packing");
        exit();
    }

    private function resolvePackerId() {
        $order = new Order();
        return $order->resolvePackerId((int)$_SESSION['user_id']);
    }
}
