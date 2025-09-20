<?php
// Ensure laporan directory exists with proper permissions
if (!is_dir('laporan')) {
    mkdir('laporan', 0755, true);
}

fopen("laporan/".$new_table_name.".php", "x");
$laporan  = fopen("laporan/".$new_table_name.".php", "w");
include '../data/laporan/header.php';

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
    }else if ($field_properties[$i] == "laporan") {
        include '../data/laporan/judul.php';
    }else{
        include '../data/laporan/judul.php';
    }
}

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
        include '../data/laporan/isi.php';
    }else if ($field_properties[$i] == "index") {
        include '../data/laporan/join.php';
    }else{
        }
    }

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
        include '../data/laporan/sql.php';
    }else if ($field_properties[$i] == "index") {
        include '../data/laporan/ulang.php';
    }else{
        include '../data/laporan/ulang.php';
        }
    }

include '../data/laporan/footer.php';

fwrite($laporan, $isi_laporan);
fclose($laporan);

// Return JSON response consistent with server.php structure
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Laporan file generated successfully',
    'data' => [
        'file_path' => 'laporan/' . $new_table_name . '.php',
        'table_name' => $new_table_name,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);
?>
