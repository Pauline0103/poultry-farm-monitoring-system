<?php

session_start();


// Protect the page
if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}


include "config/database.php";


// Validate feed ID
$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


if(!$id || $id < 1){

    header("Location: feed.php");

    exit();

}


mysqli_begin_transaction(
    $conn
);


try{

    // Delete the linked expense first
    $expenseDeleteSql = "
        DELETE FROM expenses
        WHERE feed_id = ?
    ";

    $expenseDeleteStatement =
        mysqli_prepare(
            $conn,
            $expenseDeleteSql
        );


    if(!$expenseDeleteStatement){

        throw new Exception(
            "Unable to prepare the linked expense deletion."
        );

    }


    mysqli_stmt_bind_param(
        $expenseDeleteStatement,
        "i",
        $id
    );


    if(
        !mysqli_stmt_execute(
            $expenseDeleteStatement
        )
    ){

        throw new Exception(
            "The linked feed expense could not be deleted."
        );

    }


    mysqli_stmt_close(
        $expenseDeleteStatement
    );


    // Delete the feed record
    $feedDeleteSql = "
        DELETE FROM feed
        WHERE id = ?
    ";

    $feedDeleteStatement =
        mysqli_prepare(
            $conn,
            $feedDeleteSql
        );


    if(!$feedDeleteStatement){

        throw new Exception(
            "Unable to prepare the feed deletion."
        );

    }


    mysqli_stmt_bind_param(
        $feedDeleteStatement,
        "i",
        $id
    );


    if(
        !mysqli_stmt_execute(
            $feedDeleteStatement
        )
    ){

        throw new Exception(
            "The feed record could not be deleted."
        );

    }


    mysqli_stmt_close(
        $feedDeleteStatement
    );


    mysqli_commit(
        $conn
    );


    header(
        "Location: feed.php?deleted=1"
    );

    exit();


}catch(Exception $exception){

    mysqli_rollback(
        $conn
    );


    header(
        "Location: feed.php?delete_error=1"
    );

    exit();

}

?>