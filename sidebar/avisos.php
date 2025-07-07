<?php
$sql = "SELECT post_id FROM instagram ORDER BY id_instagram DESC";
$result = mysqli_query($connect, $sql);

if ($result->num_rows > 0) {
    echo '<div class="insta-container">';
    while($row = $result->fetch_assoc()) {
        $post_id = $row["post_id"];
        echo '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/'.$post_id.'/" data-instgrm-version="14"></blockquote>';
    }
    echo '</div>';
}
mysqli_close($connect);
?>
<br>
<script async src="//www.instagram.com/embed.js"></script>
