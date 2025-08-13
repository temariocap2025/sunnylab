<?php
function getDailyAverages($connect, $year, $month, $day) {
    $sql = "SELECT 
                AVG(uv_index) as avg_uv,
                AVG(ica_index) as avg_ica,
                AVG(temperatura) as avg_temp,
                AVG(humedad) as avg_humidity,
                AVG(presion) as avg_pressure,
                AVG(co2) as avg_co2,
                AVG(co) as avg_co,
                AVG(no2) as avg_no2,
                AVG(o3) as avg_o3,
                AVG(covs) as avg_covs,
                AVG(pm1_0) as avg_pm1,
                AVG(pm2_5) as avg_pm25,
                AVG(pm10) as avg_pm10,
                COUNT(*) as total_readings
            FROM mediciones 
            WHERE DATE(registro_hora) = '$year-$month-$day'";
    
    $result = mysqli_query($connect, $sql);
    $data = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    
    return $data;
}

date_default_timezone_set("America/Guatemala");

$api_key = trim(file_get_contents('apikey.txt'));
?>

<div class="advices-alumnos">
    
    <div style="font-size: 1.1rem; line-height: 1.6; color: #374151; margin-bottom: 2rem;">
        <?php
        require "vendor/autoload.php";
        use GeminiAPI\Client;
        use GeminiAPI\Resources\ModelName;
        use GeminiAPI\Resources\Parts\TextPart;

        $client = new Client($api_key);
        $response = $client->withV1BetaVersion()
            ->generativeModel(ModelName::GEMINI_1_5_FLASH)
            ->withSystemInstruction('Estás dandole recomendaciones a un coordinador de un colegio a base del promedio de valores ambientales de una base de datos del presente día,
            no des detalles técnicos ya que es parte de un proyecto de tesis, 
            aparecerá en una página web dentro de un div por lo que tienes la libertad de usar etiquetas html, 
            el título tendrá que contener esto: font-size: 2rem; font-weight: bold; color: #2563eb; 
            No lo hagas tan largo pero tampoco tan corto,
            Empieza el mensaje después del título con algo cercano a: "Estimado coordinador, a continuación, se presentan algunas recomendaciones basadas 
            en el promedio de datos ambientales recopilados el día de hoy. Estos datos fueron procesados por una IA y reflejan las condiciones generales.
            Tenga en cuenta que se trata de valores promedios y las condiciones pueden variar a lo largo del día. "
            Trata de no escribir ```html```')
            ->generateContent(
                new TextPart(json_encode(getDailyAverages($connect, date('Y'), date('m'), date('d')))),
            );

        echo $response->text();
        ?>

</div>