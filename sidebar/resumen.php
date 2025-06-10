<div class="current">
    <div class="current-header">
        <h2 class="current-title">Datos actuales</h2>
    </div>
    
    <div class="current-table">
        <?php
            $sql = "SELECT uv_index, ica_index, temperatura, humedad FROM mediciones ORDER BY id_medicion DESC LIMIT 1";
            $result = mysqli_query($connect,$sql);
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
    $result = mysqli_query($connect,$sql);
    $last_time = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    echo "<br> Útima actualización: " . $last_time["registro_hora"];

    mysqli_close($connect);
    ?>
</div>