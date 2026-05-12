<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/InventoryMonitor.php';
require_once __DIR__ . '/../models/PurchaseOrder.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../services/SupplierSelector.php';
require_once __DIR__ . '/../services/POGenerator.php';
require_once __DIR__ . '/../services/POPdfService.php';

// Ensure StockObserver interface exists
if (!interface_exists('StockObserver')) {
    interface StockObserver {
        public function update($event);
    }
}

class ProcurementController implements StockObserver {

    public function __construct() {
        // Observer Pattern: subscribe to InventoryMonitor
        $monitor = InventoryMonitor::getInstance();
        $monitor->subscribe($this);
    }

    // Observer update method
    public function update($event) {
        if ($event === 'STOCK_BELOW_SAFETY') {
            $_SESSION['notifications'][] = "Alert: Stock below safety threshold detected!";
        }
    }

    public function index() {
        // UC-05: Monitor Stock Levels
        $this->monitorStockLevels();
    }

    public function monitorStockLevels() {
        $inv = new Inventory();
        $stockData = $inv->fetchStockLevels();
        $affectedSKUs = $inv->fetchAffectedSKUs();
        $breached = $this->checkSafetyStockThreshold($stockData);

        if ($breached) {
            // Trigger Observer Notification manually just for demonstration
            $monitor = InventoryMonitor::getInstance();
            if ($monitor->stockBelowSafety()) {
                $monitor->notifyAll();
            }
        }

        require_once __DIR__ . '/../views/procurement/index.php';
    }

    public function checkSafetyStockThreshold($data) {
        foreach ($data as $item) {
            if ($item['quantity_available'] < $item['safety_stock_qty']) {
                return true;
            }
        }
        return false;
    }

    public function requestReorderDetails() {
        // Receives SKU list from POST
        $skus = $_POST['skus'] ?? [];
        if (empty($skus)) {
            header("Location: index.php?page=procurement");
            exit();
        }

        $supplierModel = new Supplier();
        // Fetch all supplier options for these SKUs
        $suppliers = $supplierModel->fetchSupplierOptions($skus);

        // Run SupplierSelector
        $selector = new SupplierSelector();
        $selectedSupplier = $selector->runTieredSupplierSelection($suppliers);

        if ($selectedSupplier) {
            $qtys = [];
            foreach ($skus as $sku) {
                $qtys[] = 100; // Trivial default quantity for reorder
            }

            $prices = $supplierModel->fetchUnitPrices($skus, $selectedSupplier['supplier_id']);
            
            $generator = new POGenerator();
            $poData = $generator->generatePurchaseOrder($selectedSupplier, $skus, $qtys, $prices);
            $poData['managerId'] = $_SESSION['user_id'];
            
            // Persist PO
            $poModel = new PurchaseOrder();
            $poId = $poModel->createPO($poData);
            
            $_SESSION['pending_po_id'] = $poId;
            header("Location: index.php?page=procurement&action=reviewPO&id=" . $poId);
            exit();
        } else {
            $_SESSION['error'] = "No suitable suppliers found for reorder.";
            header("Location: index.php?page=procurement");
            exit();
        }
    }

    public function reviewPO() {
        $poId = $_GET['id'] ?? 0;
        $poModel = new PurchaseOrder();
        $po = $poModel->getPO($poId);

        require_once __DIR__ . '/../views/procurement/po_review.php';
    }

    public function approvePO() {
        if (($_SESSION['role'] ?? '') !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }

        $poId = $_POST['po_id'] ?? 0;
        if ($poId) {
            $poModel = new PurchaseOrder();
            $poModel->approvePO($poId);
            $this->sendPOToSupplier($poId);
        }
        header("Location: index.php?page=procurement&approved=1");
        exit();
    }

    public function downloadPOPdf() {
        $poId = $_GET['id'] ?? $_POST['po_id'] ?? 0;
        if (!$poId) {
            header("Location: index.php?page=procurement");
            exit();
        }

        $poModel = new PurchaseOrder();
        $po = $poModel->getPO($poId);
        if (!$po) {
            header("Location: index.php?page=procurement");
            exit();
        }

        $pdf = new POPdfService();
        $pdf->streamPurchaseOrderPdf($po);
    }

    public function sendPOToSupplier($poId) {
        $poModel = new PurchaseOrder();
        $poModel->logPOSent($poId);
        $_SESSION['notifications'][] = "PO $poId sent to supplier successfully.";
    }

    // --- UC-16 Supplier Actions --- //
    
    public function supplierPortal() {
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findByUserId($_SESSION['user_id']);
        if (!$supplier) {
            $pos = [];
            require_once __DIR__ . '/../views/supplier/portal.php';
            return;
        }
        $supplierId = $supplier['supplier_id'];
        $poModel = new PurchaseOrder();
        $pos = $poModel->getPOsBySupplier($supplierId);

        require_once __DIR__ . '/../views/supplier/portal.php';
    }

    // Aliases for POController methods
    public function fetchPODetails() {
        $poId = $_GET['id'] ?? 0;
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findByUserId($_SESSION['user_id']);
        $poModel = new PurchaseOrder();
        $po = $poModel->getPO($poId);
        if (!$supplier || !$po || $po['supplier_id'] != $supplier['supplier_id']) {
            $po = null;
        }
        require_once __DIR__ . '/../views/supplier/portal.php'; // Or a specific view
    }

    public function submitConfirmation() {
        $poId = $_POST['po_id'] ?? 0;
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findByUserId($_SESSION['user_id']);
        $poModel = new PurchaseOrder();
        $po = $poModel->getPO($poId);
        if ($poId && $supplier && $po && $po['supplier_id'] == $supplier['supplier_id'] && $po['status'] === 'APPROVED') {
            $poModel = new PurchaseOrder();
            $poModel->updatePOStatus($poId, 'CONFIRMED');
            $this->notifyPOConfirmed($poId);
        }
        header("Location: index.php?page=supplier&confirmed=1");
        exit();
    }

    public function submitModificationRequest() {
        $poId = $_POST['po_id'] ?? 0;
        $details = $_POST['details'] ?? '';
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findByUserId($_SESSION['user_id']);
        $poModel = new PurchaseOrder();
        $po = $poModel->getPO($poId);
        if ($poId && $supplier && $po && $po['supplier_id'] == $supplier['supplier_id'] && $po['status'] === 'APPROVED') {
            $poModel = new PurchaseOrder();
            $poModel->updatePOStatus($poId, 'MODIFICATION_REQUESTED');
            $this->notifyManagerModificationRequested($poId, $details);
        }
        header("Location: index.php?page=supplier&modification=1");
        exit();
    }

    public function notifyPOConfirmed($poId) {
        // Called when supplier confirms
        $poModel = new PurchaseOrder();
        $poModel->logConfirmation($poId);
        $_SESSION['notifications'][] = "Supplier confirmed PO $poId";
    }

    public function notifyManagerModificationRequested($poId, $details) {
        $poModel = new PurchaseOrder();
        $poModel->logModificationRequest($poId, $details);
        $_SESSION['notifications'][] = "Supplier requested modification for PO $poId";
    }
}
