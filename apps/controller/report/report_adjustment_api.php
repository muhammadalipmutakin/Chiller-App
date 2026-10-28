<?php
session_name('chiller_app');
session_start();
header('Content-Type: application/json');

require_once '../../controller/db/config.php'; // Adjust path if necessary

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

// Ensure user_role is defined or default to 'guest'
$userRole = $_SESSION['user_role'] ?? 'guest';

$action = $_GET['action'] ?? null;
$input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $action;
}

// Initialize $stmt and $conn_obj to null to avoid undefined variable errors in finally block
$stmt = null;
$conn_obj = null;

// Check if $conn (from config.php) is available and an object
if (!isset($conn) || !$conn instanceof mysqli) {
    $response['message'] = 'Database connection error. Please check db/config.php or ensure $conn is a mysqli object.';
    echo json_encode($response);
    exit();
}

$conn_obj = $conn; // Assign the global $conn to our local $conn_obj for consistent handling

try {
    switch ($action) {
        case 'get_summarized_adjustments':
            // This case is for the main table display, still fetches ALL data for client-side filtering/pagination
            $startDate = $_GET['startDate'] ?? '';
            if (empty($startDate)) {
                $startDate = '2000-01-01'; // Default to a very old date if not provided
            }
            $endDate = $_GET['endDate'] ?? '';
            if (empty($endDate)) {
                $endDate = '2050-12-31'; // Default to a very future date if not provided
            }

            // Basic validation for dates
            if (!strtotime($startDate)) {
                throw new Exception("Format Tanggal Awal tidak valid.");
            }
            if (!strtotime($endDate)) {
                throw new Exception("Format Tanggal Akhir tidak valid.");
            }
            if (new DateTime($startDate) > new DateTime($endDate)) {
                throw new Exception("Tanggal akhir tidak boleh lebih kecil dari tanggal awal.");
            }

            $params = [];
            $types = '';

            // SQL to extract the main item from 'item' column and aggregate
            // Note: GROUP_CONCAT(DISTINCT keterangan) will combine all unique descriptions for a summarized item.
            // MAX(adjustment_type) assumes one type per summarized group. If multiple types are possible and need to be shown,
            // GROUP_CONCAT(DISTINCT adjustment_type) might be needed, or adjust the grouping.
            $sqlSelect = "
                SELECT
                    storage,
                    SUBSTRING_INDEX(item, ' : ', 1) AS item, -- Alias as 'item' for consistency in JS (e.g., 'AYAM BROILER')
                    SUM(ekor_pack) AS total_ekor_pack,
                    SUM(quantity_kg) AS total_quantity_kg,
                    MAX(adjustment_type) AS adjustment_type,
                    GROUP_CONCAT(DISTINCT keterangan SEPARATOR '; ') AS keterangan_summary
                FROM
                    transactions
                WHERE
                    transaction_type = 'adjustment'
                    AND date BETWEEN ? AND ?
                    AND deleted_at IS NULL
            ";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';

            $sqlSelect .= " GROUP BY storage, SUBSTRING_INDEX(item, ' : ', 1)"; // Group by the extracted main item
            $sqlSelect .= " ORDER BY storage ASC, SUBSTRING_INDEX(item, ' : ', 1) ASC"; // Order by the extracted main item

            $stmt = $conn_obj->prepare($sqlSelect);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn_obj->error);
            }

            // Dynamically bind parameters
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $summarizedData = [];
            while ($row = $result->fetch_assoc()) {
                // Ensure numeric types are correctly cast for JSON (optional, but good practice)
                $row['total_ekor_pack'] = (float)$row['total_ekor_pack'];
                $row['total_quantity_kg'] = (float)$row['total_quantity_kg'];
                $summarizedData[] = $row;
            }
            $stmt->close();
            $stmt = null; // Set to null to prevent double closing in finally

            $response['status'] = 'success';
            $response['message'] = 'All summarized adjustments fetched successfully for client-side pagination.';
            $response['data'] = $summarizedData;
            $response['pagination'] = [
                'currentPage' => 1, // These are placeholders as frontend handles pagination
                'totalPages' => 1,
                'totalItems' => count($summarizedData)
            ];
            break;

        case 'get_daily_adjustment_details':
            // This case is for expanding individual rows in the main table
            $storage = $_GET['storage'] ?? '';
            $item = $_GET['item'] ?? ''; // This 'item' from frontend is the main_item (e.g., "AYAM BROILER")
            $startDate = $_GET['startDate'] ?? '';
            $endDate = $_GET['endDate'] ?? '';

            if (empty($storage) || empty($item) || empty($startDate) || empty($endDate)) {
                throw new Exception("Parameter 'storage', 'item', 'startDate', dan 'endDate' diperlukan untuk detail harian.");
            }

            $sql = "
                SELECT
                    date,
                    item, -- Keep the full item description for daily details (e.g., 'AYAM BROILER : PAHA')
                    ekor_pack AS daily_ekor_pack,
                    quantity_kg AS daily_quantity_kg,
                    keterangan
                FROM
                    transactions
                WHERE
                    transaction_type = 'adjustment'
                    AND date BETWEEN ? AND ?
                    AND storage = ?
                    AND SUBSTRING_INDEX(item, ' : ', 1) = ? -- Filter by the main item part
                    AND deleted_at IS NULL
                ORDER BY
                    date ASC, item ASC
            ";
            $stmt = $conn_obj->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare statement for daily details failed: " . $conn_obj->error);
            }
            $stmt->bind_param("ssss", $startDate, $endDate, $storage, $item);
            $stmt->execute();
            $result = $stmt->get_result();
            $dailyDetails = [];
            while ($row = $result->fetch_assoc()) {
                $row['daily_ekor_pack'] = (float)$row['daily_ekor_pack'];
                $row['daily_quantity_kg'] = (float)$row['daily_quantity_kg'];
                $dailyDetails[] = $row;
            }
            $stmt->close();
            $stmt = null; // Set to null to prevent double closing in finally

            $response['status'] = 'success';
            $response['message'] = 'Daily adjustment details fetched successfully';
            $response['data'] = $dailyDetails;
            break;

        case 'get_export_summary_data':
            // This action is specifically for the summary export, applying all filters directly.
            $startDate = $_GET['startDate'] ?? '';
            $endDate = $_GET['endDate'] ?? '';
            $storageFilter = $_GET['storage'] ?? ''; // Use a distinct variable name
            $itemSearch = $_GET['itemSearch'] ?? '';
            $typeFilter = $_GET['type'] ?? ''; // Use a distinct variable name

            // Basic validation for dates
            if (!strtotime($startDate)) {
                throw new Exception("Format Tanggal Awal tidak valid.");
            }
            if (!strtotime($endDate)) {
                throw new Exception("Format Tanggal Akhir tidak valid.");
            }
            if (new DateTime($startDate) > new DateTime($endDate)) {
                throw new Exception("Tanggal akhir tidak boleh lebih kecil dari tanggal awal.");
            }

            $params = [];
            $types = '';
            $whereClauses = ["transaction_type = 'adjustment'", "date BETWEEN ? AND ?", "deleted_at IS NULL"];
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';

            if (!empty($storageFilter)) {
                $whereClauses[] = "storage = ?";
                $params[] = $storageFilter;
                $types .= 's';
            }
            if (!empty($itemSearch)) {
                // Search in the main part of the item name
                $whereClauses[] = "SUBSTRING_INDEX(item, ' : ', 1) LIKE ?";
                $params[] = "%" . $itemSearch . "%";
                $types .= 's';
            }
            if (!empty($typeFilter)) {
                $whereClauses[] = "adjustment_type = ?";
                $params[] = $typeFilter;
                $types .= 's';
            }

            $whereSql = implode(" AND ", $whereClauses);

            $sql = "
                SELECT
                    storage,
                    SUBSTRING_INDEX(item, ' : ', 1) AS item,
                    SUM(ekor_pack) AS total_ekor_pack,
                    SUM(quantity_kg) AS total_quantity_kg,
                    MAX(adjustment_type) AS adjustment_type,
                    GROUP_CONCAT(DISTINCT keterangan SEPARATOR '; ') AS keterangan_summary
                FROM
                    transactions
                WHERE
                    " . $whereSql . "
                GROUP BY
                    storage, SUBSTRING_INDEX(item, ' : ', 1)
                ORDER BY
                    storage ASC, SUBSTRING_INDEX(item, ' : ', 1) ASC
            ";

            $stmt = $conn_obj->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare statement for export summary failed: " . $conn_obj->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $summarizedExportData = [];
            while ($row = $result->fetch_assoc()) {
                $row['total_ekor_pack'] = (float)$row['total_ekor_pack'];
                $row['total_quantity_kg'] = (float)$row['total_quantity_kg'];
                $summarizedExportData[] = $row;
            }
            $stmt->close();
            $stmt = null;

            $response['status'] = 'success';
            $response['message'] = 'Summarized data for export fetched successfully.';
            $response['data'] = $summarizedExportData;
            break;

        case 'get_export_all_details_data':
            // This action is specifically for the detailed export, applying all filters directly.
            $startDate = $_GET['startDate'] ?? '';
            $endDate = $_GET['endDate'] ?? '';
            $storageFilter = $_GET['storage'] ?? '';
            $itemSearch = $_GET['itemSearch'] ?? '';
            $typeFilter = $_GET['type'] ?? '';

            // Basic validation for dates
            if (!strtotime($startDate)) {
                throw new Exception("Format Tanggal Awal tidak valid.");
            }
            if (!strtotime($endDate)) {
                throw new Exception("Format Tanggal Akhir tidak valid.");
            }
            if (new DateTime($startDate) > new DateTime($endDate)) {
                throw new Exception("Tanggal akhir tidak boleh lebih kecil dari tanggal awal.");
            }

            $allDetailedData = [];

            // Step 1: Get the distinct summarized items that match the filters.
            // This query is similar to 'get_export_summary_data' but only selects the identifying columns.
            $summaryParams = [];
            $summaryTypes = '';
            $summaryWhereClauses = ["transaction_type = 'adjustment'", "date BETWEEN ? AND ?", "deleted_at IS NULL"];
            $summaryParams[] = $startDate;
            $summaryParams[] = $endDate;
            $summaryTypes .= 'ss';

            if (!empty($storageFilter)) {
                $summaryWhereClauses[] = "storage = ?";
                $summaryParams[] = $storageFilter;
                $summaryTypes .= 's';
            }
            if (!empty($itemSearch)) {
                $summaryWhereClauses[] = "SUBSTRING_INDEX(item, ' : ', 1) LIKE ?";
                $summaryParams[] = "%" . $itemSearch . "%";
                $summaryTypes .= 's';
            }
            if (!empty($typeFilter)) {
                $summaryWhereClauses[] = "adjustment_type = ?";
                $summaryParams[] = $typeFilter;
                $summaryTypes .= 's';
            }

            $summaryWhereSql = implode(" AND ", $summaryWhereClauses);

            // Fetch distinct summary identifiers along with their consolidated keterangan
            $summaryQuery = "
                SELECT
                    storage,
                    SUBSTRING_INDEX(item, ' : ', 1) AS item,
                    GROUP_CONCAT(DISTINCT keterangan SEPARATOR '; ') AS keterangan_summary
                FROM
                    transactions
                WHERE
                    " . $summaryWhereSql . "
                GROUP BY
                    storage, SUBSTRING_INDEX(item, ' : ', 1)
                ORDER BY
                    storage ASC, SUBSTRING_INDEX(item, ' : ', 1) ASC
            ";

            $stmtSummary = $conn_obj->prepare($summaryQuery);
            if (!$stmtSummary) {
                throw new Exception("Prepare statement for all details summary part failed: " . $conn_obj->error);
            }
            if (!empty($summaryParams)) {
                $stmtSummary->bind_param($summaryTypes, ...$summaryParams);
            }
            $stmtSummary->execute();
            $summaryResult = $stmtSummary->get_result();
            $summarizedItems = [];
            while ($row = $summaryResult->fetch_assoc()) {
                $summarizedItems[] = $row;
            }
            $stmtSummary->close();
            $stmtSummary = null;

            // Step 2: For each summarized item, get its individual daily details.
            foreach ($summarizedItems as $summaryItem) {
                $detailQuery = "
                    SELECT
                        date,
                        item, -- Full item name
                        ekor_pack AS daily_ekor_pack,
                        quantity_kg AS daily_quantity_kg,
                        keterangan
                    FROM
                        transactions
                    WHERE
                        transaction_type = 'adjustment'
                        AND date BETWEEN ? AND ?
                        AND storage = ?
                        AND SUBSTRING_INDEX(item, ' : ', 1) = ? -- Filter by the main item part
                        AND deleted_at IS NULL
                    ORDER BY
                        date ASC, item ASC
                ";
                $stmtDetail = $conn_obj->prepare($detailQuery);
                if (!$stmtDetail) {
                    // Log error but continue with other items if one fails
                    error_log("Prepare statement for detail data failed for " . $summaryItem['storage'] . " - " . $summaryItem['item'] . ": " . $conn_obj->error);
                    continue;
                }
                $stmtDetail->bind_param("ssss", $startDate, $endDate, $summaryItem['storage'], $summaryItem['item']);
                $stmtDetail->execute();
                $detailResult = $stmtDetail->get_result();
                while ($row = $detailResult->fetch_assoc()) {
                    // Add summary info to each detail row for easier export mapping in frontend
                    $row['summary_storage'] = $summaryItem['storage'];
                    $row['summary_item'] = $summaryItem['item'];
                    $row['summary_keterangan'] = $summaryItem['keterangan_summary']; // The consolidated summary keterangan
                    $row['daily_ekor_pack'] = (float)$row['daily_ekor_pack'];
                    $row['daily_quantity_kg'] = (float)$row['daily_quantity_kg'];
                    $allDetailedData[] = $row;
                }
                $stmtDetail->close();
            }

            $response['status'] = 'success';
            $response['message'] = 'All detailed adjustment data for export fetched successfully.';
            $response['data'] = $allDetailedData;
            break;

        default:
            $response['message'] = 'Invalid action specified';
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    // Only close if the statement object exists and hasn't been closed yet
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    // Only close if the connection object exists and is still valid
    // This check is important as $conn_obj might have been closed by a previous successful operation
    if ($conn_obj instanceof mysqli && $conn_obj->ping()) {
        $conn_obj->close();
    }
    echo json_encode($response);
}
