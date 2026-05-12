<?php
require_once __DIR__ . '/../models/Shipment.php';
require_once __DIR__ . '/../models/ShippingCarrier.php';
require_once __DIR__ . '/LabelService.php';

class CarrierSelectionService {

    public function initiateCarrierSelection($shipmentId, $orderId) {
        if (!$orderId) {
            $shipmentModel = new Shipment();
            $orderId = $shipmentModel->fetchOrderIdForShipment($shipmentId);
        }

        $orderDetails = $this->fetchOrderDetails($orderId);
        $carriers = $this->fetchAvailableCarriers();
        $urgency = strtoupper((string)($orderDetails['urgency'] ?? 'NORMAL'));
        $filteredCarriers = $this->applyCarrierSelectionLogic($carriers, $orderDetails['shipping_address'], $urgency);
        $rankedCarriers = $this->rankCarriers($filteredCarriers, $urgency);

        if (empty($rankedCarriers)) {
            return null;
        }

        $carrier = $rankedCarriers[0];
        return [
            'carrierId' => $carrier['carrier_id'],
            'name' => $carrier['carrier_name'],
            'estimatedDelivery' => $carrier['delivery_speed_days'] . ' day(s)',
            'cost' => $carrier['base_cost'],
            'orderId' => $orderDetails['order_id'],
            'shipmentId' => $shipmentId
        ];
    }

    public function fetchOrderDetails($orderId) {
        $carrierModel = new ShippingCarrier();
        return $carrierModel->fetchOrderDetails($orderId);
    }

    public function fetchAvailableCarriers() {
        $carrierModel = new ShippingCarrier();
        return $carrierModel->fetchAvailableCarriers();
    }

    public function applyCarrierSelectionLogic($carriers, $addr, $urg) {
        if (empty($carriers)) {
            return [];
        }

        $filtered = [];
        foreach ($carriers as $row) {
            if (empty($row['coverage_regions']) || stripos($row['coverage_regions'], $addr) !== false) {
                $filtered[] = $row;
            }
        }

        if (empty($filtered)) {
            $filtered = $carriers;
        }

        if ($urg === 'HIGH') {
            usort($filtered, function($a, $b) {
                if ($a['delivery_speed_days'] === $b['delivery_speed_days']) {
                    return $a['base_cost'] <=> $b['base_cost'];
                }
                return $a['delivery_speed_days'] <=> $b['delivery_speed_days'];
            });
        } else {
            usort($filtered, function($a, $b) {
                if ($a['base_cost'] === $b['base_cost']) {
                    return $a['delivery_speed_days'] <=> $b['delivery_speed_days'];
                }
                return $a['base_cost'] <=> $b['base_cost'];
            });
        }

        return $filtered;
    }

    public function rankCarriers($carriers, $urgency = 'NORMAL') {
        if (strtoupper($urgency) === 'HIGH') {
            usort($carriers, function($a, $b) {
                if ($a['delivery_speed_days'] === $b['delivery_speed_days']) {
                    return $a['base_cost'] <=> $b['base_cost'];
                }
                return $a['delivery_speed_days'] <=> $b['delivery_speed_days'];
            });
            return $carriers;
        }

        usort($carriers, function($a, $b) {
            if ($a['base_cost'] === $b['base_cost']) {
                return $a['delivery_speed_days'] <=> $b['delivery_speed_days'];
            }
            return $a['base_cost'] <=> $b['base_cost'];
        });
        return $carriers;
    }

    public function assignCarrier($shipmentId, $carrierId) {
        $shipmentModel = new Shipment();
        $orderId = $shipmentModel->fetchOrderIdForShipment($shipmentId);
        $shipmentModel->updateShipmentCarrier($shipmentId, $carrierId);

        $carrierModel = new ShippingCarrier();
        $carrierModel->linkCarrierToOrder($orderId, $carrierId);

        $ls = new LabelService();
        $ls->initiateLabelGeneration($orderId, $carrierId);

        return [
            'carrierId' => $carrierId,
            'estimatedDelivery' => ''
        ];
    }
}
