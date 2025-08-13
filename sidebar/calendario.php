<?php
// Get current month and year, or from URL parameters
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
if ($current_month < 1 || $current_month > 12) $current_month = date('n');
if ($current_year < 2020 || $current_year > 2030) $current_year = date('Y');

// Get month name in Spanish
$spanish_months = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$month_name = $spanish_months[$current_month];

// Calculate calendar data
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // 0 = Sunday, 1 = Monday, etc.

// Get daily averages from database - ALL PARAMETERS
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

// Define normal ranges for each parameter
function isParameterAbnormal($param_name, $value) {
    if ($value === null || $value == 0) return false; // No data doesn't count as abnormal
    
    $normal_ranges = [
        'uv_index' => ['min' => 0, 'max' => 7],        // UV Index: 0-7 normal, 8+ high
        'ica_index' => ['min' => 0, 'max' => 50],      // ICA: 0-50 good, 51+ moderate/unhealthy
        'temperatura' => ['min' => 15, 'max' => 35],   // Temperature: 15-35°C normal
        'humedad' => ['min' => 30, 'max' => 70],       // Humidity: 30-70% normal
        'presion' => ['min' => 1000, 'max' => 1030],   // Pressure: 1000-1030 hPa normal
        'co2' => ['min' => 0, 'max' => 1000],          // CO2: 0-1000 ppm normal
        'co' => ['min' => 0, 'max' => 10],             // CO: 0-10 mg/m³ normal
        'no2' => ['min' => 0, 'max' => 40],            // NO2: 0-40 µg/m³ normal
        'o3' => ['min' => 0, 'max' => 120],            // O3: 0-120 µg/m³ normal
        'covs' => ['min' => 0, 'max' => 300],          // COVs: 0-300 µg/m³ normal
        'pm1_0' => ['min' => 0, 'max' => 25],          // PM1.0: 0-25 µg/m³ normal
        'pm2_5' => ['min' => 0, 'max' => 25],          // PM2.5: 0-25 µg/m³ normal
        'pm10' => ['min' => 0, 'max' => 50]            // PM10: 0-50 µg/m³ normal
    ];
    
    if (!isset($normal_ranges[$param_name])) return false;
    
    $range = $normal_ranges[$param_name];
    return $value < $range['min'] || $value > $range['max'];
}

// Function to determine circle color based on number of abnormal parameters
function getCircleColorByAbnormals($daily_data) {
    if ($daily_data['total_readings'] == 0) return 'no-data';
    
    $parameters = [
        'avg_uv' => 'uv_index',
        'avg_ica' => 'ica_index', 
        'avg_temp' => 'temperatura',
        'avg_humidity' => 'humedad',
        'avg_pressure' => 'presion',
        'avg_co2' => 'co2',
        'avg_co' => 'co',
        'avg_no2' => 'no2',
        'avg_o3' => 'o3',
        'avg_covs' => 'covs',
        'avg_pm1' => 'pm1_0',
        'avg_pm25' => 'pm2_5',
        'avg_pm10' => 'pm10'
    ];
    
    $total_params = 0;
    $abnormal_count = 0;
    
    foreach ($parameters as $db_field => $param_name) {
        if ($daily_data[$db_field] !== null && $daily_data[$db_field] != 0) {
            $total_params++;
            if (isParameterAbnormal($param_name, $daily_data[$db_field])) {
                $abnormal_count++;
            }
        }
    }
    
    if ($total_params == 0) return 'no-data';
    
    $abnormal_percentage = $abnormal_count / $total_params;
    
    if ($abnormal_percentage == 0) return 'good';           // All normal - Green
    if ($abnormal_percentage <= 0.5) return 'moderate';    // Half or less abnormal - Yellow
    return 'unhealthy';                                     // Most abnormal - Red
}
?>

<div class="container-fluid dashboard-container">
    <div class="calendar-header mb-4">
        <div class="row align-items-center justify-content-center">
            <div class="col-auto">
                <select class="form-select month-selector" onchange="changeMonth(this.value)">
                    <?php foreach ($spanish_months as $num => $name): ?>
                        <option value="<?php echo $num; ?>" <?php echo ($num == $current_month) ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select class="form-select year-selector" onchange="changeYear(this.value)">
                    <?php for ($year = 2020; $year <= 2030; $year++): ?>
                        <option value="<?php echo $year; ?>" <?php echo ($year == $current_year) ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="calendar-container">
        <div class="calendar-grid">
            <!-- Day headers -->
            <div class="calendar-weekdays">
                <div class="weekday-header">DOM</div>
                <div class="weekday-header">LUN</div>
                <div class="weekday-header">MAR</div>
                <div class="weekday-header">MIÉ</div>
                <div class="weekday-header">JUE</div>
                <div class="weekday-header">VIE</div>
                <div class="weekday-header">SÁB</div>
            </div>

            <!-- Calendar days -->
            <div class="calendar-days">
                <?php
                // Empty cells for days before month starts
                for ($i = 0; $i < $start_day; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }

                // Days of the month
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $daily_data = getDailyAverages($connect, $current_year, str_pad($current_month, 2, '0', STR_PAD_LEFT), str_pad($day, 2, '0', STR_PAD_LEFT));
                    $circle_color = getCircleColorByAbnormals($daily_data);
                    $has_data = $daily_data['total_readings'] > 0;
                    
                    echo '<div class="calendar-day" data-day="' . $day . '">';
                    echo '<div class="day-number">' . $day . '</div>';
                    
                    if ($has_data) {
                        // Store all data attributes
                        $data_attrs = '';
                        $data_attrs .= ' data-uv="' . number_format($daily_data['avg_uv'], 1) . '"';
                        $data_attrs .= ' data-ica="' . number_format($daily_data['avg_ica'], 1) . '"';
                        $data_attrs .= ' data-temp="' . number_format($daily_data['avg_temp'], 1) . '"';
                        $data_attrs .= ' data-humidity="' . number_format($daily_data['avg_humidity'], 1) . '"';
                        $data_attrs .= ' data-pressure="' . number_format($daily_data['avg_pressure'], 1) . '"';
                        $data_attrs .= ' data-co2="' . number_format($daily_data['avg_co2'], 1) . '"';
                        $data_attrs .= ' data-co="' . number_format($daily_data['avg_co'], 1) . '"';
                        $data_attrs .= ' data-no2="' . number_format($daily_data['avg_no2'], 1) . '"';
                        $data_attrs .= ' data-o3="' . number_format($daily_data['avg_o3'], 1) . '"';
                        $data_attrs .= ' data-covs="' . number_format($daily_data['avg_covs'], 1) . '"';
                        $data_attrs .= ' data-pm1="' . number_format($daily_data['avg_pm1'], 1) . '"';
                        $data_attrs .= ' data-pm25="' . number_format($daily_data['avg_pm25'], 1) . '"';
                        $data_attrs .= ' data-pm10="' . number_format($daily_data['avg_pm10'], 1) . '"';
                        $data_attrs .= ' data-date="' . $day . '/' . $current_month . '/' . $current_year . '"';
                        
                        echo '<div class="data-circle ' . $circle_color . '"' . $data_attrs . '></div>';
                    }
                    
                    echo '</div>';
                }

                // Fill remaining cells
                $total_cells = $start_day + $days_in_month;
                $remaining_cells = 42 - $total_cells; // 6 weeks * 7 days
                for ($i = 0; $i < $remaining_cells && $i < 7; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="calendar-legend mt-4">
        <div class="row justify-content-center">
            <div class="col-auto">
                <div class="legend-item">
                    <div class="legend-circle good"></div>
                    <span>Todos los Parámetros Normales</span>
                </div>
            </div>
            <div class="col-auto">
                <div class="legend-item">
                    <div class="legend-circle moderate"></div>
                    <span>Algunos Parámetros Anómalos</span>
                </div>
            </div>
            <div class="col-auto">
                <div class="legend-item">
                    <div class="legend-circle unhealthy"></div>
                    <span>Mayoría de Parámetros Anómalos</span>
                </div>
            </div>
            <div class="col-auto">
                <div class="legend-item">
                    <div class="legend-circle no-data"></div>
                    <span>Sin Datos</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Popup Modal -->
<div id="dataPopup" class="data-popup">
    <div class="popup-content">
        <div class="popup-header">
            <h5 id="popupTitle">Datos de Calidad del Aire</h5>
            <button type="button" class="popup-close">&times;</button>
        </div>
        <div class="popup-body">
            <div class="data-grid">
                <div class="data-group">
                    <h6>Índices de Calidad</h6>
                    <div class="data-item">
                        <span class="param-name">UV Index:</span>
                        <span class="param-value" id="popup-uv">--</span>
                        <span class="param-unit"></span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">ICA Index:</span>
                        <span class="param-value" id="popup-ica">--</span>
                        <span class="param-unit"></span>
                    </div>
                </div>

                <div class="data-group">
                    <h6>Condiciones Ambientales</h6>
                    <div class="data-item">
                        <span class="param-name">Temperatura:</span>
                        <span class="param-value" id="popup-temp">--</span>
                        <span class="param-unit">°C</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">Humedad:</span>
                        <span class="param-value" id="popup-humidity">--</span>
                        <span class="param-unit">%</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">Presión:</span>
                        <span class="param-value" id="popup-pressure">--</span>
                        <span class="param-unit">hPa</span>
                    </div>
                </div>

                <div class="data-group">
                    <h6>Gases</h6>
                    <div class="data-item">
                        <span class="param-name">CO₂:</span>
                        <span class="param-value" id="popup-co2">--</span>
                        <span class="param-unit">ppm</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">CO:</span>
                        <span class="param-value" id="popup-co">--</span>
                        <span class="param-unit">mg/m³</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">NO₂:</span>
                        <span class="param-value" id="popup-no2">--</span>
                        <span class="param-unit">µg/m³</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">O₃:</span>
                        <span class="param-value" id="popup-o3">--</span>
                        <span class="param-unit">µg/m³</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">COVs:</span>
                        <span class="param-value" id="popup-covs">--</span>
                        <span class="param-unit">µg/m³</span>
                    </div>
                </div>

                <div class="data-group">
                    <h6>Material Particulado</h6>
                    <div class="data-item">
                        <span class="param-name">PM1.0:</span>
                        <span class="param-value" id="popup-pm1">--</span>
                        <span class="param-unit">µg/m³</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">PM2.5:</span>
                        <span class="param-value" id="popup-pm25">--</span>
                        <span class="param-unit">µg/m³</span>
                    </div>
                    <div class="data-item">
                        <span class="param-name">PM10:</span>
                        <span class="param-value" id="popup-pm10">--</span>
                        <span class="param-unit">µg/m³</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Normal ranges for parameter validation
const normalRanges = {
    uv: { min: 0, max: 7 },
    ica: { min: 0, max: 50 },
    temp: { min: 15, max: 35 },
    humidity: { min: 30, max: 70 },
    pressure: { min: 1000, max: 1030 },
    co2: { min: 0, max: 1000 },
    co: { min: 0, max: 10 },
    no2: { min: 0, max: 40 },
    o3: { min: 0, max: 120 },
    covs: { min: 0, max: 300 },
    pm1: { min: 0, max: 25 },
    pm25: { min: 0, max: 25 },
    pm10: { min: 0, max: 50 }
};

function isParameterNormal(param, value) {
    if (!normalRanges[param] || value === null || value === '' || value === '--') return true;
    const range = normalRanges[param];
    const numValue = parseFloat(value);
    return numValue >= range.min && numValue <= range.max;
}

function changeMonth(month) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('month', month);
    window.location.href = currentUrl.toString();
}

function changeYear(year) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('year', year);
    window.location.href = currentUrl.toString();
}

function showDataPopup(circleElement) {
    const popup = document.getElementById('dataPopup');
    const title = document.getElementById('popupTitle');
    
    // Get data from attributes
    const date = circleElement.getAttribute('data-date');
    const uv = circleElement.getAttribute('data-uv') || '--';
    const ica = circleElement.getAttribute('data-ica') || '--';
    const temp = circleElement.getAttribute('data-temp') || '--';
    const humidity = circleElement.getAttribute('data-humidity') || '--';
    const pressure = circleElement.getAttribute('data-pressure') || '--';
    const co2 = circleElement.getAttribute('data-co2') || '--';
    const co = circleElement.getAttribute('data-co') || '--';
    const no2 = circleElement.getAttribute('data-no2') || '--';
    const o3 = circleElement.getAttribute('data-o3') || '--';
    const covs = circleElement.getAttribute('data-covs') || '--';
    const pm1 = circleElement.getAttribute('data-pm1') || '--';
    const pm25 = circleElement.getAttribute('data-pm25') || '--';
    const pm10 = circleElement.getAttribute('data-pm10') || '--';
    
    // Set title
    title.textContent = `Datos de Calidad del Aire - ${date}`;
    
    // Populate data with color coding
    const parameters = [
        { id: 'popup-uv', value: uv, param: 'uv' },
        { id: 'popup-ica', value: ica, param: 'ica' },
        { id: 'popup-temp', value: temp, param: 'temp' },
        { id: 'popup-humidity', value: humidity, param: 'humidity' },
        { id: 'popup-pressure', value: pressure, param: 'pressure' },
        { id: 'popup-co2', value: co2, param: 'co2' },
        { id: 'popup-co', value: co, param: 'co' },
        { id: 'popup-no2', value: no2, param: 'no2' },
        { id: 'popup-o3', value: o3, param: 'o3' },
        { id: 'popup-covs', value: covs, param: 'covs' },
        { id: 'popup-pm1', value: pm1, param: 'pm1' },
        { id: 'popup-pm25', value: pm25, param: 'pm25' },
        { id: 'popup-pm10', value: pm10, param: 'pm10' }
    ];
    
    parameters.forEach(param => {
        const element = document.getElementById(param.id);
        element.textContent = param.value;
        
        // Add color coding based on normal/abnormal status
        element.className = 'param-value';
        if (param.value !== '--') {
            if (isParameterNormal(param.param, param.value)) {
                element.classList.add('normal');
            } else {
                element.classList.add('abnormal');
            }
        }
    });
    
    // Show popup
    popup.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

function hideDataPopup() {
    const popup = document.getElementById('dataPopup');
    popup.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
}

document.addEventListener('DOMContentLoaded', function() {
    // Add click events to data circles
    document.querySelectorAll('.data-circle').forEach(function(circle) {
        circle.addEventListener('click', function(e) {
            e.stopPropagation();
            showDataPopup(this);
        });
    });
    
    // Close popup events
    document.querySelector('.popup-close').addEventListener('click', hideDataPopup);
    
    document.getElementById('dataPopup').addEventListener('click', function(e) {
        if (e.target === this) {
            hideDataPopup();
        }
    });
    
    // Close popup with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideDataPopup();
        }
    });
    
    // Enhanced hover effects
    document.querySelectorAll('.calendar-day').forEach(function(day) {
        if (!day.classList.contains('empty')) {
            day.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            
            day.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        }
    });
});
</script>

<?php mysqli_close($connect); ?>