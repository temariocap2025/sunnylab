<?php
include("conexion.php");

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

<style>
.calendar-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

.month-selector, .year-selector {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 8px 15px;
    font-weight: 500;
    color: #333;
    min-width: 120px;
}

.month-selector:focus, .year-selector:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.calendar-grid {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
    margin-bottom: 10px;
}

.weekday-header {
    text-align: center;
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
    padding: 8px;
    background: rgba(240, 240, 240, 0.8);
    border-radius: 6px;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    position: relative;
    transition: all 0.2s ease;
    min-height: 80px;
    border: 1px solid #e0e0e0;
    padding: 8px 4px;
    cursor: pointer;
}

.calendar-day:not(.empty):hover {
    transform: scale(1.05);
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    z-index: 10;
    border-color: #007bff;
}

.calendar-day.empty {
    background: transparent;
    border: none;
    cursor: default;
}

.day-number {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
    line-height: 1;
}

.data-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    transition: all 0.2s ease;
    margin-bottom: 4px;
    flex-shrink: 0;
    cursor: pointer;
}

.calendar-day:hover .data-circle {
    width: 32px;
    height: 32px;
}

.data-circle.good {
    background: #7CB342; /* Green - All parameters normal */
}

.data-circle.moderate {
    background: #FFA726; /* Orange - Some parameters abnormal */
}

.data-circle.unhealthy {
    background: #EF5350; /* Red - Most parameters abnormal */
}

.data-circle.no-data {
    background: #E0E0E0;
    border: 2px dashed #999;
}

.calendar-legend {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 10px;
    padding: 15px;
    text-align: center;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0 15px;
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
}

.legend-circle {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    flex-shrink: 0;
}

.legend-circle.good {
    background: #7CB342;
}

.legend-circle.moderate {
    background: #FFA726;
}

.legend-circle.unhealthy {
    background: #EF5350;
}

.legend-circle.no-data {
    background: #E0E0E0;
    border: 2px dashed #999;
}

/* Enhanced Popup Styles */
.data-popup {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.popup-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow: hidden;
    animation: popupSlideIn 0.3s ease-out;
}

@keyframes popupSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.popup-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.popup-header h5 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.popup-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.popup-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.popup-body {
    padding: 25px;
    max-height: 60vh;
    overflow-y: auto;
}

.data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.data-group {
    background: rgba(248, 249, 250, 0.8);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e9ecef;
}

.data-group h6 {
    color: #495057;
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #dee2e6;
}

.data-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.data-item:last-child {
    border-bottom: none;
}

.param-name {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.95rem;
}

.param-value {
    font-weight: 700;
    color: #212529;
    font-size: 1rem;
    min-width: 50px;
    text-align: right;
}

.param-unit {
    font-size: 0.85rem;
    color: #868e96;
    font-weight: 400;
    min-width: 40px;
    text-align: left;
    margin-left: 5px;
}

/* Parameter value colors based on status */
.param-value.normal {
    color: #28a745;
}

.param-value.abnormal {
    color: #dc3545;
    font-weight: 800;
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 45px;
        padding: 2px 1px;
    }
    
    .day-number {
        font-size: 0.75rem;
        margin-bottom: 2px;
    }
    
    .data-circle {
        width: 16px;
        height: 16px;
    }
    
    .calendar-day:hover .data-circle {
        width: 18px;
        height: 18px;
    }
    
    .legend-item {
        margin: 5px 8px;
        font-size: 0.75rem;
    }
    
    .popup-content {
        margin: 5% auto;
        width: 95%;
        max-height: 90vh;
    }
    
    .data-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .popup-body {
        padding: 10px;
        max-height: 70vh;
    }
    
    .data-group {
        padding: 10px;
    }
    
    .data-group h6 {
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .data-item {
        padding: 4px 0;
    }
    
    .param-name {
        font-size: 0.8rem;
    }
    
    .param-value {
        font-size: 0.85rem;
    }
    
    .param-unit {
        font-size: 0.75rem;
    }
    
    .popup-header {
        padding: 12px;
    }
    
    .popup-header h5 {
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .calendar-container {
        padding: 10px;
    }
    
    .calendar-days {
        gap: 2px;
    }
    
    .calendar-weekdays {
        gap: 2px;
        margin-bottom: 5px;
    }
    
    .weekday-header {
        font-size: 0.7rem;
        padding: 4px 1px;
    }
    
    .calendar-day {
        min-height: 35px;
        padding: 2px 1px;
    }
    
    .day-number {
        font-size: 0.65rem;
        margin-bottom: 1px;
    }
    
    .data-circle {
        width: 12px;
        height: 12px;
    }
    
    .calendar-day:hover .data-circle {
        width: 14px;
        height: 14px;
    }
    
    .legend-item {
        margin: 3px 5px;
        font-size: 0.7rem;
    }
    
    .legend-circle {
        width: 12px;
        height: 12px;
    }
    
    .month-selector, .year-selector {
        min-width: 90px;
        padding: 6px 10px;
        font-size: 0.85rem;
    }
    
    .calendar-legend {
        padding: 10px;
    }
    
    .popup-content {
        margin: 2% auto;
        width: 98%;
        max-height: 95vh;
    }
    
    .popup-header {
        padding: 8px;
    }
    
    .popup-header h5 {
        font-size: 1rem;
    }
    
    .popup-body {
        padding: 8px;
        max-height: 75vh;
    }
    
    .data-group {
        padding: 8px;
    }
    
    .data-group h6 {
        font-size: 0.85rem;
        margin-bottom: 6px;
    }
    
    .data-item {
        padding: 3px 0;
    }
    
    .param-name {
        font-size: 0.75rem;
    }
    
    .param-value {
        font-size: 0.8rem;
    }
    
    .param-unit {
        font-size: 0.7rem;
    }
}
</style>

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