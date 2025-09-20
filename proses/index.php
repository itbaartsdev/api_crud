<?php
// Ensure Panel directory exists with proper permissions
if (!is_dir('Panel')) {
    mkdir('Panel', 0755, true);
}
if (!is_dir('Panel/' . $judul_tabel_sistem)) {
    mkdir('Panel/' . $judul_tabel_sistem, 0755, true);
}

fopen("Panel/".$judul_tabel_sistem."/index.php", "x");
$index  = fopen("Panel/".$judul_tabel_sistem."/index.php", "w");
include '../data/index/header.php';

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
    }else if ($keterangan_field_sistem[$i] == "index") {
        include '../data/index/judul.php';
    }else{
        include '../data/index/judul.php';
    }
}

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
        include '../data/index/isi.php';
    }else if ($keterangan_field_sistem[$i] == "index") {
        include '../data/index/join.php';
    }else{
        }
    }

for ($i=0; $i < $total; $i++) {
    if ($keterangan_field_sistem[$i] == "primary") {
        include '../data/index/sql.php';
    }else if ($keterangan_field_sistem[$i] == "index") {
        include '../data/index/ulang.php';
    }else{
        include '../data/index/ulang.php';
        }
    }

include '../data/index/footer.php';

fwrite($index, $isi_index);
fclose($index);

// Return JSON response consistent with server.php structure
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Index file generated successfully',
    'data' => [
        'file_path' => 'Panel/' . $judul_tabel_sistem . '/index.php',
        'table_name' => $judul_tabel_sistem,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);
?>
