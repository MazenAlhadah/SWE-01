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

require_once __DIR__ . '/../models/PurchaseOrder.php';
require_once __DIR__ . '/../models/Shipment.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../services/LeadTimeEstimator.php';
require_once __DIR__ . '/../services/CarrierSelectionService.php';
require_once __DIR__ . '/../models/NotificationService.php';
require_once __DIR__ . '/BackorderController.php';

class ShipmentController {

    public function fetchConfirmedPO($poId) {
        $poModel = new PurchaseOrder();
        $po = $poModel->getPO($poId);
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findByUserId($_SESSION['user_id']);

        if (!$po || !$supplier || $po['supplier_id'] != $supplier['supplier_id'] || $po['status'] !== 'CONFIRMED') {
            $error = 'Only confirmed purchase orders can be dispatched.';
            $shipment = null;
            $recommendedCarrier = null;
            require_once __DIR__ . '/../views/supplier/shipment.php';
            return;
        }

        $shipment = null;
        $recommendedCarrier = null;
        require_once __DIR__ . '/../views/supplier/shipment.php';
    }

    public function updateShipmentDispatch($poId, $date, $items) {
        $poModel = new PurchaseOrder();
        $po = $poModel->getPO($poId);
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findByUserId($_SESSION['user_id']);

        if (!$po || !$supplier || $po['supplier_id'] != $supplier['supplier_id'] || $po['status'] !== 'CONFIRMED') {
            header("Location: index.php?page=supplier&shipment_error=1");
            exit();
        }

        $lte = new LeadTimeEstimator();
        $estimatedArrivalDate = $lte->estimateArrival($po['supplier_id'], $date);

        $shipmentModel = new Shipment();
        $shipmentId = $shipmentModel->persistShipmentDetails($poId, $date, $estimatedArrivalDate, $items);

        $this->initReceivingStateMachine($shipmentId);

        $ns = NotificationService::getInstance();
        $ns->notifyManagerAsync($estimatedArrivalDate, $shipmentId);

        $recommendedCarrier = null;
        if (!$this->checkCarrierAssignment($shipmentId)) {
            $recommendedCarrier = $this->triggerCarrierSelection($shipmentId);
        }

        $backorderSummary = $this->triggerBackorderCheck($shipmentId);
        $shipment = $shipmentModel->getShipmentById($shipmentId);
        $shipment['backorderSummary'] = $backorderSummary;
        $po = $poModel->getPO($poId);
        $success = 'Shipment dispatch details saved successfully.';

        require_once __DIR__ . '/../views/supplier/shipment.php';
    }

    public function initReceivingStateMachine($shipmentId) {
        $rsm = new ReceivingStateMachine();
        $rsm->initReceivingStateMachine($shipmentId);
    }

    public function persistShipmentDetails($poId, $dDate, $aDate) {
        $shipmentModel = new Shipment();
        return $shipmentModel->persistShipmentDetails($poId, $dDate, $aDate, []);
    }

    public function checkCarrierAssignment($shipmentId) {
        $shipmentModel = new Shipment();
        return $shipmentModel->checkCarrierAssignment($shipmentId);
    }

    public function triggerCarrierSelection($shipmentId) {
        $carrierService = new CarrierSelectionService();
        return $carrierService->initiateCarrierSelection($shipmentId, 0);
    }

    public function triggerBackorderCheck($shipmentId) {
        $bc = new BackorderController();
        return $bc->triggerBackorderCheck($shipmentId);
    }

    public function confirmCarrier() {
        $shipmentId = isset($_POST['shipment_id']) ? (int)$_POST['shipment_id'] : 0;
        $carrierId = isset($_POST['carrier_id']) ? (int)$_POST['carrier_id'] : 0;
        if (!$shipmentId || !$carrierId) {
            header("Location: index.php?page=supplier&shipment_error=1");
            exit();
        }

        $carrierService = new CarrierSelectionService();
        $carrierResult = $carrierService->assignCarrier($shipmentId, $carrierId);

        header("Location: index.php?page=supplier&carrier_assigned=1&shipment_id={$shipmentId}&carrier_id={$carrierId}");
        exit();
    }
}

class ReceivingStateMachine {

    public function initReceivingStateMachine($shipmentId) {
        $shipmentModel = new Shipment();
        $shipmentModel->setShipmentState($shipmentId, 'EXPECTED');
    }
}
