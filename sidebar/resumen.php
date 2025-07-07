<div class="summary-wrapper">
    <div class="current">
        <div class="current-header">
            <h2 class="current-title">Datos actuales</h2>
        </div>
        
        <div class="current-table">
            <?php
                $sql = "SELECT uv_index, ica_index, temperatura, humedad FROM mediciones ORDER BY id_medicion DESC LIMIT 1";
                $result = mysqli_query($connect, $sql);
                $data = mysqli_fetch_assoc($result);
                mysqli_free_result($result);

                $uv_index = $data['uv_index'];
                $ica_index = $data['ica_index'];
                $temperatura = $data['temperatura'];
                $humedad = $data['humedad'];

                function getUVLevel($uv) {
                    if ($uv < 3) return "Bajo ($uv)";
                    elseif ($uv < 6) return "Moderado ($uv)";
                    elseif ($uv < 8) return "Alto ($uv)";
                    elseif ($uv < 11) return "Muy Alto ($uv)";
                    else return "Extremo ($uv)";
                }

                function getICALevel($ica) {
                    if ($ica <= 50) return "Buena (ICA $ica)";
                    elseif ($ica <= 100) return "Moderada (ICA $ica)";
                    elseif ($ica <= 150) return "Poco saludable (ICA $ica)";
                    elseif ($ica <= 200) return "Dañina (ICA $ica)";
                    elseif ($ica <= 300) return "Muy Dañina (ICA $ica)";
                    else return "Peligrosa (ICA $ica)";
                }   
            ?>
            <table>
                <tr>
                    <th>Radiación UV</th>
                    <th>Calidad de aire</th>
                    <th>Temperatura</th>
                    <th>Humedad</th>
                </tr>
                <tr>
                    <td><?php echo getUVLevel($uv_index); ?></td>
                    <td><?php echo getICALevel($ica_index); ?></td>
                    <td><?php echo $temperatura . " °C"; ?></td>
                    <td><?php echo $humedad . " %"; ?></td>
                </tr>
            </table>
        </div>

        <?php
            $sql = "SELECT registro_hora FROM mediciones ORDER BY id_medicion DESC LIMIT 1";
            $result = mysqli_query($connect, $sql);
            $last_time = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            echo "<br> Última actualización: " . $last_time["registro_hora"];
        ?>
    </div>

    <div class="res-chart">
        <?php
            $dataType = isset($_GET['dataType']) ? $_GET['dataType'] : 'uv_index';
            $timeRange = isset($_GET['timeRange']) ? $_GET['timeRange'] : '12';

            switch ($dataType) {
                case "uv_index":
                    $column = "uv_index";
                    $label = "Radiación UV";
                    break;
                case "ica_index":
                    $column = "ica_index";
                    $label = "Calidad de Aire";
                    break;
                case "temperatura":
                    $column = "temperatura";
                    $label = "Temperatura";
                    break;
                case "humedad":
                    $column = "humedad";
                    $label = "Humedad";
                    break;
                default:
                    $column = "uv_index";
                    $label = "Radiación UV";
            }

            $hoursBack = intval($timeRange);
            $sql = "SELECT DATE_FORMAT(registro_hora, '%H:%i') as hora, $column as valor 
                    FROM mediciones 
                    WHERE registro_hora >= NOW() - INTERVAL $hoursBack HOUR
                    ORDER BY registro_hora ASC";
            $result = mysqli_query($connect, $sql);
            $labels = [];
            $values = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row["hora"];
                $values[] = floatval($row["valor"]);
            }

            echo "<script>
                const chartLabels = " . json_encode($labels) . ";
                const chartValues = " . json_encode($values) . ";
                const chartLabel = " . json_encode($label) . ";
            </script>";
        ?>
        <div class="chart-controls">
            <label for="dataType">Datos: </label>
            <select id="dataType">
                <option value="uv_index" <?= $dataType == 'uv_index' ? 'selected' : '' ?>>Radiación UV</option>
                <option value="ica_index" <?= $dataType == 'ica_index' ? 'selected' : '' ?>>Calidad del Aire</option>
                <option value="temperatura" <?= $dataType == 'temperatura' ? 'selected' : '' ?>>Temperatura</option>
                <option value="humedad" <?= $dataType == 'humedad' ? 'selected' : '' ?>>Humedad</option>
            </select>

            <label for="timeRange">Rango de tiempo: </label>
            <select id="timeRange">
                <option value="12" <?= $timeRange == '12' ? 'selected' : '' ?>>Últimas 12 horas</option>
                <option value="24" <?= $timeRange == '24' ? 'selected' : '' ?>>Últimas 24 horas</option>
                <option value="168" <?= $timeRange == '168' ? 'selected' : '' ?>>Últimos 7 días</option>
                <option value="720" <?= $timeRange == '720' ? 'selected' : '' ?>>Últimos 30 días</option>
            </select>
        </div>

        <canvas id="myChart" width="600" height="400"></canvas>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.getElementById("dataType").addEventListener("change", function() {
                window.location.href = "?seccion=resumen" + "&dataType=" + this.value + "&timeRange=" + document.getElementById("timeRange").value;
            });
            document.getElementById("timeRange").addEventListener("change", function() {
                window.location.href = "?seccion=resumen" + "&dataType=" + document.getElementById("dataType").value + "&timeRange=" + this.value;
            });

            const ctx = document.getElementById('myChart').getContext('2d');

            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: chartLabel,
                        data: chartValues,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            title: { display: true, text: 'Hora' }
                        },
                        y: {
                            title: { display: true, text: 'Valor' },
                            beginAtZero: true
                        }
                    },
                },
            });
        </script>
    </div>
<?php mysqli_close($connect); ?>
</div>