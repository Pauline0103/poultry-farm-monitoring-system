<?php

session_start();


// Protect page
if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}


include "config/database.php";


// Validate expense ID
$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


if(!$id || $id < 1){

    header("Location: expenses.php");

    exit();

}


// Delete expense securely
$deleteSql = "
    DELETE FROM expenses
    WHERE id = ?
";

$deleteStatement =
    mysqli_prepare(
        $conn,
        $deleteSql
    );


if(!$deleteStatement){

    header("Location: expenses.php");

    exit();

}


mysqli_stmt_bind_param(
    $deleteStatement,
    "i",
    $id
);


if(
    mysqli_stmt_execute(
        $deleteStatement
    )
){

    mysqli_stmt_close(
        $deleteStatement
    );

    header(
        "Location: expenses.php?deleted=1"
    );

    exit();

}


mysqli_stmt_close(
    $deleteStatement
);


header("Location: expenses.php");

exit();

?>