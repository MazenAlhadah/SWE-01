<?php
/* Raw session check — manager & picker */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'picker') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Backorder.php';
require_once __DIR__ . '/../services/ZonalOptimizer.php';
require_once __DIR__ . '/../services/ExpiryWatchdog.php';
require_once __DIR__ . '/../services/BackorderService.php';

class StorageController {

    /* UC-02: requestZonalData — fetch items + zones, run optimizer, display suggestions */
    public function index() {
        $model     = new Item();
        $optimizer = new ZonalOptimizer();
        $bs        = new BackorderService();

        $items    = $model->fetchItemCatalog();
        $zones    = $model->fetchZoneData();
        $suggestions = $optimizer->runSmartZonalOptimizer($items, $zones);

        $itemsById = [];
        foreach ($items as $item) {
            $itemsById[$item['item_id']] = $item;
        }

        foreach ($suggestions as &$suggestion) {
            $current = $itemsById[$suggestion['item_id']] ?? [];
            $suggestion['current_zone_id'] = $current['current_zone_id'] ?? null;
            $suggestion['current_zone_name'] = $current['current_zone_name'] ?? 'Unassigned';
        }
        unset($suggestion);

        /* opt: check for backorders to cross-dock */
        $incoming_items = $model->fetchIncomingShipmentItems();
        $cross_dock   = [];
        if (!empty($incoming_items)) {
            $cross_dock = $bs->checkAgainstBackorders($incoming_items);
        }

        $success = '';
        $error   = '';

        require_once __DIR__ . '/../views/storage/index.php';
    }

    /* UC-02 alt: manager approves suggestions — commit all suggested assignments */
    public function approve() {
        $suggestions = $_POST['suggestions'] ?? [];
        $model = new Item();

        foreach ($suggestions as $item_id => $zone_id) {
            $model->updateZoneAssignment((int)$item_id, (int)$zone_id);
        }

        /* Log to AUDIT_LOG (append-only) */
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (?, NULL, 'ZONE_ASSIGNMENT', 'Bulk zone assignments approved', 'Manager approved optimizer suggestions', 0, NOW())"
        );
        $stmt->execute([$_SESSION['user_id']]);

        $success = 'Zone assignments updated.';
        $this->index();
    }

    /* UC-02 alt: manager overrides a single item location */
    public function override() {
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $zone_id = isset($_POST['zone_id']) ? (int)$_POST['zone_id'] : 0;

        if (!$item_id || !$zone_id) {
            header("Location: index.php?page=storage");
            exit();
        }

        $model = new Item();
        $model->updateZoneAssignment($item_id, $zone_id);

        header("Location: index.php?page=storage&updated=1");
        exit();
    }

    /* UC-03: runExpiryScan — fetch all items with expiry, filter, apply FEFO */
    public function expiryScan() {
        $model    = new Item();
        $watchdog = new ExpiryWatchdog();
        $conn     = Database::getInstance()->getConnection();

        $all_items     = $model->fetchAllItemsWithExpiry();
        $expiring      = array_values($watchdog->scanExpiryDates($all_items));

        if (!empty($expiring)) {
            /* Apply FEFO order and update picking priority */
            $expiring = $watchdog->applyFEFO($expiring);
            $model->updatePickingPriority($expiring);

            /* Raise expiry alert for each into AUDIT_LOG */
            foreach ($expiring as $item) {
                $watchdog->raiseExpiryAlert($item, $conn);
            }

            /* Simulate dispatch to floor staff via session notification */
            $watchdog->notifyPicker('FEFO instructions sent for ' . count($expiring) . ' item(s).');
        }

        $success = !empty($expiring)
            ? 'FEFO instructions generated and sent to floor staff.'
            : 'No items expiring within 7 days.';

        require_once __DIR__ . '/../views/storage/expiry.php';
    }

    /* UC-15: picker confirms cross-docking delivery to packing station */
    public function crossDockConfirm() {
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $backorder_id = isset($_POST['backorder_id']) ? (int)$_POST['backorder_id'] : 0;
        
        if ($item_id && $backorder_id) {
            $backorder = new Backorder();
            $bs = new BackorderService();
            
            /* updateBackorderRecord -> setBackorderStatus */
            $bs->updateBackorderRecord($backorder_id, 'CROSS_DOCKED');
            
            /* bypassStorageIntake & updateInventory */
            $backorder->markItemForCrossDocking($item_id);
            
            $_SESSION['notifications'][] = "Item {$item_id} cross-docked to packing.";
        }
        
        header("Location: index.php?page=storage&crossdock_confirmed=1");
        exit();
    }
}
