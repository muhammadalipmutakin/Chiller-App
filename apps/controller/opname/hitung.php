<?php
session_start();
require '../../controller/db/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_nama'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$user = $_SESSION['user_nama'];

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_locations':
        $query = "SELECT id, lokasi FROM locations WHERE lokasi LIKE '%chiller%'";
        $result = $conn->query($query);

        $locations = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $locations[] = $row;
            }
        }
        echo json_encode($locations);
        break;

    case 'get_items':
        $search = $conn->real_escape_string($_GET['q'] ?? '');
        $query = "SELECT id, item, sku FROM items WHERE item LIKE '%$search%' OR sku LIKE '%$search%' LIMIT 10";
        $result = $conn->query($query);

        $items = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        echo json_encode($items);
        break;

    case 'get_subitems':
        $search = $conn->real_escape_string($_GET['q'] ?? '');
        $query = "SELECT id, subitem FROM subitems WHERE subitem LIKE '%$search%' LIMIT 10";
        $result = $conn->query($query);

        $subitems = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $subitems[] = $row;
            }
        }
        echo json_encode($subitems);
        break;

    case 'get_hitung_data':
        $storage = $conn->real_escape_string($_GET['storage'] ?? '');
        $user = $conn->real_escape_string($_GET['user'] ?? '');

        $query = "SELECT * FROM so_hitung WHERE storage = '$storage'";
        if (!empty($user)) {
            $query .= " AND user_hitung = '$user'";
        }
        $query .= " ORDER BY id asc";

        $result = $conn->query($query);
        $data = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        break;

    case 'save_row':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validate required fields
        if (empty($data['item']) || empty($data['quantity_kg'])) {
            echo json_encode(['error' => 'Item dan berat harus diisi']);
            exit;
        }

        // Prepare item name
        $itemName = $conn->real_escape_string($data['item']);
        if (!empty($data['subitem'])) {
            $subitem = $conn->real_escape_string($data['subitem']);
            $itemName .= " : " . $subitem;
        }

        $storage = $conn->real_escape_string($data['storage']);
        $ekor_pack = intval($data['ekor_pack'] ?? 0);
        $quantity_kg = floatval($data['quantity_kg']);
        $user = $conn->real_escape_string($user);

        // Insert without checking for existing data
        $query = "INSERT INTO so_hitung 
        (item, storage, ekor_pack, quantity_kg, opname_date, user_hitung) 
        VALUES ('$itemName', '$storage', $ekor_pack, $quantity_kg, CURDATE(), '$user')";

        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            error_log("Database error: " . $conn->error);
            echo json_encode(['error' => 'Gagal menyimpan data']);
        }
        break;




    case 'update_row':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validate required fields
        if (empty($data['item']) || empty($data['quantity_kg'])) {
            echo json_encode(['error' => 'Item dan berat harus diisi']);
            exit;
        }

        $id = intval($data['id']);
        $itemName = $conn->real_escape_string($data['item']);
        if (!empty($data['subitem'])) {
            $subitem = $conn->real_escape_string($data['subitem']);
            $itemName .= " : " . $subitem;
        }
        $ekor_pack = intval($data['ekor_pack'] ?? 0);
        $quantity_kg = floatval($data['quantity_kg']);
        $user = $conn->real_escape_string($user);

        $query = "UPDATE so_hitung 
            SET item = '$itemName', 
                ekor_pack = $ekor_pack, 
                quantity_kg = $quantity_kg 
            WHERE id = $id AND user_hitung = '$user'";

        if ($conn->query($query)) {
            echo json_encode(['success' => true]);
        } else {
            error_log("Database error: " . $conn->error);
            echo json_encode(['error' => 'Gagal memperbarui data']);
        }
        break;

    case 'delete_row':
        $id = intval($_GET['id'] ?? 0);
        $user = $conn->real_escape_string($user);

        $query = "DELETE FROM so_hitung WHERE id = $id AND user_hitung = '$user'";

        if ($conn->query($query)) {
            echo json_encode(['success' => $conn->affected_rows > 0]);
        } else {
            error_log("Database error: " . $conn->error);
            echo json_encode(['error' => 'Gagal menghapus data']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$conn->close();
