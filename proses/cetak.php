<?php
// Ensure Panel directory exists with proper permissions
if (!is_dir('Panel')) {
    mkdir('Panel', 0755, true);
}
if (!is_dir('Panel/' . $judul_tabel_sistem)) {
    mkdir('Panel/' . $judul_tabel_sistem, 0755, true);
}

fopen("Panel/".$judul_tabel_sistem."/cetak.php", "x");
$cetak  = fopen("Panel/".$judul_tabel_sistem."/cetak.php", "w");
include '../data/cetak/header.php';

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
    }else if ($keterangan_field_sistem[$i] == "cetak") {
        include '../data/cetak/judul.php';
    }else{
        include '../data/cetak/judul.php';
    }
}

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
        include '../data/cetak/isi.php';
    }else if ($keterangan_field_sistem[$i] == "index") {
        include '../data/cetak/join.php';
    }else{
        }
    }

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
        include '../data/cetak/sql.php';
    }else if ($keterangan_field_sistem[$i] == "index") {
        include '../data/cetak/ulang.php';
    }else{
        include '../data/cetak/ulang.php';
        }
    }

include '../data/cetak/footer.php';

fwrite($cetak, $isi_cetak);
fclose($cetak);

// Return JSON response consistent with server.php structure
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Cetak file generated successfully',
    'data' => [
        'file_path' => 'Panel/' . $judul_tabel_sistem . '/cetak.php',
        'table_name' => $judul_tabel_sistem,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);
?>
