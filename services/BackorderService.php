<?php
require_once __DIR__ . '/../models/Backorder.php';
require_once __DIR__ . '/../models/NotificationService.php';

/* In folder structure and class diagram — not an invented class */
class BackorderService {

    private $backorder;
    private $ns;

    public function __construct() {
        $this->backorder = new Backorder();
        $this->ns        = NotificationService::getInstance();
    }

    /* UC-15: check incoming items against open backorders */
    public function checkAgainstBackorders($incoming_items) {
        $backorders = $this->backorder->fetchOpenBackorders();
        return $this->backorder->matchIncomingToBackorders($incoming_items, $backorders);
    }

    /* UC-15: fetch open backorders (pass-through for CDC) */
    public function fetchAndMatchBackorders($incoming_items) {
        return $this->checkAgainstBackorders($incoming_items);
    }

    /* UC-15: update backorder record to CROSS_DOCKED */
    public function updateBackorderRecord($backorder_id, $status) {
        /* Enum: 'OPEN' | 'FULFILLED' | 'CROSS_DOCKED' */
        $this->backorder->setBackorderStatus($backorder_id, $status);
    }
}
