<?php
// api/download_adjustment_format.php

// Atur header untuk mengunduh file CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="format_adjustment_chiller.csv"'); // Ganti ekstensi menjadi .csv
header('Pragma: no-cache');
header('Expires: 0');

// Definisikan header kolom yang persis seperti yang diharapkan oleh skrip adjustment
$headers = [
    'No',
    'Item : Subitem',
    'Ekor/Pack',
    'Quantity (KG)',
    'Keterangan'
];

// Buka output stream
$output = fopen('php://output', 'w');

if ($output) {
    // Tulis Byte Order Mark (BOM) untuk kompatibilitas Excel dengan karakter non-ASCII
    // Ini membantu Excel mengenali encoding UTF-8 dengan benar saat membuka CSV
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Tulis baris header ke file CSV
    // Gunakan koma sebagai delimiter standar untuk CSV
    fputcsv($output, $headers, ',');

    // Tutup output stream
    fclose($output);
} else {
    // Tangani error jika fopen gagal
    http_response_code(500);
    echo "Error generating file.";
}
exit();
