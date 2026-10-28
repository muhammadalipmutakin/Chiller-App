<?php
session_name('chiller_app');
session_start();

// Sesuaikan path ke file koneksi database Anda
// Pastikan path ini benar relatif terhadap lokasi file route.php
require_once __DIR__ . '/../../controller/db/config.php';

header('Content-Type: application/json');

// Pastikan hanya superadmin yang dapat mengakses API ini
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Akses tidak diizinkan.']);
    exit();
}

try {
    // Bangun query SQL dasar
    $sql_logs = "SELECT la.*, u.nama as user_nama 
                 FROM log_activity la
                 LEFT JOIN users u ON la.id_user = u.id";
    $params = [];
    $types = '';
    $where_clauses = [];

    // Filter Tanggal Awal
    if (!empty($_GET['startDate'])) {
        $where_clauses[] = "la.created_at >= ?";
        $params[] = $_GET['startDate'] . ' 00:00:00';
        $types .= 's';
    }

    // Filter Tanggal Akhir
    if (!empty($_GET['endDate'])) {
        $where_clauses[] = "la.created_at <= ?";
        $params[] = $_GET['endDate'] . ' 23:59:59';
        $types .= 's';
    }

    // Filter Pengguna
    if (!empty($_GET['userId'])) {
        $where_clauses[] = "la.id_user = ?";
        $params[] = $_GET['userId'];
        $types .= 'i';
    }

    // Filter Tabel
    if (!empty($_GET['tableName'])) {
        $where_clauses[] = "la.table_name = ?";
        $params[] = $_GET['tableName'];
        $types .= 's';
    }

    // Filter Tipe Aksi
    if (!empty($_GET['actionType'])) {
        $where_clauses[] = "la.action_type = ?";
        $params[] = $_GET['actionType'];
        $types .= 's';
    }

    // Pencarian Umum
    if (!empty($_GET['searchQuery'])) {
        $search_query = '%' . $_GET['searchQuery'] . '%';
        $where_clauses[] = "(u.nama LIKE ? OR la.action_type LIKE ? OR la.table_name LIKE ? OR la.notes LIKE ? OR la.data_old LIKE ? OR la.data_new LIKE ?)";
        // Tambahkan parameter pencarian untuk setiap kolom yang dicari
        $params = array_merge($params, array_fill(0, 6, $search_query));
        $types .= 'ssssss'; // 6 's' untuk 6 parameter string
    }

    // Gabungkan klausa WHERE jika ada
    if (!empty($where_clauses)) {
        $sql_logs .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // Urutkan data
    $sql_logs .= " ORDER BY la.created_at DESC";

    // Persiapkan statement
    $stmt_logs = $conn->prepare($sql_logs);
    if ($stmt_logs === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind parameter jika ada
    if (!empty($params)) {
        // Menggunakan reference untuk bind_param
        $bind_names = [];
        foreach ($params as $key => $value) {
            $bind_names[$key] = &$params[$key];
        }
        call_user_func_array([$stmt_logs, 'bind_param'], array_merge([$types], $bind_names));
    }

    $stmt_logs->execute();
    $result_logs = $stmt_logs->get_result();

    $all_logs = [];
    while ($row = $result_logs->fetch_assoc()) {
        $row['data_old'] = $row['data_old'] ? json_decode($row['data_old'], true) : null;
        $row['data_new'] = $row['data_new'] ? json_decode($row['data_new'], true) : null;
        $all_logs[] = $row;
    }
    $stmt_logs->close();

    // Ambil daftar filter unik (dilakukan tanpa filter, agar semua opsi tersedia)
    $sql_users = "SELECT id, nama FROM users ORDER BY nama ASC";
    $stmt_users = $conn->prepare($sql_users);
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    $users = [['id' => '', 'nama' => 'Semua Pengguna']];
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt_users->close();

    $sql_tables = "SELECT DISTINCT table_name FROM log_activity ORDER BY table_name ASC";
    $stmt_tables = $conn->prepare($sql_tables);
    $stmt_tables->execute();
    $result_tables = $stmt_tables->get_result();
    $tables = ['Semua Tabel'];
    while ($row = $result_tables->fetch_assoc()) {
        $tables[] = $row['table_name'];
    }
    $stmt_tables->close();

    $sql_action_types = "SELECT DISTINCT action_type FROM log_activity ORDER BY action_type ASC";
    $stmt_action_types = $conn->prepare($sql_action_types);
    $stmt_action_types->execute();
    $result_action_types = $stmt_action_types->get_result();
    $action_types = ['Semua Tipe Aksi'];
    while ($row = $result_action_types->fetch_assoc()) {
        $action_types[] = $row['action_type'];
    }
    $stmt_action_types->close();

    echo json_encode([
        'success' => true,
        'data' => $all_logs,
        'filters' => [
            'users' => $users,
            'tables' => $tables,
            'action_types' => $action_types
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
