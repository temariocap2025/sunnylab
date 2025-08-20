<?php
$account = $_GET['account'];

$names = [
    'colegiocapouilliez' => 'Colegio Capouilliez',
    'conredgt' => 'CONRED',
    'mineducgt' => 'MINEDUC',
    'tn23noticias' => 'TN23'
];

if ($account) {
    $sql = "SELECT post_id FROM instagram WHERE poster_name='$account' ORDER BY id_instagram DESC";
    $result = mysqli_query($connect, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo '<div class="insta-container">';
        while ($row = mysqli_fetch_assoc($result)) {
            $post_id = $row["post_id"];
            echo '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/'.$post_id.'/" data-instgrm-version="14"></blockquote>';
        }
        echo '</div>';
    }

} else {
    $sql = "SELECT DISTINCT poster_name FROM instagram ORDER BY poster_name ASC";
    $result = mysqli_query($connect, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        echo '<div class="account-buttons">';
        while ($row = mysqli_fetch_assoc($result)) {
            $name = htmlspecialchars($row['poster_name']);
            if($name){
                echo '<a href="?seccion=avisos&account='.$name.'" class="avisos-button">'.$names[$name].'</a> '; ?> <br> <?php
            }
        }
        echo '</div>';
    }else {
        echo 'No accounts found.';
    }
}

mysqli_close($connect);
?>
<br>
<script async src="//www.instagram.com/embed.js"></script>