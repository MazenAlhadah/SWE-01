<?php

require_once __DIR__ . '/../services/ZonalOptimizer.php';
require_once __DIR__ . '/../services/ExpiryWatchdog.php';
require_once __DIR__ . '/../services/SupplierSelector.php';

function assertSameValue($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function testCalculate3DVolumetricUsage() {
    $optimizer = new ZonalOptimizer();
    $zones = [
        [
            'zone_id' => 10,
            'current_occupancy_m3' => '12.50',
            'total_capacity_m3' => '20.00',
        ],
    ];

    $result = $optimizer->calculate3DVolumetricUsage($zones);

    assertSameValue(12.5, $result[10]['used_m3'], 'Used volume should be cast to float.');
    assertSameValue(20.0, $result[10]['total_m3'], 'Total volume should be cast to float.');
    assertSameValue(7.5, $result[10]['free_m3'], 'Free volume should be computed as total minus used.');
}

function testApplyFEFO() {
    $watchdog = new ExpiryWatchdog();
    $items = [
        ['sku' => 'LATE', 'expiry_date' => '2026-06-10'],
        ['sku' => 'EARLY', 'expiry_date' => '2026-05-15'],
        ['sku' => 'MID', 'expiry_date' => '2026-05-20'],
    ];

    $result = $watchdog->applyFEFO($items);

    assertSameValue('EARLY', $result[0]['sku'], 'FEFO should put the earliest expiry first.');
    assertSameValue('MID', $result[1]['sku'], 'FEFO should keep ascending expiry order.');
    assertSameValue('LATE', $result[2]['sku'], 'FEFO should place the latest expiry last.');
}

function testRunTieredSupplierSelection() {
    $selector = new SupplierSelector();
    $suppliers = [
        ['supplier_id' => 1, 'unit_price' => 20, 'deliverySpeed' => 5],
        ['supplier_id' => 2, 'unit_price' => 10, 'deliverySpeed' => 2],
        ['supplier_id' => 3, 'unit_price' => 15, 'deliverySpeed' => 4],
    ];

    $result = $selector->runTieredSupplierSelection($suppliers);

    assertTrue(is_array($result), 'Supplier selection should return the winning supplier row.');
    assertSameValue(2, $result['supplier_id'], 'Supplier with the best computed score should be selected.');
}

$tests = [
    'testCalculate3DVolumetricUsage',
    'testApplyFEFO',
    'testRunTieredSupplierSelection',
];

$failures = 0;

foreach ($tests as $test) {
    try {
        $test();
        echo "[PASS] {$test}\n";
    } catch (Throwable $e) {
        $failures++;
        echo "[FAIL] {$test}\n";
        echo $e->getMessage() . "\n";
    }
}

exit($failures === 0 ? 0 : 1);
