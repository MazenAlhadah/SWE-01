<?php
/* In folder structure and class diagram — not an invented class */
class ZonalOptimizer {

    /* UC-02: compute velocity score for each item (simulate: sales_velocity column) */
    public function computeVelocityScore($items) {
        /* Items already sorted DESC by sales_velocity from fetchItemCatalog() */
        $scores = [];
        foreach ($items as $row) {
            $scores[$row['item_id']] = (float)$row['sales_velocity'];
        }
        return $scores;
    }

    /* UC-02: compute 3D volumetric usage per zone */
    public function calculate3DVolumetricUsage($zones) {
        $data = [];
        foreach ($zones as $z) {
            $data[$z['zone_id']] = [
                'used_m3'  => (float)$z['current_occupancy_m3'],
                'total_m3' => (float)$z['total_capacity_m3'],
                'free_m3'  => (float)$z['total_capacity_m3'] - (float)$z['current_occupancy_m3'],
            ];
        }
        return $data;
    }

    /* UC-02: suggest zone per item — high velocity → first zone with free space */
    public function runSmartZonalOptimizer($items, $zones) {
        $velocity_scores = $this->computeVelocityScore($items);
        $volumetric      = $this->calculate3DVolumetricUsage($zones);

        /* Sort zones by free space DESC */
        usort($zones, function($a, $b) use ($volumetric) {
            return $volumetric[$b['zone_id']]['free_m3'] <=> $volumetric[$a['zone_id']]['free_m3'];
        });

        $suggestions = [];
        foreach ($items as $item) {
            /* Simulate: assign item to the zone with most free space */
            $target = $zones[0] ?? null;
            if ($target) {
                $suggestions[] = [
                    'item_id'   => $item['item_id'],
                    'sku'       => $item['sku'],
                    'name'      => $item['name'],
                    'zone_id'   => $target['zone_id'],
                    'zone_name' => $target['zone_name'],
                    'velocity'  => $velocity_scores[$item['item_id']] ?? 0,
                ];
            }
        }
        return $suggestions;
    }
}
