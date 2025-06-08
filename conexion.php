<?php 
include("configuracion.php");
$connect = new mysqli($server,$user,$pass,$db);
if (mysqli_connect_errno()){
    echo "La conexion a la BD **FALLO**",mysqli_connect_error();
    exit();
}
else{
    echo "<script>
            alert('Conexion exitosa');
        </script>";
}
?>