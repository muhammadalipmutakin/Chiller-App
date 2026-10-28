<?php
// Set header JSON secepat mungkin
header('Content-Type: application/json');
session_name('chiller_app');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require '../../controller/db/config.php';

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_grading_report') {
        $startDate = $_GET['startDate'] ?? date('Y-m-d');
        $endDate   = $_GET['endDate']   ?? date('Y-m-d');
        $storage   = trim($_GET['storage'] ?? '');

        if (!strtotime($startDate) || !strtotime($endDate)) {
            throw new Exception("Format tanggal tidak valid.");
        }
        if ($startDate > $endDate) {
            throw new Exception("Tanggal akhir tidak boleh lebih kecil dari tanggal awal.");
        }

        // =========================================================================
        // 1. QUERY ITEM MASUK DARI GRADING (INDEX DRIVEN BY DATE)
        // =========================================================================
        $whereIn = [
            "t.date >= ?",
            "t.date <= ?",
            "t.deleted_at IS NULL",
            "t.transaction_type = 'in'",
            "t.from_location LIKE '%grading%'"
        ];
        $paramsIn = [$startDate, $endDate];
        $typesIn  = "ss";

        if (!empty($storage) && $storage !== 'Semua') {
            $whereIn[] = "t.storage = ?";
            $paramsIn[] = $storage;
            $typesIn .= "s";
        }

        $queryIn = "
            SELECT 
                t.item,
                t.storage,
                t.from_location,
                COUNT(t.id) AS total_transaksi,
                SUM(t.ekor_pack) AS total_ekor,
                SUM(t.quantity_kg) AS total_kg
            FROM transactions t
            WHERE " . implode(' AND ', $whereIn) . "
            GROUP BY t.item, t.storage, t.from_location
        ";

        $stmtIn = $conn->prepare($queryIn);
        if (!$stmtIn) {
            throw new Exception("Query Grading IN Error: " . $conn->error);
        }
        $stmtIn->bind_param($typesIn, ...$paramsIn);
        $stmtIn->execute();
        $resIn = $stmtIn->get_result();

        $gradingInData = [];
        $totalsIn = ['transaksi' => 0, 'ekor' => 0, 'kg' => 0.0];

        while ($row = $resIn->fetch_assoc()) {
            $fullItem = $row['item'] ?? '';
            $itemName = $fullItem;
            $subitemName = '';

            if (strpos($fullItem, ' : ') !== false) {
                $parts = explode(' : ', $fullItem, 2);
                $itemName = trim($parts[0]);
                $subitemName = trim($parts[1]);
            }

            $ekor = (int)$row['total_ekor'];
            $kg   = (float)$row['total_kg'];
            $trx  = (int)$row['total_transaksi'];

            $gradingInData[] = [
                'full_item_name'  => $fullItem,
                'item_name'       => $itemName,
                'subitem_name'    => $subitemName,
                'storage'         => $row['storage'],
                'from_location'   => $row['from_location'],
                'total_transaksi' => $trx,
                'total_ekor'      => $ekor,
                'total_kg'        => round($kg, 2)
            ];

            $totalsIn['transaksi'] += $trx;
            $totalsIn['ekor']      += $ekor;
            $totalsIn['kg']        += $kg;
        }
        $stmtIn->close();

        // Natural sort Subitem lalu Item
        usort($gradingInData, function ($a, $b) {
            $subA = $a['subitem_name'];
            $subB = $b['subitem_name'];

            if ($subA === '' && $subB !== '') return 1;
            if ($subA !== '' && $subB === '') return -1;

            $cmpSub = strnatcasecmp($subA, $subB);
            if ($cmpSub !== 0) {
                return $cmpSub;
            }

            return strcasecmp($a['item_name'], $b['item_name']);
        });

        // =========================================================================
        // 2. QUERY OUT CS: DIKELOMPOKKAN MURNI PER POS TUJUAN & STORAGE (TANPA USER)
        // =========================================================================
        $whereOut = [
            "t.date >= ?",
            "t.date <= ?",
            "t.deleted_at IS NULL",
            "t.transaction_type IN ('out', 'transfer-out')",
            "t.to_location NOT LIKE '%abf%'",
            "t.to_location NOT LIKE '%ekspedisi%'",
            "t.to_location NOT LIKE '%expedisi%'"
        ];
        $paramsOut = [$startDate, $endDate];
        $typesOut  = "ss";

        if (!empty($storage) && $storage !== 'Semua') {
            $whereOut[] = "t.storage = ?";
            $paramsOut[] = $storage;
            $typesOut .= "s";
        }

        $queryOut = "
            SELECT 
                t.to_location,
                t.storage,
                t.item,
                COUNT(t.id) AS total_transaksi,
                SUM(ABS(t.ekor_pack)) AS total_ekor,
                SUM(ABS(t.quantity_kg)) AS total_kg
            FROM transactions t
            WHERE " . implode(' AND ', $whereOut) . "
            GROUP BY t.to_location, t.storage, t.item
        ";

        $stmtOut = $conn->prepare($queryOut);
        if (!$stmtOut) {
            throw new Exception("Query Out CS Error: " . $conn->error);
        }
        $stmtOut->bind_param($typesOut, ...$paramsOut);
        $stmtOut->execute();
        $resOut = $stmtOut->get_result();

        $outCsMap = [];
        $totalsOut = ['transaksi' => 0, 'ekor' => 0, 'kg' => 0.0];

        while ($row = $resOut->fetch_assoc()) {
            $toLoc    = $row['to_location'] ?? '-';
            $stg      = $row['storage'] ?? '-';
            $fullItem = $row['item'] ?? '-';
            $ekor     = (int)$row['total_ekor'];
            $kg       = (float)$row['total_kg'];
            $trx      = (int)$row['total_transaksi'];

            $itemName    = $fullItem;
            $subitemName = '';
            if (strpos($fullItem, ' : ') !== false) {
                $parts = explode(' : ', $fullItem, 2);
                $itemName = trim($parts[0]);
                $subitemName = trim($parts[1]);
            }

            // Key Grouping murni per Tujuan Pos dan Storage
            $groupKey = "{$toLoc}|{$stg}";

            if (!isset($outCsMap[$groupKey])) {
                $outCsMap[$groupKey] = [
                    'to_location'     => $toLoc,
                    'storage'         => $stg,
                    'total_transaksi' => 0,
                    'total_ekor'      => 0,
                    'total_kg'        => 0.0,
                    'items_breakdown' => []
                ];
            }

            $outCsMap[$groupKey]['total_transaksi'] += $trx;
            $outCsMap[$groupKey]['total_ekor']      += $ekor;
            $outCsMap[$groupKey]['total_kg']        += $kg;

            $outCsMap[$groupKey]['items_breakdown'][] = [
                'full_item'       => $fullItem,
                'item_name'       => $itemName,
                'subitem_name'    => $subitemName,
                'total_transaksi' => $trx,
                'total_ekor'      => $ekor,
                'total_kg'        => round($kg, 2)
            ];

            $totalsOut['transaksi'] += $trx;
            $totalsOut['ekor']      += $ekor;
            $totalsOut['kg']        += $kg;
        }
        $stmtOut->close();

        // Urutkan rincian item di setiap pos berdasarkan subitem & item secara natural
        foreach ($outCsMap as &$posItem) {
            usort($posItem['items_breakdown'], function ($a, $b) {
                $subA = $a['subitem_name'];
                $subB = $b['subitem_name'];

                if ($subA === '' && $subB !== '') return 1;
                if ($subA !== '' && $subB === '') return -1;

                $cmpSub = strnatcasecmp($subA, $subB);
                if ($cmpSub !== 0) {
                    return $cmpSub;
                }
                return strcasecmp($a['item_name'], $b['item_name']);
            });
        }
        unset($posItem);

        $outCsData = array_values($outCsMap);
        usort($outCsData, function ($a, $b) {
            return $b['total_kg'] <=> $a['total_kg'];
        });

        $response['status'] = 'success';
        $response['message'] = 'Data grading berhasil dimuat.';
        $response['data'] = [
            'grading_in' => $gradingInData,
            'out_cs'     => $outCsData,
            'totals'     => [
                'in' => [
                    'transaksi' => $totalsIn['transaksi'],
                    'ekor'      => $totalsIn['ekor'],
                    'kg'        => round($totalsIn['kg'], 2)
                ],
                'out' => [
                    'transaksi' => $totalsOut['transaksi'],
                    'ekor'      => $totalsOut['ekor'],
                    'kg'        => round($totalsOut['kg'], 2)
                ]
            ]
        ];
    } else {
        $response['message'] = 'Metode atau aksi tidak valid.';
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    error_log("Laporan Grading Error: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

echo json_encode($response);
