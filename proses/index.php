<?php
// Ensure Panel directory exists with proper permissions
if (!is_dir('Panel')) {
    mkdir('Panel', 0755, true);
}
if (!is_dir('Panel/' . $table_display_name)) {
    mkdir('Panel/' . $table_display_name, 0755, true);
}

fopen("Panel/".$table_display_name."/index.php", "x");
$index  = fopen("Panel/".$table_display_name."/index.php", "w");
include '../data/index/header.php';

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
    }else if ($field_properties[$i] == "index") {
        include '../data/index/judul.php';
    }else{
        include '../data/index/judul.php';
    }
}

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
        include '../data/index/isi.php';
    }else if ($field_properties[$i] == "index") {
        include '../data/index/join.php';
    }else{
        }
    }

for ($i=0; $i < $total; $i++) {
    if ($field_properties[$i] == "primary") {
        include '../data/index/sql.php';
    }else if ($field_properties[$i] == "index") {
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
        'file_path' => 'Panel/' . $table_display_name . '/index.php',
        'table_name' => $table_display_name,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);
?>
