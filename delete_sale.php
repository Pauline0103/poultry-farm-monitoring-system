<?php

session_start();

if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}

include "config/database.php";


// Validate ID
if(
    !isset($_GET['id']) ||
    !filter_var($_GET['id'], FILTER_VALIDATE_INT) ||
    (int)$_GET['id'] <= 0
){

    header("Location: sales.php");

    exit();

}

$saleId = (int)$_GET['id'];


// Check whether the sale exists
$checkQuery = "
SELECT id
FROM sales
WHERE id = ?
LIMIT 1
";

$checkStatement = mysqli_prepare(
    $conn,
    $checkQuery
);

mysqli_stmt_bind_param(
    $checkStatement,
    "i",
    $saleId
);

mysqli_stmt_execute(
    $checkStatement
);

$result = mysqli_stmt_get_result(
    $checkStatement
);

if(mysqli_num_rows($result) == 0){

    mysqli_stmt_close($checkStatement);

    header("Location: sales.php");

    exit();

}

mysqli_stmt_close($checkStatement);


// Delete sale
$deleteQuery = "
DELETE FROM sales
WHERE id = ?
";

$deleteStatement = mysqli_prepare(
    $conn,
    $deleteQuery
);

mysqli_stmt_bind_param(
    $deleteStatement,
    "i",
    $saleId
);

if(mysqli_stmt_execute($deleteStatement)){

    mysqli_stmt_close($deleteStatement);

    header("Location: sales.php?deleted=1");

    exit();

}

mysqli_stmt_close($deleteStatement);

header("Location: sales.php?deleted=0");

exit();

?>