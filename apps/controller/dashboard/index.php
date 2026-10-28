<?php
// dashboard_api.php
header('Content-Type: application/json');

// Include database configuration
require '../../controller/db/config.php'; // Adjust path if necessary

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

// Determine the type of request
$action = $_GET['action'] ?? 'get_summary_stats'; // Default action

try {
    switch ($action) {
        case 'get_summary_stats':
            // Fetch counts for users, items, subitems, locations, and transactions
            $user_count = 0;
            $item_count = 0;
            $subitem_count = 0;
            $location_count = 0;
            $transaction_count = 0;
            $total_in_kg = 0;
            $total_out_kg = 0;
            $total_in_ekor = 0;
            $total_out_ekor = 0;

            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) $user_count = $row['count'];
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM items");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) $item_count = $row['count'];
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM subitems");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) $subitem_count = $row['count'];
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM locations");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) $location_count = $row['count'];
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM transactions");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) $transaction_count = $row['count'];
            $stmt->close();

            // Calculate total in/out (kg)
            $stmt = $conn->prepare("SELECT SUM(CASE WHEN transaction_type IN ('in', 'transfer-in') THEN quantity_kg ELSE 0 END) AS total_in_kg, SUM(CASE WHEN transaction_type IN ('out', 'transfer-out') THEN ABS(quantity_kg) ELSE 0 END) AS total_out_kg FROM transactions");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $total_in_kg = $row['total_in_kg'] ?? 0;
                $total_out_kg = $row['total_out_kg'] ?? 0;
            }
            $stmt->close();

            // Calculate total in/out (ekor)
            $stmt = $conn->prepare("SELECT SUM(CASE WHEN transaction_type IN ('in', 'transfer-in') THEN ekor_pack ELSE 0 END) AS total_in_ekor, SUM(CASE WHEN transaction_type IN ('out', 'transfer-out') THEN ABS(ekor_pack) ELSE 0 END) AS total_out_ekor FROM transactions");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $total_in_ekor = $row['total_in_ekor'] ?? 0;
                $total_out_ekor = $row['total_out_ekor'] ?? 0;
            }
            $stmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Summary stats fetched successfully';
            $response['data'] = [
                'user_count' => $user_count,
                'item_count' => $item_count,
                'subitem_count' => $subitem_count,
                'location_count' => $location_count,
                'transaction_count' => $transaction_count,
                'total_in_kg' => $total_in_kg,
                'total_out_kg' => $total_out_kg,
                'total_in_ekor' => $total_in_ekor,
                'total_out_ekor' => $total_out_ekor
            ];
            break;

        case 'get_stock_by_location':
            // Current stock grouped by specified storage locations (by weight)
            $query = "
                SELECT
                    t.storage AS location_name,
                    SUM(CASE WHEN t.transaction_type IN ('in', 'transfer-in') THEN t.quantity_kg ELSE 0 END) -
                    SUM(CASE WHEN t.transaction_type IN ('out', 'transfer-out') THEN ABS(t.quantity_kg) ELSE 0 END) AS current_kg
                FROM
                    transactions t
                WHERE
                    t.storage IN ('Chiller 1', 'Chiller 2', 'Chiller 3')
                GROUP BY
                    t.storage
                ORDER BY
                    t.storage ASC;
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            // Hardcoded capacities
            $capacities = [
                'Chiller 1' => 50000,
                'Chiller 2' => 10000,
                'Chiller 3' => 1000
            ];

            $total_current_kg = 0;
            $total_capacity_kg = array_sum($capacities);

            // Initialize data for all chillers to ensure they are always present, even if stock is zero
            $chiller_data = [];
            foreach ($capacities as $location => $capacity) {
                $chiller_data[$location] = [
                    'location_name' => $location,
                    'current_kg' => 0.0,
                    'capacity_kg' => $capacity,
                    'percentage' => 0.0
                ];
            }

            while ($row = $result->fetch_assoc()) {
                $location_name = $row['location_name'];
                $current_kg = (float) $row['current_kg'];
                $capacity_kg = $capacities[$location_name] ?? 0;
                $percentage = ($capacity_kg > 0) ? ($current_kg / $capacity_kg) * 100 : 0;

                $chiller_data[$location_name] = [
                    'location_name' => $location_name,
                    'current_kg' => $current_kg,
                    'capacity_kg' => $capacity_kg,
                    'percentage' => round($percentage, 2)
                ];
                $total_current_kg += $current_kg;
            }
            $stmt->close();

            // Add total data
            $total_percentage = ($total_capacity_kg > 0) ? ($total_current_kg / $total_capacity_kg) * 100 : 0;
            $chiller_data['Total'] = [
                'location_name' => 'Total',
                'current_kg' => $total_current_kg,
                'capacity_kg' => $total_capacity_kg,
                'percentage' => round($total_percentage, 2)
            ];

            // Convert associative array back to indexed array for consistent JSON output
            $data = array_values($chiller_data);

            $response['status'] = 'success';
            $response['message'] = 'Stock by location fetched successfully';
            $response['data'] = $data;
            break;

        case 'get_daily_adjustments_table':
            $daily_adjustments = [];
            $chillers = ['Chiller 1', 'Chiller 2', 'Chiller 3'];

            // Calculate end date as yesterday
            $end_date = date('Y-m-d', strtotime('-1 day'));
            // Calculate start date as 6 days before the end_date, to get a total of 7 days (end_date included)
            $start_date = date('Y-m-d', strtotime('-6 days', strtotime($end_date)));

            // Initialize data structure for the 7-day period (from start_date to end_date)
            for ($i = 0; $i < 7; $i++) {
                $current_date = date('Y-m-d', strtotime("{$start_date} +{$i} days"));
                $day_data = ['date' => $current_date];
                $day_data['total_adjustment'] = 0;
                foreach ($chillers as $chiller) {
                    $day_data[str_replace(' ', '_', strtolower($chiller))] = 0;
                }
                $daily_adjustments[$current_date] = $day_data;
            }

            $query = "
                SELECT
                    DATE(date) AS transaction_day,
                    storage,
                    SUM(quantity_kg) AS daily_adjustment
                FROM
                    transactions
                WHERE
                    transaction_type = 'adjustment'
                    AND date >= ? AND date <= ?
                    AND storage IN ('Chiller 1', 'Chiller 2', 'Chiller 3')
                GROUP BY
                    DATE(date), storage
                ORDER BY
                    transaction_day ASC, storage ASC;
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $day = $row['transaction_day'];
                $storage = str_replace(' ', '_', strtolower($row['storage']));

                if (isset($daily_adjustments[$day])) {
                    $daily_adjustments[$day][$storage] = (float) $row['daily_adjustment'];
                    $daily_adjustments[$day]['total_adjustment'] += (float) $row['daily_adjustment'];
                }
            }
            $stmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Daily adjustments fetched successfully';
            $response['data'] = array_values($daily_adjustments); // Convert to indexed array
            break;

        default:
            throw new Exception("Unknown action.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("API Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
