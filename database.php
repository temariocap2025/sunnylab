<?php
if (isset($_GET['send'])) {
    echo '
    <h2>Send to database</h2>
    <form method="get">
        *UV: <input type="number" step="any" name="uv" required><br><br>
        *ICA: <input type="number" step="any" name="ica" required><br><br>
        *Temperatura: <input type="number" step="any" name="temp" required><br><br>
        *Humedad: <input type="number" step="any" name="hum" required><br><br>
        *Presión: <input type="number" step="any" name="press" required><br><br>
        CO2: <input type="number" step="any" name="co2"><br><br>
        CO: <input type="number" step="any" name="co"><br><br>
        NO2: <input type="number" step="any" name="no2"><br><br>
        O3: <input type="number" step="any" name="o3"><br><br>
        COVs: <input type="number" step="any" name="covs"><br><br>
        PM1.0: <input type="number" step="any" name="pm1"><br><br>
        PM2.5: <input type="number" step="any" name="pm25"><br><br>
        PM10: <input type="number" step="any" name="pm10"><br><br>
        <input type="submit" value="Submit">
    </form>
    ';
    exit();
}

$uv = $_GET['uv'];
$ica = $_GET['ica'];
$temp = $_GET['temp'];
$hum = $_GET['hum'];
$press = $_GET['press'];
$co2  = isset($_GET['co2'])  && $_GET['co2']  !== '' ? "'" . $_GET['co2']  . "'" : "NULL";
$co   = isset($_GET['co'])   && $_GET['co']   !== '' ? "'" . $_GET['co']   . "'" : "NULL";
$no2  = isset($_GET['no2'])  && $_GET['no2']  !== '' ? "'" . $_GET['no2']  . "'" : "NULL";
$o3   = isset($_GET['o3'])   && $_GET['o3']   !== '' ? "'" . $_GET['o3']   . "'" : "NULL";
$covs = isset($_GET['covs']) && $_GET['covs'] !== '' ? "'" . $_GET['covs'] . "'" : "NULL";
$pm1  = isset($_GET['pm1'])  && $_GET['pm1']  !== '' ? "'" . $_GET['pm1']  . "'" : "NULL";
$pm25 = isset($_GET['pm25']) && $_GET['pm25'] !== '' ? "'" . $_GET['pm25'] . "'" : "NULL";
$pm10 = isset($_GET['pm10']) && $_GET['pm10'] !== '' ? "'" . $_GET['pm10'] . "'" : "NULL";


date_default_timezone_set("America/Guatemala");
$time = date("Y-m-d h:i:s");

if(isset($_GET["uv"]) && $ica && $temp && isset($_GET["hum"]) && $press){
    print(
            "UV: " . $uv . "<br>".
            "ICA: " . $ica . "<br>".
            "Temperatura: " . $temp . "<br>".
            "Humedad: " . $hum . "<br>".
            "Presión: " . $press . "<br>".
            "CO2: " . $co2 . "<br>".
            "CO: " . $co . "<br>".
            "NO2: " . $no2 . "<br>".
            "O3: " . $o3 . "<br>".
            "COVs: " . $covs . "<br>".
            "PM1.0: " . $pm1 . "<br>".
            "PM2.5: " . $pm25 . "<br>".
            "PM10: " . $pm10 . "<br>".
            "Fecha y hora: " . $time . "<br>"
        );

    include("conexion.php");
    $sql = "INSERT INTO mediciones (registro_hora, uv_index, ica_index, temperatura, humedad, presion, co2, co, no2, o3, covs, pm1_0, pm2_5, pm10)
    VALUES ('$time','$uv','$ica','$temp','$hum','$press',$co2,$co,$no2,$o3,$covs,$pm1,$pm25,$pm10)";
    $result = mysqli_query($connect,$sql);
    if($result > 0){print("<br> La información ha sido enviada a la base de datos.");}
    else{print("<br> No se pudo enviar la información a la base de datos.");}
}
else{
    if (isset($_GET["get_uv"])){
        include("conexion.php");
        $sql = "SELECT uv_index FROM mediciones ORDER BY id_medicion DESC LIMIT 1";
        $result = mysqli_query($connect, $sql);
        $data = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        print($data['uv_index']);
        flush();
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
}
?>
