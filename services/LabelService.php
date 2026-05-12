<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/ShippingLabel.php';
require_once __DIR__ . '/QRCodeGenerator.php';

class LabelService {
    private $order;
    private $label;
    private $qr;
    private $lastError = '';

    public function __construct() {
        $this->order = new Order();
        $this->label = new ShippingLabel();
        $this->qr = new QRCodeGenerator();
    }

    public function initiateLabelGeneration($orderId, $carrierId) {
        return $this->generateLabel($orderId);
    }

    public function generateLabel($orderId) {
        $order = $this->order->fetchOrderAndCarrierDetails($orderId);
        if (empty($order)) {
            return [];
        }

        $qrCode = $this->qr->generateQRCode($orderId);
        $labelId = $this->label->storeLabel($orderId, $order['carrier_id'] ?? null, $qrCode);

        return [
            'label_id' => $labelId,
            'order_id' => $orderId,
            'carrier_name' => $order['carrier_name'] ?: 'Unassigned Carrier',
            'shipping_address' => $order['shipping_address'],
            'qr_code' => $qrCode
        ];
    }

    public function triggerPrint($labelId) {
        return $labelId > 0;
    }

    public function confirmLabelAttached($orderId, $qrCode) {
        $this->lastError = '';
        $row = $this->label->fetchByOrderId($orderId);
        if (empty($row) || $row['qr_code'] !== $qrCode) {
            $this->lastError = 'Wrong label, reprint required';
            return false;
        }

        return $this->label->confirmScanned($orderId, $qrCode);
    }

    public function getLastError() {
        return $this->lastError;
    }
}
