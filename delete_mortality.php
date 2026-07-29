<?php

session_start();

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

include "config/database.php";

if(isset($_GET['id'])){

    $id = (int)$_GET['id'];

    mysqli_query($conn,
    "DELETE FROM mortality WHERE id=$id");

}

header("Location: mortality.php");

exit();

?>