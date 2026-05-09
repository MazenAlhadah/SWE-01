<?php
class QRCodeGenerator {

    public function generateQRCode($orderId) {
        return 'QR-ORDER-' . $orderId;
    }

    public function generateLabel($qrCode) {
        return $qrCode;
    }
}
