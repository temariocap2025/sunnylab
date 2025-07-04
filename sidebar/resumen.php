<div class="main-wrapper">
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
            
            $hoursBack = intval($timeRange);
            
            switch ($dataType) {
                case "uv_index":
                    $column = "uv_index";
                    $label = "Radiación UV";
                    break;
                case "ica_index":
                    $column = "ica_index";
                    $label = "Calidad Aire";
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

            $sql = "SELECT DATE_FORMAT(registro_hora, '%H:%i') as hora, $column as valor 
                    FROM mediciones 
                    ORDER BY registro_hora ASC";

            $result = $connect->query($sql);

            $data = [];
            $data[] = ["Hora", $label];

            while ($row = $result->fetch_assoc()) {
                $data[] = [$row["hora"], floatval($row["valor"])];
            }

            echo "<script>const serverData = " . json_encode($data) . ";</script>";
        ?>          
        <div class="chart-controls">
            <label for="dataType">Mostrar: </label>
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

        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
            google.charts.load("current", { packages: ["corechart"] });
            google.charts.setOnLoadCallback(initChart);
            const sampleData = serverData;

            function initChart() {
                document.getElementById("dataType").addEventListener("change", function() {
                    window.location.href = "?seccion=resumen" + "&dataType=" + this.value + "&timeRange=" + document.getElementById("timeRange").value;
                });
                document.getElementById("timeRange").addEventListener("change", function() {
                    window.location.href = "?seccion=resumen" + "&dataType=" + document.getElementById("dataType").value + "&timeRange=" + this.value;
                });
                
                document.getElementById("dataType").addEventListener("change", drawChart);
                document.getElementById("timeRange").addEventListener("change", drawChart);
                drawChart();
            }

            function drawChart() {
                const chartData = google.visualization.arrayToDataTable(sampleData);

                const options = {
                    title: "Datos desde la base",
                    width: 600,
                    height: 400,
                    legend: { position: "none" },
                    bar: { groupWidth: "90%" },
                    hAxis: { title: 'Hora' },
                    vAxis: { title: 'Valor' }
                };

                const chart = new google.visualization.ColumnChart(document.getElementById("columnchart_values"));
                chart.draw(chartData, options);
            }

        </script>
        <div id="columnchart_values"></div>
    </div>

</div>

<?php mysqli_close($connect); ?>