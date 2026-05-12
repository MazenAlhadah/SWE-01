<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Auth.php';

$page   = $_GET['page']   ?? 'auth';
$action = $_GET['action'] ?? 'login';

/* Route table — add new controllers here each shot */
switch ($page) {

    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $ctrl = new AuthController();
        if ($action === 'register') {
            $ctrl->register();
        } elseif ($action === 'logout') {
            $ctrl->logout();
        } else {
            $ctrl->login();
        }
        break;

    /* UC-01 — Inventory Health Dashboard (Shot 3) */
    case 'dashboard':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/InventoryController.php';
        $ctrl = new InventoryController();
        $ctrl->requestDashboardData();
        break;

    /* UC-06 — Capacity Alert JSON endpoint (Shot 3) */
    case 'alerts':
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => [], 'error' => 'Unauthenticated']);
            exit();
        }
        if ($_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/AlertController.php';
        $ctrl = new AlertController();
        $ctrl->getCapacityStatus();
        break;

    /* UC-02 / UC-03 — Zonal Storage + Expiry Watchdog (Shot 4) + UC-15 Cross-Docking (Shot 5) */
    case 'storage':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'picker') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/StorageController.php';
        $ctrl = new StorageController();
        if ($action === 'approve') {
            $ctrl->approve();
        } elseif ($action === 'override') {
            $ctrl->override();
        } elseif ($action === 'expiryScan') {
            $ctrl->expiryScan();
        } elseif ($action === 'confirmFEFO') {
            /* confirmFEFO re-dispatches to expiry view — simulate: redirect back */
            header("Location: index.php?page=storage&action=expiryScan&confirmed=1");
            exit();
        } elseif ($action === 'crossDockConfirm') {
            $ctrl->crossDockConfirm();
        } else {
            $ctrl->index();
        }
        break;

    /* UC-05 — Procurement (Shot 6) */
    case 'procurement':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/ProcurementController.php';
        $ctrl = new ProcurementController();
        if ($action === 'requestReorderDetails') {
            $ctrl->requestReorderDetails();
        } elseif ($action === 'reviewPO') {
            $ctrl->reviewPO();
        } elseif ($action === 'downloadPOPdf') {
            $ctrl->downloadPOPdf();
        } elseif ($action === 'approvePO') {
            $ctrl->approvePO();
        } elseif ($action === 'advanceShipmentState') {
            $ctrl->advanceShipmentState();
        } else {
            $ctrl->index();
        }
        break;

    /* UC-16 — Supplier Portal (Shot 6) */
    case 'supplier':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'supplier') {
            http_response_code(403);
            die("Access denied.");
        }
        if ($action === 'openShipmentUpdate' || $action === 'submitDispatchDetails' || $action === 'confirmCarrier') {
            require_once __DIR__ . '/controllers/ShipmentController.php';
            $shipment = new ShipmentController();
            if ($action === 'openShipmentUpdate') {
                $shipment->fetchConfirmedPO($_GET['id'] ?? 0);
            } elseif ($action === 'submitDispatchDetails') {
                $shipment->updateShipmentDispatch(
                    $_POST['po_id'] ?? 0,
                    $_POST['dispatch_date'] ?? '',
                    $_POST['items'] ?? []
                );
            } else {
                $shipment->confirmCarrier();
            }
        } elseif ($action === 'performance') {
            require_once __DIR__ . '/controllers/AuditController.php';
            $audit = new AuditController();
            $audit->requestPerformanceAudit();
        } else {
            require_once __DIR__ . '/controllers/ProcurementController.php';
            $ctrl = new ProcurementController();
            if ($action === 'fetchPODetails') {
                $ctrl->fetchPODetails();
            } elseif ($action === 'submitConfirmation') {
                $ctrl->submitConfirmation();
            } elseif ($action === 'submitModificationRequest') {
                $ctrl->submitModificationRequest();
            } else {
                $ctrl->supplierPortal();
            }
        }
        break;

    /* UC-04 — Supplier analytics (Shot 7) */
    case 'supplier_analytics':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/SupplierAnalyticsController.php';
        $ctrl = new SupplierAnalyticsController();
        if ($action === 'recordDecision') {
            $ctrl->recordManagerDecision();
        } else {
            $ctrl->index();
        }
        break;

    /* UC-10 — Batch Picking (Shot 9) */
    case 'picking':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'picker') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/PickingController.php';
        $ctrl = new PickingController();
        if ($action === 'confirmItemPicked') {
            $ctrl->confirmItemPicked();
        } elseif ($action === 'nextBinInRoute') {
            $ctrl->nextBinInRoute();
        } else {
            $ctrl->index();
        }
        break;

    /* UC-11 / UC-12 / UC-13 — Packing Station (Shot 10) */
    case 'packing':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'packer') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/PackingController.php';
        $ctrl = new PackingController();
        if ($action === 'selectOrder') {
            $ctrl->selectOrder();
        } elseif ($action === 'placeItemInBin') {
            $ctrl->placeItemInBin();
        } elseif ($action === 'confirmPacked') {
            $ctrl->confirmPacked();
        } elseif ($action === 'resubmitAfterRecheck') {
            $ctrl->resubmitAfterRecheck();
        } elseif ($action === 'printLabel') {
            $ctrl->printLabel();
        } elseif ($action === 'confirmLabelScanned') {
            $ctrl->confirmLabelScanned();
        } else {
            $ctrl->index();
        }
        break;

    /* UC-14 — Order Fulfillment State Machine (Shot 11) */
    case 'orders':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'picker' && $_SESSION['role'] !== 'packer') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/OrderController.php';
        $ctrl = new OrderController();
        if ($action === 'getOrderDetails') {
            $ctrl->getOrderDetails();
        } elseif ($action === 'requestStateTransition') {
            $ctrl->requestStateTransition();
        } else {
            $ctrl->index();
        }
        break;

    /* UC-07 — Emergency Mode (Shot 12) */
    case 'emergency':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/EmergencyController.php';
        $ctrl = new EmergencyController();
        if ($action === 'activate') {
            $ctrl->activateEmergencyMode();
        } else {
            $ctrl->index();
        }
        break;

    /* UC-09 — Archive & Retain Data (Shot 12) */
    case 'archive':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/ArchiveController.php';
        $ctrl = new ArchiveController();
        if ($action === 'run') {
            $ctrl->runArchiveJob();
        } elseif ($action === 'request') {
            $ctrl->requestArchivedOrder();
        } else {
            $ctrl->index();
        }
        break;

    case 'admin':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/UserManagementController.php';
        $ctrl = new UserManagementController();
        if ($action === 'editUser') {
            $ctrl->edit();
        } elseif ($action === 'createUser') {
            $ctrl->createUser();
        } elseif ($action === 'updateRole') {
            $ctrl->updateRole();
        } elseif ($action === 'toggleActive') {
            $ctrl->toggleActive();
        } else {
            /* default: users list */
            $ctrl->index();
        }
        break;

    /* UC-20 — Performance Audit (Shot 7) */
    case 'audit':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        if ($_SESSION['role'] !== 'supplier') {
            http_response_code(403);
            die("Access denied.");
        }
        require_once __DIR__ . '/controllers/AuditController.php';
        $ctrl = new AuditController();
        if ($action === 'performanceReport') {
            $ctrl->requestPerformanceAudit();
        } else {
            $ctrl->requestPerformanceAudit();
        }
        break;

    default:
        /* Catch-all: redirect unauthenticated users to login */
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=auth&action=login");
            exit();
        }
        http_response_code(404);
        echo "<p class='p-4'>Page not found.</p>";
        break;
}
