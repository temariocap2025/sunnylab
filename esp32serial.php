<?php
$logFile = "esp32serial.log";

if (!empty($_GET)) {
    $line = date("Y-m-d H:i:s") . " | " . $_SERVER['QUERY_STRING'] . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    echo "OK";
    exit;
}

$logs = @file($logFile);

?>

<!DOCTYPE html><html><head>
<meta charset='UTF-8'>
<title>ESP32 Serial Monitor</title>
<style>
body { font-family: monospace; background: #111; color: #0f0; padding: 10px; }
pre { white-space: pre-wrap; }
</style>
<meta http-equiv='refresh' content='5'>
</head><body>

<?php
echo "<h2>ESP32 Serial Monitor (solo se mostrarán los últimos 50 datos)</h2><pre>";
if ($logs) {
    foreach (array_slice($logs, -50) as $line) {
        echo htmlspecialchars($line);
    }
}
echo "</pre></body></html>";
?>