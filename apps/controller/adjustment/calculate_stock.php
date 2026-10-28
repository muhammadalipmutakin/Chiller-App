<?php
// controller/adjustment/calculate_stock.php

session_start();
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'calculated_data' => []
];

// Database connection
require_once __DIR__ . '/../../controller/db/config.php';

if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    echo json_encode($response);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$stockDateUntil = $input['stock_date_until'] ?? '';
$adjustmentMode = $input['adjustment_mode'] ?? 'csv_only'; // 'csv_only' or 'all_data'
$selectedStorages = $input['selected_storages'] ?? []; // Array of selected storages
$dataToProcess = $input['data'] ?? []; // Data from CSV

// Validate date
if (empty($stockDateUntil) || !strtotime($stockDateUntil)) {
    $response['message'] = 'Invalid stock date until.';
    echo json_encode($response);
    exit();
}

if ($adjustmentMode === 'csv_only' && empty($dataToProcess)) {
    $response['message'] = 'No data provided for calculation in CSV-only mode.';
    echo json_encode($response);
    exit();
}

if ($adjustmentMode === 'all_data' && empty($selectedStorages)) {
    $response['message'] = 'No storages selected for calculation in "All Data" mode.';
    echo json_encode($response);
    exit();
}

$currentStockCache = [];

// Step 1: Fetch current stock based on the adjustment mode
if ($adjustmentMode === 'csv_only') {
    // For CSV_ONLY mode, only fetch stock for items present in the CSV data
    $uniqueCombinations = [];
    foreach ($dataToProcess as $row) {
        $fullItemName = trim($row['item']) . (!empty(trim($row['subitem'])) ? " : " . trim($row['subitem']) : '');
        $uniqueCombinations[trim($row['storage']) . '|' . $fullItemName] = true;
    }

    foreach (array_keys($uniqueCombinations) as $combo) {
        list($storage, $fullItemName) = explode('|', $combo);

        $stmt = $conn->prepare("
            SELECT SUM(ekor_pack) AS current_ekor_pack, SUM(quantity_kg) AS current_quantity_kg
            FROM transactions
            WHERE storage = ?
              AND item = ?
              AND date <= ?
              AND deleted_at IS NULL
        ");

        if ($stmt === false) {
            $response['message'] = 'Failed to prepare stock query (CSV_ONLY): ' . $conn->error;
            echo json_encode($response);
            $conn->close();
            exit();
        }

        $stmt->bind_param("sss", $storage, $fullItemName, $stockDateUntil);
        $stmt->execute();
        $result = $stmt->get_result();
        $stockResult = $result->fetch_assoc();

        $currentStockCache[$combo] = [
            'ekor_pack' => (int)($stockResult['current_ekor_pack'] ?? 0),
            'quantity_kg' => (float)($stockResult['current_quantity_kg'] ?? 0.00)
        ];
        $stmt->close();
    }
} elseif ($adjustmentMode === 'all_data') {
    // For ALL_DATA mode, fetch stock for ALL items in selected storages
    $placeholders = implode(',', array_fill(0, count($selectedStorages), '?'));
    $sql = "
        SELECT storage, item, SUM(ekor_pack) AS current_ekor_pack, SUM(quantity_kg) AS current_quantity_kg
        FROM transactions
        WHERE storage IN ($placeholders)
          AND date <= ?
          AND deleted_at IS NULL
        GROUP BY storage, item
    ";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $response['message'] = 'Failed to prepare stock query (ALL_DATA): ' . $conn->error;
        echo json_encode($response);
        $conn->close();
        exit();
    }

    $types = str_repeat('s', count($selectedStorages)) . 's';
    $params = array_merge($selectedStorages, [$stockDateUntil]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Split combined item name back into item and subitem if possible
        $fullItemName = $row['item'];
        $subitem = '';
        if (strpos($fullItemName, ' : ') !== false) {
            list($itemPart, $subitemPart) = explode(' : ', $fullItemName, 2);
            $fullItemName = $itemPart; // Keep the 'item' part
            $subitem = $subitemPart; // Set the 'subitem' part
        }

        $combo = trim($row['storage']) . '|' . trim($fullItemName) . '|' . trim($subitem); // Key with subitem now
        $currentStockCache[$combo] = [
            'ekor_pack' => (int)($row['current_ekor_pack'] ?? 0),
            'quantity_kg' => (float)($row['current_quantity_kg'] ?? 0.00),
            'storage' => trim($row['storage']),
            'item' => trim($fullItemName),
            'subitem' => trim($subitem)
        ];
    }
    $stmt->close();
}


// Step 2: Process each row from the uploaded data and calculate adjustment
$calculatedData = [];
$processedCsvKeys = [];

foreach ($dataToProcess as $row) {
    $storage = trim($row['storage']);
    $item = trim($row['item']);
    $subitem = trim($row['subitem']);
    $targetEkorPack = (int)($row['target_ekor_pack'] ?? 0);
    $targetQuantityKg = (float)($row['target_quantity_kg'] ?? 0.00);
    $keterangan = trim($row['keterangan'] ?? '');

    $fullItemNameKey = $item . (!empty($subitem) ? " : $subitem" : '');
    $comboKey = $storage . '|' . $item . '|' . $subitem; // Use exact item/subitem for key

    $processedCsvKeys[$comboKey] = true;

    $currentEkorPack = $currentStockCache[$comboKey]['ekor_pack'] ?? 0;
    $currentQuantityKg = $currentStockCache[$comboKey]['quantity_kg'] ?? 0.00;

    // Calculate adjustment needed
    $adjustmentEkorPack = $targetEkorPack - $currentEkorPack;
    $adjustmentQuantityKg = $targetQuantityKg - $currentQuantityKg;

    $calculatedData[] = [
        'storage' => $storage,
        'item' => $item,
        'subitem' => $subitem,
        'current_ekor_pack' => $currentEkorPack,
        'current_quantity_kg' => $currentQuantityKg,
        'target_ekor_pack' => $targetEkorPack, // From CSV
        'target_quantity_kg' => $targetQuantityKg, // From CSV
        'adjustment_ekor_pack' => $adjustmentEkorPack,
        'adjustment_quantity_kg' => round($adjustmentQuantityKg, 2),
        'keterangan' => $keterangan,
        'source' => 'csv' // Mark as from CSV
    ];
}

// Step 3: Add items from current stock that were NOT in the CSV data (only for 'all_data' mode)
if ($adjustmentMode === 'all_data') {
    foreach ($currentStockCache as $comboKey => $stockData) {
        if (!isset($processedCsvKeys[$comboKey])) {
            // This item exists in stock but was not in the CSV
            $storage = $stockData['storage'];
            $item = $stockData['item'];
            $subitem = $stockData['subitem'];

            $currentEkorPack = $stockData['ekor_pack'];
            $currentQuantityKg = $stockData['quantity_kg'];

            // Target from CSV for these items is 0
            $targetEkorPack = 0;
            $targetQuantityKg = 0.00;

            $adjustmentEkorPack = $targetEkorPack - $currentEkorPack;
            $adjustmentQuantityKg = $targetQuantityKg - $currentQuantityKg;

            $calculatedData[] = [
                'storage' => $storage,
                'item' => $item,
                'subitem' => $subitem,
                'current_ekor_pack' => $currentEkorPack,
                'current_quantity_kg' => $currentQuantityKg,
                'target_ekor_pack' => $targetEkorPack, // Target from CSV is 0
                'target_quantity_kg' => $targetQuantityKg, // Target from CSV is 0
                'adjustment_ekor_pack' => $adjustmentEkorPack,
                'adjustment_quantity_kg' => round($adjustmentQuantityKg, 2),
                'keterangan' => 'Stock-only item, target 0 from CSV',
                'source' => 'stock_only' // Mark as from stock only
            ];
        }
    }
}

$response['success'] = true;
$response['message'] = 'Stock adjustment calculated successfully.';
$response['calculated_data'] = $calculatedData;

$conn->close();
echo json_encode($response);
exit();
