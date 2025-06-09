<div class="current">
    <div class="current-header">
        <h2 class="current-title">Datos actuales</h2>
    </div>
    
    <p style="font-size: 1.1rem; line-height: 1.6; color: #374151; margin-bottom: 2rem;">
        <?php
            $sql = "SELECT uv_index, ica_index, temperatura, humedad FROM mediciones ORDER BY id_medicion DESC LIMIT 1";
            $result = mysqli_query($connect,$sql);
            $data = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            echo "Radiación uv: {$data['uv_index']}, Calidad de aire: {$data['ica_index']}, Temperatura: {$data['temperatura']}, Humedad: {$data['humedad']}";
            echo "<br> **Work in Progress, esto se cambiará y ajustará posteriormente**";
            echo "<br> (Datos falsos de fines demostrativos)";
            mysqli_close($connect);
            ?>
    </p>
</div>