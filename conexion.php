<?php 
include("configuracion.php");
$connect = new mysqli($server,$user,$pass,$db);
if (mysqli_connect_errno()){
    die("La conexión falló: " . mysqli_connect_error());
    exit();
}
else{
    echo '<script>console.log("La conexión con la base de datos fué exitosa");</script>';
}
?>