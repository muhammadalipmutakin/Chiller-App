<?php
session_start();
header('Content-Type: application/json');

require '../../controller/db/config.php';

$response = ['status' => 'error', 'message' => 'Invalid request', 'data' => []];
$action = $_GET['action'] ?? $_POST['action'] ?? 'get_stock_data';

try {
    if ($action === 'get_filters') {
        // Fetch chiller locations only
        $stmt = $conn->prepare("SELECT lokasi FROM locations WHERE lokasi LIKE 'chiller%' ORDER BY lokasi ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row['lokasi'];
        }
        $stmt->close();

        $response = [
            'status' => 'success',
            'message' => 'Filter data fetched successfully',
            'data' => ['locations' => $locations]
        ];
    } elseif ($action === 'get_stock_data') {
        $reportDate = $_GET['reportDate'] ?? date('Y-m-d');
        $storage = $_GET['storage'] ?? 'Semua';

        if (!strtotime($reportDate)) {
            throw new Exception("Invalid report date format.");
        }

        // Get all chiller locations if 'Semua' is selected
        $locations = [];
        if ($storage === 'Semua') {
            $stmt = $conn->prepare("SELECT lokasi FROM locations WHERE lokasi LIKE 'chiller%' ORDER BY lokasi ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $locations[] = $row['lokasi'];
            }
            $stmt->close();
        } else {
            $locations = [$storage];
        }

        $stockData = [];
        foreach ($locations as $loc) {
            $query = "SELECT 
                        t.item AS item_name,
                        t.storage AS storage_name,
                        SUM(t.ekor_pack) AS current_ep,
                        SUM(t.quantity_kg) AS current_kg
                      FROM transactions t
                      WHERE t.deleted_at IS NULL 
                        AND t.date <= ?
                        AND t.storage = ?
                      GROUP BY t.item, t.storage
                      HAVING (SUM(t.ekor_pack) != 0 OR SUM(t.quantity_kg) != 0)
                      ORDER BY t.item";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $reportDate, $loc);
            $stmt->execute();
            $result = $stmt->get_result();

            $chillerData = [];
            while ($row = $result->fetch_assoc()) {
                $chillerData[] = [
                    'item' => $row['item_name'],
                    'storage' => $row['storage_name'],
                    'current_ep' => (int)$row['current_ep'],
                    'current_kg' => (float)round($row['current_kg'], 2)
                ];
            }
            $stmt->close();

            if (!empty($chillerData)) {
                $stockData[] = [
                    'location' => $loc,
                    'items' => $chillerData
                ];
            }
        }

        $response = [
            'status' => 'success',
            'message' => 'Stock data retrieved successfully',
            'data' => $stockData
        ];
    } else {
        throw new Exception("Unknown action.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("API Error: " . $e->getMessage());
} finally {
    if ($conn) $conn->close();
}

echo json_encode($response);
