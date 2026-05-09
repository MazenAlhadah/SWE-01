<?php
require_once __DIR__ . '/../models/Shipment.php';

class LeadTimeEstimator {

    public function estimateArrival($supplierId, $date) {
        $shipmentModel = new Shipment();
        $history = $shipmentModel->fetchSupplierDeliveryHistory($supplierId);
        return $this->computeEstimatedArrival($history, $date);
    }

    public function computeEstimatedArrival($history, $date) {
        $days = $this->estimateLeadTime($history);
        return date('Y-m-d', strtotime($date . " +{$days} days"));
    }

    public function estimateLeadTime($history) {
        if (empty($history)) {
            return 3;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($history as $row) {
            if (!empty($row['dispatch_date']) && !empty($row['actual_arrival'])) {
                $dispatch = strtotime($row['dispatch_date']);
                $arrival = strtotime($row['actual_arrival']);
                if ($dispatch && $arrival && $arrival >= $dispatch) {
                    $totalDays += (int)round(($arrival - $dispatch) / 86400);
                    $count++;
                }
            } elseif (!empty($row['dispatch_date']) && !empty($row['estimated_arrival'])) {
                $dispatch = strtotime($row['dispatch_date']);
                $arrival = strtotime($row['estimated_arrival']);
                if ($dispatch && $arrival && $arrival >= $dispatch) {
                    $totalDays += (int)round(($arrival - $dispatch) / 86400);
                    $count++;
                }
            }
        }

        if ($count === 0) {
            return 3;
        }

        return max(1, (int)round($totalDays / $count));
    }
}
