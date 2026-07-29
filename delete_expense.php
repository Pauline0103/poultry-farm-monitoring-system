<?php

session_start();

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

include "config/database.php";

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){

    header("Location: expenses.php");
    exit();

}

$id = (int) $_GET['id'];

$sql = "DELETE FROM expenses WHERE id = $id";

mysqli_query($conn, $sql);

header("Location: expenses.php");

exit();

?>