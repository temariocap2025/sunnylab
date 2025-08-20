<div class="container-fluid dashboard-container">
    <h1 class="section-title" style="color:#3D3D3D">
        <i class="fas fa-leaf me-3"></i>
        Panel de Monitoreo Ambiental
    </h1>
    
    <?php
        include("conexion.php");
        $sql = "SELECT uv_index, ica_index, temperatura, humedad, presion, co2, co, no2, o3, covs, pm1_0, pm2_5, pm10, registro_hora 
                FROM mediciones ORDER BY id_medicion DESC LIMIT 1";
        $result = mysqli_query($connect, $sql);
        $data = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        $uv_index = $data['uv_index'] ?? 0;
        $ica_index = $data['ica_index'] ?? 0;
        $temperatura = $data['temperatura'] ?? 0;
        $humedad = $data['humedad'] ?? 0;
        $presion = $data['presion'] ?? 0;
        $co2 = $data['co2'] ?? 0;
        $co = $data['co'] ?? 0;
        $no2 = $data['no2'] ?? 0;
        $o3 = $data['o3'] ?? 0;
        $covs = $data['covs'] ?? 0;
        $pm1_0 = $data['pm1_0'] ?? 0;
        $pm2_5 = $data['pm2_5'] ?? 0;
        $pm10 = $data['pm10'] ?? 0;
        $last_update = $data['registro_hora'] ?? '';

        function getUVLevel($uv) {
            if ($uv < 3) return ["Bajo", "uv-low", "fas fa-sun"];
            elseif ($uv < 6) return ["Moderado", "uv-moderate", "fas fa-sun"];
            elseif ($uv < 8) return ["Alto", "uv-high", "fas fa-sun"];
            elseif ($uv < 11) return ["Muy Alto", "uv-very-high", "fas fa-exclamation-triangle"];
            else return ["Extremo", "uv-extreme", "fas fa-exclamation-triangle"];
        }

        function getICALevel($ica) {
            if ($ica <= 50) return ["Buena", "aqi-good", "fas fa-leaf"];
            elseif ($ica <= 100) return ["Moderada", "aqi-moderate", "fas fa-cloud"];
            elseif ($ica <= 150) return ["Poco saludable", "aqi-unhealthy-sensitive", "fas fa-exclamation"];
            elseif ($ica <= 200) return ["Dañina", "aqi-unhealthy", "fas fa-exclamation-triangle"];
            elseif ($ica <= 300) return ["Muy Dañina", "aqi-very-unhealthy", "fas fa-skull"];
            else return ["Peligrosa", "aqi-hazardous", "fas fa-skull-crossbones"];
        }

        function getPressureStatus($pressure) {
            if ($pressure < 1000) return ["Baja", "text-info", "fas fa-arrow-down"];
            elseif ($pressure > 1020) return ["Alta", "text-warning", "fas fa-arrow-up"];
            else return ["Normal", "text-success", "fas fa-equals"];
        }

        $uvData = getUVLevel($uv_index);
        $icaData = getICALevel($ica_index);
        $pressureData = getPressureStatus($presion);

        function getChartData($connect, $parameter) {
            $sql = "SELECT DATE_FORMAT(registro_hora, '%H:%i') as hora, $parameter as valor 
                    FROM mediciones 
                    WHERE registro_hora >= NOW() - INTERVAL 24 HOUR
                    ORDER BY registro_hora ASC";
            $result = mysqli_query($connect, $sql);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = floatval($row["valor"]);
            }
            return $data;
        }

        $chartData = [
            'uv_index' => getChartData($connect, 'uv_index'),
            'ica_index' => getChartData($connect, 'ica_index'),
            'temperatura' => getChartData($connect, 'temperatura'),
            'humedad' => getChartData($connect, 'humedad'),
            'presion' => getChartData($connect, 'presion'),
            'co2' => getChartData($connect, 'co2'),
            'co' => getChartData($connect, 'co'),
            'no2' => getChartData($connect, 'no2'),
            'o3' => getChartData($connect, 'o3'),
            'covs' => getChartData($connect, 'covs'),
            'pm1_0' => getChartData($connect, 'pm1_0'),
            'pm2_5' => getChartData($connect, 'pm2_5'),
            'pm10' => getChartData($connect, 'pm10')
        ];
    ?>

    <?php
    $metrics = [
        'uv_index' => ['Radiación UV', $uv_index, $uvData[2], $uvData[1], $uvData[0], '', 'chart-uv_index'],
        'ica_index' => ['Calidad del Aire', $ica_index, $icaData[2], $icaData[1], $icaData[0], '', 'chart-ica_index'],
        'temperatura' => ['Temperatura', $temperatura . '°C', 'fas fa-thermometer-half', 'text-danger', 'Actual', '', 'chart-temperatura'],
        'humedad' => ['Humedad', $humedad . '%', 'fas fa-tint', 'text-primary', 'Relativa', '', 'chart-humedad'],
        'presion' => ['Presión', $presion . ' hPa', $pressureData[2], $pressureData[1], $pressureData[0], '', 'chart-presion'],
        'co2' => ['CO2', $co2 . ' ppm', 'fas fa-smog', 'text-secondary', 'Dióxido de Carbono', '', 'chart-co2'],
        'co' => ['CO', $co . ' ppm', 'fas fa-cloud', 'text-dark', 'Monóxido de Carbono', '', 'chart-co'],
        'no2' => ['NO2', $no2 . ' ppb', 'fas fa-wind', 'text-info', 'Dióxido de Nitrógeno', '', 'chart-no2'],
        'pm1_0' => ['PM1.0', $pm1_0 . ' ug/m3', 'fas fa-circle', 'text-muted', 'Partículas Finas', '', 'chart-pm1_0'],
        'pm2_5' => ['PM2.5', $pm2_5 . ' ug/m3', 'fas fa-dot-circle', 'text-danger', 'Partículas Respirables', '', 'chart-pm2_5'],
        'pm10' => ['PM10', $pm10 . ' ug/m3', 'fas fa-circle-notch', 'text-primary', 'Partículas Inhalables', '', 'chart-pm10']
    ];
    ?>

    <div class="row">
    <?php foreach ($metrics as $key => [$label, $value, $icon, $color, $desc, $extraClass, $chartId]): ?>
        <div class="col-lg-3 col-md-6 mb-4 <?php echo $extraClass; ?>">
            <div class="card metric-card text-center h-100">
                <div class="card-body">
                    <i class="<?php echo "$icon metric-icon $color"; ?>"></i>
                    <div class="metric-value <?php echo $color; ?>"><?php echo $value; ?></div>
                    <div class="metric-label"><?php echo $label; ?></div>
                    <span class="badge status-badge bg-light text-dark"><?php echo $desc; ?></span>
                    <canvas class="mini-chart" id="<?php echo $chartId; ?>"></canvas>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="last-update">
                <i class="fas fa-clock me-2"></i>
                <strong>Última actualización:</strong> <?php echo $last_update; ?> <br>
                <small>(Los datos mostrados en las gráficas son los últimos registrados en 24 horas)</small>
            </div>
        </div>
    </div>
</div>

<?php
$colorMap = [
    'text-danger' => '#dc3545',
    'text-primary' => '#0d6efd',
    'text-success' => '#198754',
    'text-warning' => '#ffc107',
    'text-info' => '#0dcaf0',
    'text-secondary' => '#6c757d',
    'text-dark' => '#212529',
    'text-muted' => '#6c757d',

    'uv-low' => '#4CAF50',
    'uv-moderate' => '#FF9800',
    'uv-high' => '#FF5722',
    'uv-very-high' => '#E91E63',
    'uv-extreme' => '#9C27B0',
    'aqi-good' => '#4CAF50',
    'aqi-moderate' => '#FFEB3B',
    'aqi-unhealthy-sensitive' => '#FF9800',
    'aqi-unhealthy' => '#FF5722',
    'aqi-very-unhealthy' => '#9C27B0',
    'aqi-hazardous' => '#8D6E63'
];

$chartColors = [];
foreach ($metrics as $key => $metric) {
    $bootstrapClass = $metric[3];
    $chartColors[$key] = $colorMap[$bootstrapClass] ?? '#4CAF50';
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const miniCharts = {};
    const chartData = <?php echo json_encode($chartData); ?>;
    const chartColors = <?php echo json_encode($chartColors); ?>;

    function createMiniChart(canvasId, dataPoints, label, color) {
        const ctx = document.getElementById(canvasId).getContext('2d');

        if (miniCharts[canvasId]) {
            miniCharts[canvasId].destroy();
        }

        miniCharts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dataPoints.map((_, i) => i),
                datasets: [{
                    label: label,
                    data: dataPoints,
                    borderColor: color,
                    backgroundColor: color + '20',
                    borderWidth: 1.5,
                    tension: 0.4,
                    pointRadius: 0,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    }

    console.log(chartData);
    Object.keys(chartData).forEach(param => {
        const canvasId = 'chart-' + param;
        if (document.getElementById(canvasId)) {
            createMiniChart(canvasId, chartData[param], param, chartColors[param]);
        }
    });
</script>

<?php mysqli_close($connect); ?>