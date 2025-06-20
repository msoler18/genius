<?php
$dbUrl = getenv('JAWSDB_URL')
    ?: die("JAWSDB_URL no definido\n");
$url = parse_url($dbUrl);
$mysqli = new mysqli(
    $url['host'], $url['user'], $url['pass'],
    ltrim($url['path'], '/'),
    $url['port'] ?? 3306
);
if ($mysqli->connect_error) {
    die("Error conectando a MySQL: ".$mysqli->connect_error);
}

$sql = file_get_contents(__DIR__.'/demo.sql');
if (!$sql) die("No se encontró demo.sql\n");

$statements = array_filter(array_map('trim', explode(";\n", $sql)));

foreach ($statements as $stmt) {
    if (!$mysqli->query($stmt)) {
        echo "Error en sentencia:\n{$stmt}\n-> ".$mysqli->error."\n";
        exit(1);
    }
}

echo "Importación completada con éxito.\n";
