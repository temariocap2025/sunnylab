<?php
$uv = $_GET['uv'];
$ica = $_GET['ica'];
$temp = $_GET['temp'];
$hum = $_GET['hum'];
date_default_timezone_set("America/Guatemala");
$time = date("Y-m-d h:i:s");

if($uv && $ica && $temp && $hum){
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
}
else{
    
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    include("conexion.php");

    try {
        $sql = "SELECT * FROM mediciones";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($result);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "error" => $e->getMessage()
        ]);
    }
}
?>