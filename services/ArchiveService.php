<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/NotificationService.php';

class ArchiveService {
    private $order;
    private $ns;

    public function __construct() {
        $this->order = new Order();
        $this->ns = NotificationService::getInstance();
    }

    public function runScheduledArchive() {
        $orders = $this->order->fetchCompletedOrdersOlderThan(12);
        $count = 0;

        foreach ($orders as $row) {
            $data = json_encode($row);
            if ($data === false) {
                continue;
            }

            $archived = $this->order->archiveOrderRecord($row['order_id'], $data);
            if ($archived) {
                $this->order->removeFromActiveDB($row['order_id']);
                $count++;
            }
        }

        if ($count > 0) {
            $this->ns->update("Archive complete. {$count} orders moved to archive.");
        }

        return [
            'archivedCount' => $count,
            'eligibleOrders' => $orders
        ];
    }

    public function fetchFromArchive($orderId) {
        $row = $this->order->fetchFromArchive($orderId);
        if (empty($row)) {
            return [];
        }

        if (!empty($row['archive_data']) && is_string($row['archive_data'])) {
            $decoded = json_decode($row['archive_data'], true);
            if (is_array($decoded)) {
                $row['archive_data'] = $decoded;
            }
        }

        return $row;
    }
}
