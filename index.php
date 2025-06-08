<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solmáforo Colegio Capouilliez</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="main-logo">
                <h1 class="main-title">Solmáforo</h1>
                <p class="main-subtitle">Colegio Capouilliez</p>
            </div>
        </header>

        <div class="main-layout">
            <nav class="sidebar">
                <ul class="nav-menu">
                    <li><a href="?seccion=inicio" class="nav-item <?php echo (!isset($_GET['seccion']) || $_GET['seccion'] == 'inicio') ? 'active' : ''; ?>">
                        <span class="nav-icon">🏠</span>
                        Inicio
                    </a></li>
                    <li><a href="?seccion=resumen" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'resumen') ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>
                        Resumen
                    </a></li>
                    <li><a href="?seccion=calendario" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'calendario') ? 'active' : ''; ?>">
                        <span class="nav-icon">📅</span>
                        Calendario
                    </a></li>
                    <li><a href="?seccion=recomendaciones" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'recomendaciones') ? 'active' : ''; ?>">
                        <span class="nav-icon">🛡️</span>
                        Recomendaciones
                    </a></li>
                    <li><a href="?seccion=avisos" class="nav-item <?php echo (isset($_GET['seccion']) && $_GET['seccion'] == 'avisos') ? 'active' : ''; ?>">
                        <span class="nav-icon">🔔</span>
                        Avisos
                    </a></li>
                </ul>
            </nav>

            <main class="content">
                <?php
                $seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'inicio';
                
                switch($seccion) {     
                    case 'resumen':
                        include 'sidebar/resumen.php';
                        break;
                    case 'calendario':
                        include 'sidebar/calendario.php';
                        break;
                    case 'recomendaciones':
                        include 'sidebar/recomendaciones.php';
                        break;
                    case 'avisos':
                        include 'sidebar/avisos.php';
                        break;
                    default:
                        include 'sidebar/inicio.php';
                        break;
                }
                ?>
            </main>
        </div>
    </div>
</body>
</html>