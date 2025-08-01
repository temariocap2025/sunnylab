<?php
$correct_user = "temario";
$correct_pass = "temario";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST["username"];
    $pass = $_POST["password"];

    if ($user === $correct_user && $pass === $correct_pass) {
        setcookie("loggedin", "yes");
        header("Location: index.php");
        exit;
    } else {
        $error = "Wrong username or password.";
    }
}
?>

Inicio de sesión
<form method="post">
    <input type="text" name="username" required><br><br>
    <input type="password" name="password" required><br><br>
    <button type="submit">Login</button>
</form>