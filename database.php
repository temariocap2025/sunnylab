<?php
ini_set('display_errors', 0);
function errHandle($errNo, $errStr, $errFile, $errLine) {
    $msg = "$errStr in $errFile on line $errLine";
    if ($errNo == E_NOTICE || $errNo == E_WARNING) {
        throw new ErrorException($msg, $errNo);
    } else {
        echo $msg;
    }
}
set_error_handler('errHandle');

$uv = $_GET['uv'];
$ica = $_GET['ica'];
$temp = $_GET['temp'];
$hum = $_GET['hum'];
date_default_timezone_set("America/Guatemala");
$time = date("Y-m-d h:i:s");

print(
    "UV: " . $uv . "<br>".
    "ICA: " . $ica . "<br>".
    "Temperatura: " . $temp . "<br>".
    "Humedad: " . $hum . "<br>".
    "Fecha y hora: " . $time . "<br>"
);

include("conexion.php");
$sql = "INSERT INTO mediciones(registro_hora, uv_index, ica_index, temperatura, humedad) 
VALUES ('$time','$uv','$ica','$temp','$hum')";
$result = mysqli_query($connect,$sql);
if($result > 0){print("<br> La información ha sido enviada a la base de datos.");}
else{print("<br> No se pudo enviar la información a la base de datos.");}

?>