<?php

include "config/database.php";

$id = $_GET['id'];

$sql = "DELETE FROM feed WHERE id='$id'";

mysqli_query($conn,$sql);

header("Location: feed.php");

exit();

?>