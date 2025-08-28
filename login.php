<?php
$correct_user = "temario"; // valores hardcodeados (pueden mantenerse)
$correct_pass = "temario";
$error = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST["username"]);
    $pass = trim($_POST["password"]);

    // Conexión a la base de datos
    $host = "localhost";
    $db = "db_solmaforo";
    $user_db = "root";
    $pass_db = "";

    $conn = new mysqli($host, $user_db, $pass_db, $db);
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Consulta segura usando la tabla login
    $sql = "SELECT * FROM login WHERE username=? AND password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si encuentra un usuario, login correcto
    if ($result->num_rows === 1) {
        setcookie("loggedin", "yes");
        header("Location: index.php");
        exit;
    } else {
        $error = 1; // login incorrecto
    }

    $stmt->close();
    $conn->close();
}
?>

<?php
$correct_user = "temario";
$correct_pass = "temario";
$error = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST["username"];
    $pass = $_POST["password"];

    if ($user === $correct_user && $pass === $correct_pass) {
        setcookie("loggedin", "yes");
        header("Location: index.php");
        exit;
    } else {
        $error = 1;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1"/>
        <title>SunnyLab</title>
        <link rel="stylesheet" href="styles.css">
        <link rel="icon" type="image/x-icon" href="/favicon.png">
    </head>
    <body oncontextmenu="return false">
        <div class="split left">
            <div class="centered">
                <img src="https://cdn-icons-png.flaticon.com/512/2072/2072130.png">
            </div>
        </div>

        <div class="split right">
            <div class="centered">
                <label> ¡Bienvenido de nuevo! </label> <br><br>
                <form method="post">
                    <fieldset>
                        <label for="username">Usuario: </label>
                        <input type="text" name="username" id="username" required><br><br>
                        <label for="password">Contraseña: </label>
                        <input type="password" name="password" id="password" required><br><br>
                        <?php if($error){ ?> El usuario o la contraseña son incorrectos. <br> <?php }?>
                        <button type="submit">Login</button>
                    </fieldset>
                </form>
            </div>    
        </div> 
            <script 
            disable-devtool-auto 
            disable-menu='false'
            src='https://cdn.jsdelivr.net/npm/disable-devtool'></script>
        </div>
    </body>
</html>

