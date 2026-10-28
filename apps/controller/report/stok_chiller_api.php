<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure session is started to access $_SESSION variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../controller/db/config.php';

define('FLOAT_EPSILON', 0.001);


function getAllCategories(mysqli $conn): array
{
    $categories = [];
    $sql = "SELECT DISTINCT kategori FROM kategori_laporan ORDER BY kategori ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['kategori'];
        }
        $result->free();
    } else {
        error_log("Error fetching categories: " . $conn->error);
    }
    return $categories;
}


function getReportDataFromDB(string $date, string $storage, mysqli $conn, array $allCategories): ?array
{
    $rawChillerData = [];
    $storageWhere = ($storage !== 'All') ? "AND T.storage = ?" : '';

    // Modifikasi ORDER BY: Urutkan berdasarkan storage, kemudian kategori alfabetis, kemudian item alfabetis
    // Changed KL.kategori to coalesce with 'Unknown Category' to ensure sorting works even for NULL categories
    $sql = "
        SELECT T.storage, T.item, SUM(T.ekor_pack) AS ekor, SUM(T.quantity_kg) AS berat, COALESCE(KL.kategori, 'Unknown Category') AS kategori
        FROM transactions AS T
        LEFT JOIN kategori_laporan AS KL ON T.item = KL.item
        WHERE T.deleted_at IS NULL
        AND T.date <= ?
        {$storageWhere}
        GROUP BY T.storage, T.item, KL.kategori
        HAVING ABS(SUM(T.quantity_kg)) > " . FLOAT_EPSILON . " OR ABS(SUM(T.ekor_pack)) > " . FLOAT_EPSILON . "
        ORDER BY T.storage ASC, kategori ASC, T.item ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        throw new Exception('Gagal menyiapkan pernyataan database.');
    }

    if ($storage !== 'All') {
        $stmt->bind_param("ss", $date, $storage);
    } else {
        $stmt->bind_param("s", $date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }

    while ($row = $result->fetch_assoc()) {
        $chillerName = $row['storage'];
        // Ensure category always exists, default to 'Unknown Category' if null from DB
        $category = $row['kategori'] ?? 'Unknown Category';

        if (!isset($rawChillerData[$chillerName])) {
            $rawChillerData[$chillerName] = [
                'data' => [],
                'categoryTotals' => [],
                'chillerTotal' => ['total_e_p' => 0.0, 'total_kg' => 0.0]
            ];
            // Initialize all possible categories for this chiller, even if they have no data yet
            foreach ($allCategories as $cat) {
                if (!isset($rawChillerData[$chillerName]['data'][$cat])) {
                    $rawChillerData[$chillerName]['data'][$cat] = [];
                    $rawChillerData[$chillerName]['categoryTotals'][$cat] = ['total_e_p' => 0.0, 'total_kg' => 0.0];
                }
            }
            // Ensure 'Unknown Category' is also initialized if it's not in $allCategories but appears in data
            if (!in_array('Unknown Category', $allCategories) && !isset($rawChillerData[$chillerName]['data']['Unknown Category'])) {
                $rawChillerData[$chillerName]['data']['Unknown Category'] = [];
                $rawChillerData[$chillerName]['categoryTotals']['Unknown Category'] = ['total_e_p' => 0.0, 'total_kg' => 0.0];
            }
        }

        // If a category not in $allCategories (e.g., new data) is found, initialize it
        if (!isset($rawChillerData[$chillerName]['data'][$category])) {
            $rawChillerData[$chillerName]['data'][$category] = [];
            $rawChillerData[$chillerName]['categoryTotals'][$category] = ['total_e_p' => 0.0, 'total_kg' => 0.0];
        }

        $itemEkor = (float)$row['ekor'];
        $itemBerat = (float)$row['berat'];

        // Inlined formatNumber logic for ekor (0 decimal places)
        $formattedEkor = (abs(round($itemEkor, 0)) < FLOAT_EPSILON) ? '0' : number_format($itemEkor, 0, ',', '.'); // Changed from '' to '.' for thousand separator

        // Inlined formatNumber logic for berat (2 decimal places, as requested)
        $formattedBerat = (abs(round($itemBerat, 2)) < FLOAT_EPSILON) ? '0,00' : number_format($itemBerat, 2, ',', '.'); // Changed from '' to '.' for thousand separator

        $rawChillerData[$chillerName]['data'][$category][] = [
            'item' => $row['item'],
            'e_p' => $formattedEkor,
            'kg' => $formattedBerat
        ];

        $rawChillerData[$chillerName]['categoryTotals'][$category]['total_e_p'] += $itemEkor;
        $rawChillerData[$chillerName]['categoryTotals'][$category]['total_kg'] += $itemBerat;

        $rawChillerData[$chillerName]['chillerTotal']['total_e_p'] += $itemEkor;
        $rawChillerData[$chillerName]['chillerTotal']['total_kg'] += $itemBerat;
    }

    $stmt->close();

    $finalReport = [];
    $grandTotalEP = 0.0;
    $grandTotalKg = 0.0;


    foreach ($rawChillerData as $chillerName => $details) {
        // Format category totals within each chiller
        foreach ($details['categoryTotals'] as $catKey => $catTotals) {
            $rawChillerData[$chillerName]['categoryTotals'][$catKey]['total_e_p'] = (abs(round($catTotals['total_e_p'], 0)) < FLOAT_EPSILON) ? '0' : number_format($catTotals['total_e_p'], 0, ',', '.');
            $rawChillerData[$chillerName]['categoryTotals'][$catKey]['total_kg'] = (abs(round($catTotals['total_kg'], 2)) < FLOAT_EPSILON) ? '0,00' : number_format($catTotals['total_kg'], 2, ',', '.');
        }

        // Inlined formatNumber logic for chiller totals
        $rawChillerData[$chillerName]['chillerTotal']['total_e_p'] = (abs(round($details['chillerTotal']['total_e_p'], 0)) < FLOAT_EPSILON) ? '0' : number_format($details['chillerTotal']['total_e_p'], 0, ',', '.');
        $rawChillerData[$chillerName]['chillerTotal']['total_kg'] = (abs(round($details['chillerTotal']['total_kg'], 2)) < FLOAT_EPSILON) ? '0,00' : number_format($details['chillerTotal']['total_kg'], 2, ',', '.');


        if ($storage === 'All') {
            // For grand totals, accumulate raw float values
            $grandTotalEP += (float)str_replace(',', '.', str_replace('.', '', $rawChillerData[$chillerName]['chillerTotal']['total_e_p']));
            $grandTotalKg += (float)str_replace(',', '.', str_replace('.', '', $rawChillerData[$chillerName]['chillerTotal']['total_kg']));
        }
        $finalReport[$chillerName] = $rawChillerData[$chillerName]; // Use the modified rawChillerData for final report
    }

    if ($storage === 'All') {
        return [
            'all_chiller_data' => $finalReport,
            'grandTotals' => [
                // Inlined formatNumber logic for grand totals
                'grand_total_e_p' => (abs(round($grandTotalEP, 0)) < FLOAT_EPSILON) ? '0' : number_format($grandTotalEP, 0, ',', '.'),
                'grand_total_kg' => (abs(round($grandTotalKg, 2)) < FLOAT_EPSILON) ? '0,00' : number_format($grandTotalKg, 2, ',', '.')
            ]
        ];
    } else {
        if (isset($finalReport[$storage])) {
            return $finalReport[$storage];
        } else {
            return null;
        }
    }
}


// API Endpoint Logic
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    try {
        // Ensure $conn is available from config.php
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
        }

        switch ($_GET['action']) {
            case 'get_report':
                $date = $_GET['date'] ?? '';
                $storage = $_GET['storage'] ?? 'All';

                if (empty($date)) {
                    throw new Exception('Tanggal harus disediakan.');
                }

                $allCategories = getAllCategories($conn); // Get all categories for consistent initialization

                $reportData = getReportDataFromDB($date, $storage, $conn, $allCategories);

                if ($reportData !== null) {
                    $response = ['success' => true, 'data' => $reportData];
                } else {
                    $response = ['success' => true, 'data' => null, 'message' => 'No data found for the selected date and storage.'];
                }
                break;

            // Add other actions if needed
            default:
                $response = ['success' => false, 'message' => 'Unknown action.'];
                break;
        }
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
        $response = ['success' => false, 'message' => $e->getMessage()];
    } finally {
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    }
}

echo json_encode($response);
