<?php
include("conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>SunnyLab</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/x-icon" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body oncontextmenu="return false">
    <div class="main-container">
        <header class="main-header">
            <button  class="open-sidebar" onclick="openNav()">&#9776;</button>
            <div class="main-logo">
                <h1 class="main-title"></h1>
                <p class="main-subtitle"></p>
            </div>
            <a class="login" href="login.php"><img class="capo-logo" src="logo.svg"></a>
        </header>

        <div class="main-layout">
            <nav id="mobile-sidebar" class="sidebar">
                <a href="javascript:void(0)" class="close-sidebar" onclick="closeNav()">X</a>    
                <ul class="nav-menu">
                    <li><a href="?seccion=inicio" class="nav-item <?php echo (!isset($_GET['seccion']) || $_GET['seccion'] == 'inicio') ? 'active' : ''; ?>">
                        <i class="bi bi-house-door-fill nav-icon"></i>
                        Inicio
                    </a></li>
                    <li><a href="?seccion=resumen" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'resumen') ? 'active' : ''; ?>">
                        <i class="bi bi-bar-chart-fill nav-icon"></i>
                        Resumen
                    </a></li>
                    <li><a href="?seccion=calendario" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'calendario') ? 'active' : ''; ?>">
                        <i class="bi bi-calendar-event-fill nav-icon"></i>
                        Calendario
                    </a></li>
                    <li><a href="?seccion=recomendaciones" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'recomendaciones') ? 'active' : ''; ?>">
                        <i class="bi bi-shield-fill-check nav-icon"></i>
                        Recomendaciones
                    </a></li>
                    <?php if(isset($_COOKIE['loggedin'])){?>
                    <li><a href="?seccion=coor-recomendaciones" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'coor-recomendaciones') ? 'active' : ''; ?>">
                        <span class="bi bi-question-circle-fill nav-icon"></span>
                        Recomendaciones para Coordinadores
                    </a></li>
                    <?php }?>
                    <li><a href="?seccion=avisos" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'avisos') ? 'active' : ''; ?>">
                        <span class="bi bi-bell-fill nav-icon"></span>
                        Avisos
                    </a></li>
                </ul>
            </nav>

            <main class="content">
                <?php
                $seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'inicio';
                include "sidebar/$seccion.php";
                ?>
            </main>
        </div>
    <script>
        function openNav() {document.getElementById("mobile-sidebar").style.width = "100%"}
        function closeNav() {document.getElementById("mobile-sidebar").style.width = "0"}        
    </script>
    <!-- <script 
    disable-devtool-auto 
    disable-menu='false'
    src='https://cdn.jsdelivr.net/npm/disable-devtool'></script> -->
    </div>
    <?php if (isset($_GET['seccion']) && $_GET['seccion'] === 'resumen' || $_GET['seccion'] === 'calendario'): ?>
        <script>
            setInterval(function() {
            location.reload();
        }, 60000);
        </script>
    <?php endif; ?>
</body>
</html>