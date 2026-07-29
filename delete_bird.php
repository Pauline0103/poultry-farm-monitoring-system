<?php

include "config/database.php";

$id = $_GET['id'];

$sql = "DELETE FROM birds WHERE id='$id'";

mysqli_query($conn,$sql);

header("Location: birds.php");

exit();

?>