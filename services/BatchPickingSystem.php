<?php
class BatchPickingSystem {

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
            $key = (string)($row['bin_id'] ?? '') . '|' . (string)($row['location_code'] ?: 'Unassigned Bin');
            if (!isset($bins[$key])) {
                $bins[$key] = [
                    'bin_id' => isset($row['bin_id']) ? (int)$row['bin_id'] : PHP_INT_MAX,
                    'location_code' => $row['location_code'] ?: 'Unassigned Bin'
                ];
            }
        }

        uasort($bins, function($a, $b) {
            $binCmp = $a['bin_id'] <=> $b['bin_id'];
            if ($binCmp !== 0) {
                return $binCmp;
            }
            return strcmp((string)$a['location_code'], (string)$b['location_code']);
        });

        return array_values(array_map(static fn ($row) => $row['location_code'], $bins));
    }

    public function groupOrdersIntoBatch($orders) {
        usort($orders, function($a, $b) {
            $binCmp = ((int)($a['bin_id'] ?? PHP_INT_MAX)) <=> ((int)($b['bin_id'] ?? PHP_INT_MAX));
            if ($binCmp !== 0) {
                return $binCmp;
            }
            return strcmp((string)$a['location_code'], (string)$b['location_code']);
        });
        return $orders;
    }
}
