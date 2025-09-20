<?php
// Ensure Panel directory exists with proper permissions
if (!is_dir('Panel')) {
    mkdir('Panel', 0755, true);
}
if (!is_dir('Panel/' . $table_display_name)) {
    mkdir('Panel/' . $table_display_name, 0755, true);
}

fopen("Panel/".$table_display_name."/cetak.php", "x");
$cetak  = fopen("Panel/".$table_display_name."/cetak.php", "w");
include '../data/cetak/header.php';

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
    }else if ($field_properties[$i] == "cetak") {
        include '../data/cetak/judul.php';
    }else{
        include '../data/cetak/judul.php';
    }
}

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
        include '../data/cetak/isi.php';
    }else if ($field_properties[$i] == "index") {
        include '../data/cetak/join.php';
    }else{
        }
    }

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
        include '../data/cetak/sql.php';
    }else if ($field_properties[$i] == "index") {
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
        'file_path' => 'Panel/' . $table_display_name . '/cetak.php',
        'table_name' => $table_display_name,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);
?>
