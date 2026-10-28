<?php
session_name('chiller_app');
session_start();
// Adjust path if necessary
require_once __DIR__ . '/../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUserId = $_SESSION['user_id'] ?? 0;

header('Content-Type: application/json');

$currentUser = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'guest';

$input = [];

$rawInput = file_get_contents('php://input');
if ($rawInput) {
    $decodedInput = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedInput)) {
        $input = $decodedInput;
    } else {
        // Log JSON parsing error for debugging
        error_log("JSON Decode Error: " . json_last_error_msg() . " for raw input: " . $rawInput);
        // Fallback to $_POST if JSON parsing fails
        if (!empty($_POST)) {
            $input = $_POST;
        }
    }
} else {
    // If raw input is empty, fallback to $_POST
    if (!empty($_POST)) {
        $input = $_POST;
    }
}

// Get 'action' from the determined input, or from GET if it's a GET request
$action = $input['action'] ?? $_GET['action'] ?? '';

// --- End of Input Handling Refinement ---

switch ($action) {
    case 'get_adjustments':
        handleGetAdjustments($conn, $currentUser);
        break;
    case 'save_adjustment':
        // Pass the $input array directly to the handler functions
        handleSaveAdjustment($conn, $currentUser, $currentUserId, $input); // Meneruskan $currentUserId
        break;
    case 'delete_adjustment':
        handleDeleteAdjustment($conn, $currentUser, $currentUserId, $input); // Meneruskan $currentUserId
        break;
    case 'reset_adjustments':
        handleResetAdjustments($conn, $currentUser, $currentUserId, $input); // Meneruskan $currentUserId
        break;
    case 'get_user_list': // New case for getting user list
        handleGetUserList($conn);
        break;
    case 'check_barcode_duplicate':
        handleCheckBarcodeDuplicate($conn, $input);
        break;
    case 'check_barcode_all_users':
        handleCheckBarcodeAllUsers($conn, $input);
        break;
    default:
        // Provide more detailed debug information
        echo json_encode([
            'success' => false,
            'message' => 'Action not specified.',
            'debug' => [
                'action_received' => $action,
                'input_source' => (!empty($input) ? (isset($input['action']) ? 'from_input_array' : 'from_post_fallback') : 'none'),
                'is_input_array' => is_array($input),
                'is_input_empty' => empty($input),
                'raw_php_input_length' => strlen($rawInput),
                'post_keys' => array_keys($_POST),
                'get_keys' => array_keys($_GET)
            ]
        ]);
        break;
}

$conn->close();

function handleGetAdjustments($conn, $currentUser)
{
    // This function now also uses direct string interpolation
    $adjustmentDate = $conn->real_escape_string($_GET['date'] ?? '');
    $selectedStorage = $conn->real_escape_string($_GET['storage'] ?? '');
    $adjustmentType = $conn->real_escape_string($_GET['type'] ?? '');
    $currentUser_escaped = $conn->real_escape_string($currentUser);

    if (empty($adjustmentDate) || empty($selectedStorage) || empty($adjustmentType) || empty($currentUser_escaped)) {
        echo json_encode(['success' => false, 'message' => 'Missing required filters for get_adjustments.']);
        return;
    }

    $sql = "SELECT * FROM adjust_tmp WHERE adjustment_date = '$adjustmentDate' AND storage = '$selectedStorage' AND adjustment_type = '$adjustmentType' AND deleted_at IS NULL ORDER BY created_at ASC";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to execute query for get_adjustments: ' . $conn->error . ' Query: ' . $sql]);
        return;
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}

// Tambahkan parameter $currentUserId
function handleSaveAdjustment($conn, $currentUser, $currentUserId, $input)
{
    // global $logHandlerUrl; // Dihapus karena tidak digunakan lagi

    if (!is_array($input) || empty($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or empty input data provided to save_adjustment function.']);
        return;
    }

    $required_fields = [
        'adjustment_date',
        'storage',
        'adjustment_type',
        'row_unique_id',
        'item'
    ];

    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || (empty($input[$field]) && $input[$field] !== '0' && $input[$field] !== 0)) {
            echo json_encode(['success' => false, 'message' => "Missing or empty required field: $field"]);
            return;
        }
    }

    // --- Prepare and Sanitize Data for SQL ---
    $user_input_escaped = "'" . $conn->real_escape_string($currentUser) . "'";
    $adjustment_date_escaped = "'" . $conn->real_escape_string($input['adjustment_date']) . "'";
    $storage_escaped = "'" . $conn->real_escape_string($input['storage']) . "'";
    $adjustment_type_escaped = "'" . $conn->real_escape_string($input['adjustment_type']) . "'";
    $row_unique_id_value = (int)$input['row_unique_id'];

    $id_in_value = $input['id_in'] ?? null;
    $id_in_sql = ($id_in_value === 'null' || $id_in_value === '' || $id_in_value === null) ? "NULL" : "'" . $conn->real_escape_string($id_in_value) . "'";

    $barcode_value = $input['barcode'] ?? null;
    $barcode_sql = ($barcode_value === null || $barcode_value === '') ? "NULL" : "'" . $conn->real_escape_string($barcode_value) . "'";

    // PERBAIKAN: Pastikan item tidak mengandung subitem
    $item_value = $input['item'] ?? '';
    $subitem_value = $input['subitem'] ?? null;

    // Bersihkan item jika mengandung subitem
    if (!empty($item_value) && strpos($item_value, ' : ') !== false) {
        $parts = explode(' : ', $item_value, 2);
        $item_value = trim($parts[0]);
        // Jika subitem belum diisi, ambil dari item
        if (empty($subitem_value) && isset($parts[1])) {
            $subitem_value = trim($parts[1]);
        }
    }

    $item_escaped = "'" . $conn->real_escape_string($item_value) . "'";

    $subitem_sql = ($subitem_value === null || $subitem_value === '') ? "NULL" : "'" . $conn->real_escape_string($subitem_value) . "'";

    $ekor_stock_current_value = $input['ekor_stock_current'] ?? null;
    $ekor_stock_current_sql = ($ekor_stock_current_value === null || $ekor_stock_current_value === '') ? "NULL" : (float)$ekor_stock_current_value;

    $berat_stock_current_value = $input['berat_stock_current'] ?? null;
    $berat_stock_current_sql = ($berat_stock_current_value === null || $berat_stock_current_value === '') ? "NULL" : (float)$berat_stock_current_value;

    $ekor_new_value = $input['ekor_new'] ?? 0;
    $ekor_new_sql = (float)$ekor_new_value;

    $berat_new_value = $input['berat_new'] ?? 0;
    $berat_new_sql = (float)$berat_new_value;

    $keterangan_value = $input['keterangan'] ?? null;
    $keterangan_sql = ($keterangan_value === null || $keterangan_value === '') ? "NULL" : "'" . $conn->real_escape_string($keterangan_value) . "'";

    $is_edited_value = (int)($input['is_edited'] ?? 0);
    $is_details_collapsed_value = (int)($input['is_details_collapsed'] ?? 0);

    $conn->begin_transaction();

    try {
        // --- Periksa keberadaan data ---
        $check_sql = "SELECT * FROM adjust_tmp WHERE row_unique_id = {$row_unique_id_value} AND adjustment_date = {$adjustment_date_escaped} AND storage = {$storage_escaped} AND adjustment_type = {$adjustment_type_escaped} AND user_input = {$user_input_escaped} AND deleted_at IS NULL";
        $check_result = $conn->query($check_sql);

        if (!$check_result) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to execute check query: ' . $conn->error . ' Query: ' . $check_sql]);
            return;
        }

        if ($check_result->num_rows > 0) {
            // Data sudah ada, lakukan UPDATE
            $update_sql = "UPDATE adjust_tmp SET
                                id_in = {$id_in_sql},
                                barcode = {$barcode_sql},
                                item = {$item_escaped},
                                subitem = {$subitem_sql},
                                ekor_stock_current = {$ekor_stock_current_sql},
                                berat_stock_current = {$berat_stock_current_sql},
                                ekor_new = {$ekor_new_sql},
                                berat_new = {$berat_new_sql},
                                keterangan = {$keterangan_sql},
                                is_edited = {$is_edited_value},
                                is_details_collapsed = {$is_details_collapsed_value},
                                updated_at = CURRENT_TIMESTAMP
                            WHERE row_unique_id = {$row_unique_id_value}
                            AND adjustment_date = {$adjustment_date_escaped}
                            AND storage = {$storage_escaped}
                            AND adjustment_type = {$adjustment_type_escaped}
                            AND user_input = {$user_input_escaped}
                            AND deleted_at IS NULL";

            if ($conn->query($update_sql)) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Adjustment updated successfully.']);
            } else {
                throw new Exception('Failed to update adjustment: ' . $conn->error . ' Query: ' . $update_sql);
            }
        } else {
            // Data belum ada, lakukan INSERT
            $insert_sql = "INSERT INTO adjust_tmp (
                                user_input, adjustment_date, storage, adjustment_type, row_unique_id,
                                id_in, barcode, item, subitem,
                                ekor_stock_current, berat_stock_current, ekor_new, berat_new,
                                keterangan, is_edited, is_details_collapsed
                            ) VALUES (
                                {$user_input_escaped}, {$adjustment_date_escaped}, {$storage_escaped}, {$adjustment_type_escaped}, {$row_unique_id_value},
                                {$id_in_sql}, {$barcode_sql}, {$item_escaped}, {$subitem_sql},
                                {$ekor_stock_current_sql}, {$berat_stock_current_sql}, {$ekor_new_sql}, {$berat_new_sql},
                                {$keterangan_sql}, {$is_edited_value}, {$is_details_collapsed_value}
                            )";

            if ($conn->query($insert_sql)) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Adjustment inserted successfully.']);
            } else {
                throw new Exception('Failed to insert adjustment: ' . $conn->error . ' Query: ' . $insert_sql);
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in handleSaveAdjustment: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to save adjustment: ' . $e->getMessage()]);
    }
}

// Tambahkan parameter $currentUserId
function handleDeleteAdjustment($conn, $currentUser, $currentUserId, $input)
{
    // global $logHandlerUrl; // Dikomentari karena tidak digunakan

    if (!is_array($input) || empty($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or empty input data provided to delete_adjustment function.']);
        return;
    }

    $row_unique_id = (int)($input['row_unique_id'] ?? 0);
    $adjustment_date_escaped = "'" . $conn->real_escape_string($input['adjustment_date'] ?? '') . "'";
    $storage_escaped = "'" . $conn->real_escape_string($input['storage'] ?? '') . "'";
    $adjustment_type_escaped = "'" . $conn->real_escape_string($input['adjustment_type'] ?? '') . "'";
    $currentUser_escaped = "'" . $conn->real_escape_string($currentUser) . "'";

    if (empty($row_unique_id) || empty($input['adjustment_date']) || empty($input['storage']) || empty($input['adjustment_type'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields for deletion.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // --- Ambil data lama sebelum dihapus (soft delete) untuk log ---
        $old_data = null;
        $select_sql = "SELECT * FROM adjust_tmp WHERE row_unique_id = {$row_unique_id} AND adjustment_date = {$adjustment_date_escaped} AND storage = {$storage_escaped} AND adjustment_type = {$adjustment_type_escaped} AND user_input = {$currentUser_escaped} AND deleted_at IS NULL";
        $select_result = $conn->query($select_sql);
        if ($select_result && $select_result->num_rows > 0) {
            $old_data = $select_result->fetch_assoc();
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Adjustment not found or already deleted.']);
            return;
        }
        // --- END: Ambil data lama ---

        $sql = "UPDATE adjust_tmp SET deleted_at = CURRENT_TIMESTAMP WHERE row_unique_id = {$row_unique_id} AND adjustment_date = {$adjustment_date_escaped} AND storage = {$storage_escaped} AND adjustment_type = {$adjustment_type_escaped} AND user_input = {$currentUser_escaped}";

        if ($conn->query($sql)) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Adjustment marked as deleted.']);

            // --- KIRIM LOG AKTIVITAS (DELETE) - DIKOMENTARI ---
            /*
            $log_data = [
                'action_type' => 'DELETE',
                'table_name' => 'adjust_tmp',
                'record_id' => $old_data['id'] ?? null,
                'data_old' => $old_data, // Data lama sebelum dihapus
                'data_new' => null, // Ini adalah operasi delete, jadi data_new null
                'notes' => "Data penyesuaian (adjustment) untuk ID unik {$row_unique_id} berhasil dihapus (soft delete) oleh user '{$currentUser}'.",
                'id_user' => $currentUserId // <--- BARIS INI DITAMBAHKAN
            ];

            $ch = curl_init($logHandlerUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($log_data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT_MS => 500,
                CURLOPT_NOSIGNAL => 1
            ]);
            $log_response = curl_exec($ch);
            $log_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $log_curl_error = curl_error($ch);
            curl_close($ch);

            if ($log_http_code !== 201) {
                error_log("Gagal mencatat log aktivitas untuk delete adjustment. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
            }
            */
            // --- END KIRIM LOG AKTIVITAS ---

        } else {
            throw new Exception('Failed to delete adjustment: ' . $conn->error . ' Query: ' . $sql);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in handleDeleteAdjustment: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete adjustment: ' . $e->getMessage()]);
    }
}

// Tambahkan parameter $currentUserId
function handleResetAdjustments($conn, $currentUser, $currentUserId, $input)
{
    // global $logHandlerUrl; // Dikomentari karena tidak digunakan

    if (!is_array($input) || empty($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or empty input data provided to reset_adjustments function.']);
        return;
    }

    $adjustment_date_escaped = "'" . $conn->real_escape_string($input['adjustment_date'] ?? '') . "'";
    $storage_escaped = "'" . $conn->real_escape_string($input['storage'] ?? '') . "'";
    $adjustment_type_escaped = "'" . $conn->real_escape_string($input['adjustment_type'] ?? '') . "'";
    $currentUser_escaped = "'" . $conn->real_escape_string($currentUser) . "'";

    if (empty($input['adjustment_date']) || empty($input['storage']) || empty($input['adjustment_type'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields for reset.']);
        return;
    }

    $conn->begin_transaction();

    try {
        $sql = "UPDATE adjust_tmp SET deleted_at = CURRENT_TIMESTAMP WHERE adjustment_date = {$adjustment_date_escaped} AND storage = {$storage_escaped} AND adjustment_type = {$adjustment_type_escaped} AND deleted_at IS NULL";

        if ($conn->query($sql)) {
            $affected_rows = $conn->affected_rows;
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'All adjustments reset for current filter.']);

            // --- KIRIM LOG AKTIVITAS (MASS DELETE/RESET) - DIKOMENTARI ---
            /*
            $log_data = [
                'action_type' => 'DELETE', // Ini adalah operasi delete massal
                'table_name' => 'adjust_tmp',
                'record_id' => null, // Tidak ada ID tunggal untuk reset massal
                'data_old' => ['filter_used' => $input], // Log filter yang digunakan untuk reset
                'data_new' => null,
                'notes' => "Reset massal penyesuaian ({$affected_rows} record terpengaruh) untuk tanggal {$input['adjustment_date']}, storage {$input['storage']}, tipe {$input['adjustment_type']} oleh user '{$currentUser}'.",
                'id_user' => $currentUserId // <--- BARIS INI DITAMBAHKAN
            ];

            $ch = curl_init($logHandlerUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($log_data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT_MS => 500,
                CURLOPT_NOSIGNAL => 1
            ]);
            $log_response = curl_exec($ch);
            $log_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $log_curl_error = curl_error($ch);
            curl_close($ch);

            if ($log_http_code !== 201) {
                error_log("Gagal mencatat log aktivitas untuk reset adjustment. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
            }
            */
            // --- END KIRIM LOG AKTIVITAS ---

        } else {
            throw new Exception('Failed to reset adjustments: ' . $conn->error . ' Query: ' . $sql);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in handleResetAdjustments: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to reset adjustments: ' . $e->getMessage()]);
    }
}

/**
 * Handles fetching a distinct list of users from the adjust_tmp table.
 *
 * @param mysqli $conn The database connection object.
 */
function handleGetUserList($conn)
{
    $sql = "SELECT DISTINCT user_input FROM adjust_tmp ORDER BY user_input ASC";
    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve user list: ' . $conn->error]);
        return;
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row['user_input'];
    }

    echo json_encode(['success' => true, 'data' => $users]);
}

function handleCheckBarcodeDuplicate($conn, $input)
{
    if (!is_array($input) || empty($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        return;
    }

    $barcode = $conn->real_escape_string($input['barcode'] ?? '');
    $adjustment_date = $conn->real_escape_string($input['adjustment_date'] ?? '');
    $storage = $conn->real_escape_string($input['storage'] ?? '');
    $adjustment_type = $conn->real_escape_string($input['adjustment_type'] ?? '');

    if (empty($barcode) || empty($adjustment_date) || empty($storage) || empty($adjustment_type)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        return;
    }

    $sql = "SELECT user_input, created_at FROM adjust_tmp 
            WHERE barcode = '$barcode' 
            AND adjustment_date = '$adjustment_date' 
            AND storage = '$storage' 
            AND adjustment_type = '$adjustment_type'
            AND deleted_at IS NULL 
            LIMIT 1";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to check barcode: ' . $conn->error]);
        return;
    }

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'data' => [
                'is_exists' => true,
                'user_input' => $row['user_input'],
                'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at']))
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'is_exists' => false
            ]
        ]);
    }
}

function handleCheckBarcodeAllUsers($conn, $input)
{
    if (!is_array($input) || empty($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        return;
    }

    $barcode = $conn->real_escape_string($input['barcode'] ?? '');
    $adjustment_date = $conn->real_escape_string($input['adjustment_date'] ?? '');
    $storage = $conn->real_escape_string($input['storage'] ?? '');
    $adjustment_type = $conn->real_escape_string($input['adjustment_type'] ?? '');

    if (empty($barcode) || empty($adjustment_date) || empty($storage) || empty($adjustment_type)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        return;
    }

    // Cek apakah barcode sudah ada di adjust_tmp untuk tanggal/storage/tipe apa pun
    // TERMASUK untuk user lain
    $sql = "SELECT user_input, created_at FROM adjust_tmp 
            WHERE barcode = '$barcode' 
            AND adjustment_date = '$adjustment_date' 
            AND storage = '$storage' 
            AND adjustment_type = '$adjustment_type'
            AND deleted_at IS NULL 
            ORDER BY created_at DESC 
            LIMIT 1";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to check barcode: ' . $conn->error]);
        return;
    }

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'data' => [
                'is_exists' => true,
                'user_input' => $row['user_input'],
                'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at']))
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'is_exists' => false
            ]
        ]);
    }
}
