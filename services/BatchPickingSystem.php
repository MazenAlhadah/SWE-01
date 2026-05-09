<?php
class BatchPichingSystem {

    public function requestPickList($orders) {
        $batch = $this->groupOrdersIntoBatch($orders);
        $route = $this->computeOptimalRoute($batch);
        $binLocations = $this->getZoneLocation($batch);

        return [
            'items' => $batch,
            'route' => $route,
            'binLocations' => $binLocations,
            'order_ids' => array_values(array_unique(array_column($batch, 'order_id')))
        ];
    }

    public function getZoneLocation($orders = []) {
        $result = [];
        foreach ($orders as $row) {
            $result[] = [
                'bin_id' => $row['bin_id'],
                'location_code' => $row['location_code'],
                'zone_name' => $row['zone_name']
            ];
        }
        return $result;
    }

    public function computeOptimalRoute($orders) {
        $bins = [];
        foreach ($orders as $row) {
            $code = $row['location_code'] ?: 'Unassigned Bin';
            if (!in_array($code, $bins)) {
                $bins[] = $code;
            }
        }
        sort($bins);
        return $bins;
    }

    public function groupOrdersIntoBatch($orders) {
        usort($orders, function($a, $b) {
            $zoneCmp = strcmp((string)$a['zone_name'], (string)$b['zone_name']);
            if ($zoneCmp !== 0) {
                return $zoneCmp;
            }
            return strcmp((string)$a['location_code'], (string)$b['location_code']);
        });
        return $orders;
    }
}
