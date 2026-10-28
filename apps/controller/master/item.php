<?php
session_name('chiller_app');
session_start();


header("Content-Type: application/json");
require '../../controller/db/config.php';
$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

// Simple router
$method = $_SERVER['REQUEST_METHOD'];
$sku = $_GET['sku'] ?? null;
$searchQuery = $_GET['search'] ?? null; // Search term from client
$limit = $_GET['limit'] ?? 10;
$offset = $_GET['offset'] ?? 0;

try {
    switch ($method) {
        case 'GET':
            // Base SQL for selecting items
            $sql = "SELECT sku, item, kategory, isActive FROM items";
            $countSql = "SELECT COUNT(*) AS total FROM items";

            $params = [];
            $types = "";

            // Apply search filter if searchQuery is provided
            if ($searchQuery) {
                $searchParam = "%" . $searchQuery . "%";
                // Search across sku, item, and kategory columns
                $sql .= " WHERE sku LIKE ? OR item LIKE ? OR kategory LIKE ?";
                $countSql .= " WHERE sku LIKE ? OR item LIKE ? OR kategory LIKE ?";
                $params = [$searchParam, $searchParam, $searchParam];
                $types = "sss"; // Three string parameters
            }

            // --- Count Total Items for Pagination ---
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $totalItems = $countStmt->get_result()->fetch_assoc()['total'];

            // --- Fetch Paginated Items ---
            $sql .= " ORDER BY item ASC LIMIT ? OFFSET ?"; // Added ORDER BY for consistent results
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= "ii"; // Add integer types for limit and offset

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $items,
                'totalItems' => $totalItems
            ]);
            break;

        case 'DELETE':
            if (!$sku) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'SKU diperlukan untuk penghapusan.'
                ]);
                exit();
            }

            // --- Ambil data lama sebelum dihapus untuk log ---
            $old_data = null;
            $select_stmt = $conn->prepare("SELECT id, sku, item, kategory, isActive FROM items WHERE sku = ?");
            if ($select_stmt) {
                $select_stmt->bind_param("s", $sku);
                $select_stmt->execute();
                $result = $select_stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $old_data = $result->fetch_assoc();
                }
                $select_stmt->close();
            } else {
                error_log("DELETE - Failed to prepare select statement for old_data: " . $conn->error);
            }
            // --- END: Ambil data lama ---

            $stmt = $conn->prepare("DELETE FROM items WHERE sku = ?");
            if ($stmt === false) {
                error_log("DELETE - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server saat menyiapkan penghapusan item.']);
                exit();
            }

            $stmt->bind_param("s", $sku);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("DELETE - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menghapus item karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows > 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Item berhasil dihapus.'
                ]);

                // --- KIRIM LOG AKTIVITAS (DELETE) ---
                $log_data = [
                    'action_type' => 'DELETE',
                    'table_name' => 'items',
                    'record_id' => $old_data['id'] ?? null, // Gunakan ID jika ada, atau SKU
                    'data_old' => $old_data, // Data lama sebelum dihapus
                    'data_new' => null, // Operasi DELETE
                    'notes' => "Item SKU '{$sku}' ('" . ($old_data['item'] ?? 'N/A') . "') berhasil dihapus oleh '{$currentUser}'.",
                    'id_user' => $currentUserId
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
                    error_log("Gagal mencatat log aktivitas untuk delete item SKU {$sku}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---

            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak ada item ditemukan dengan SKU tersebut.'
                ]);
            }
            $stmt->close();
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['sku']) || empty($input['item']) || empty($input['kategory'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "SKU, Item, dan Kategori adalah bidang yang wajib diisi untuk pembuatan item."]);
                exit();
            }

            $isActive = isset($input['isActive']) ? (int)$input['isActive'] : 0;

            $stmt = $conn->prepare("INSERT INTO items (sku, item, kategory, isActive) VALUES (?, ?, ?, ?)");
            if ($stmt === false) {
                error_log("POST - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server saat menyiapkan penambahan item.']);
                exit();
            }

            $stmt->bind_param("sssi", $input['sku'], $input['item'], $input['kategory'], $isActive);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("POST - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                if ($stmt->errno == 1062) { // Duplicate entry error
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'message' => 'SKU sudah ada. Harap pilih SKU lain.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Gagal menambahkan item karena masalah database.']);
                }
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal membuat item atau tidak ada baris yang terpengaruh (kesalahan tidak terduga).'
                ]);
            } else {
                $new_item_id = $conn->insert_id; // Ambil ID yang baru dibuat
                http_response_code(201); // Created
                echo json_encode([
                    'success' => true,
                    'message' => 'Item berhasil ditambahkan.',
                    'id' => $new_item_id
                ]);

                // --- BLOK LOG AKTIVITAS DIHAPUS ---
            }
            $stmt->close();
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);

            // Determine if it's an isActive toggle or a full item update
            $updateSku = $input['sku'] ?? $sku; // Prioritize SKU from body if present, else from URL
            $newIsActive = $input['isActive'] ?? null;
            $newItem = $input['item'] ?? null;
            $newKategory = $input['kategory'] ?? null;

            if (!$updateSku) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "SKU diperlukan untuk pembaruan."]);
                exit();
            }

            // --- Ambil data lama sebelum diupdate untuk log ---
            $old_data = null;
            $select_stmt_old = $conn->prepare("SELECT id, sku, item, kategory, isActive FROM items WHERE sku = ?");
            if ($select_stmt_old) {
                $select_stmt_old->bind_param("s", $updateSku);
                $select_stmt_old->execute();
                $result_old = $select_stmt_old->get_result();
                if ($result_old && $result_old->num_rows > 0) {
                    $old_data = $result_old->fetch_assoc();
                }
                $select_stmt_old->close();
            } else {
                error_log("PUT - Failed to prepare select statement for old_data: " . $conn->error);
            }
            // --- END: Ambil data lama ---

            $setClauses = [];
            $params = [];
            $types = "";

            // Handle isActive toggle
            if ($newIsActive !== null) {
                $setClauses[] = "isActive = ?";
                $params[] = (int)$newIsActive;
                $types .= "i";
            }

            // Handle general item/kategory update (optional, based on your form)
            if ($newItem !== null) {
                $setClauses[] = "item = ?";
                $params[] = $newItem;
                $types .= "s";
            }
            if ($newKategory !== null) {
                $setClauses[] = "kategory = ?";
                $params[] = $newKategory;
                $types .= "s";
            }

            if (empty($setClauses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Tidak ada bidang yang valid disediakan untuk pembaruan."]);
                exit();
            }

            $sql = "UPDATE items SET " . implode(', ', $setClauses) . " WHERE sku = ?";
            $params[] = $updateSku;
            $types .= "s";

            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("PUT - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server saat menyiapkan pembaruan item.']);
                exit();
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("PUT - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                if ($stmt->errno == 1062) { // Duplicate entry error for unique SKU
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'message' => 'SKU sudah ada untuk item lain. Harap pilih SKU lain.']);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Gagal memperbarui item karena masalah database.'
                    ]);
                }
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Item ditemukan, tetapi tidak ada perubahan yang dibuat atau SKU tidak valid.'
                ]);
            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Item berhasil diperbarui.'
                ]);

                // --- KIRIM LOG AKTIVITAS (UPDATE) ---
                $log_data = [
                    'action_type' => 'UPDATE',
                    'table_name' => 'items',
                    'record_id' => $old_data['id'] ?? null, // Gunakan ID jika ada, atau SKU
                    'data_old' => $old_data, // Data lama sebelum update
                    'data_new' => $input, // Data baru yang diupdate
                    'notes' => "Item SKU '{$updateSku}' ('" . ($old_data['item'] ?? 'N/A') . "') berhasil diperbarui oleh '{$currentUser}'.",
                    'id_user' => $currentUserId
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
                    error_log("Gagal mencatat log aktivitas untuk update item SKU {$updateSku}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---
            }
            $stmt->close();
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
