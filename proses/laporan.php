<?php
// Ensure laporan directory exists with proper permissions
if (!is_dir('laporan')) {
    mkdir('laporan', 0755, true);
}

fopen("laporan/".$nama_tabel_sistem.".php", "x");
$laporan  = fopen("laporan/".$nama_tabel_sistem.".php", "w");
include '../data/laporan/header.php';

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
    }else if ($keterangan_field_sistem[$i] == "laporan") {
        include '../data/laporan/judul.php';
    }else{
        include '../data/laporan/judul.php';
    }
}

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
        include '../data/laporan/isi.php';
    }else if ($keterangan_field_sistem[$i] == "index") {
        include '../data/laporan/join.php';
    }else{
        }
    }

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
        include '../data/laporan/sql.php';
    }else if ($keterangan_field_sistem[$i] == "index") {
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
        'file_path' => 'laporan/' . $nama_tabel_sistem . '.php',
        'table_name' => $nama_tabel_sistem,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);
?>
