<?php

session_start();

if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}

include "config/database.php";


// Message variables
$successMessage = "";

$errorMessage = "";


// Search value
$search = trim($_GET['search'] ?? "");


// Save a new bird batch
if(isset($_POST['save'])){

    $batchName = trim(
        $_POST['batch_name'] ?? ""
    );

    $birdType = trim(
        $_POST['bird_type'] ?? ""
    );

    $quantity = trim(
        $_POST['quantity'] ?? ""
    );

    $arrivalDate = trim(
        $_POST['arrival_date'] ?? ""
    );


    // Check for empty fields
    if(
        $batchName === "" ||
        $birdType === "" ||
        $quantity === "" ||
        $arrivalDate === ""
    ){

        $errorMessage =
            "Please complete all the required fields.";

    }


    // Validate quantity
    elseif(
        !filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        ) ||
        (int) $quantity <= 0
    ){

        $errorMessage =
            "Quantity must be a whole number greater than zero.";

    }


    // Prevent future arrival dates
    elseif($arrivalDate > date("Y-m-d")){

        $errorMessage =
            "The arrival date cannot be in the future.";

    }


    else{

        $quantityNumber = (int) $quantity;


        // Secure insert statement
        $insertSql = "
            INSERT INTO birds
            (
                batch_name,
                bird_type,
                quantity,
                arrival_date
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )
        ";

        $insertStatement =
            mysqli_prepare(
                $conn,
                $insertSql
            );


        if($insertStatement){

            mysqli_stmt_bind_param(
                $insertStatement,
                "ssis",
                $batchName,
                $birdType,
                $quantityNumber,
                $arrivalDate
            );


            if(
                mysqli_stmt_execute(
                    $insertStatement
                )
            ){

                $successMessage =
                    "Bird batch saved successfully.";

            }else{

                $errorMessage =
                    "The bird batch could not be saved.";

            }

            mysqli_stmt_close(
                $insertStatement
            );

        }else{

            $errorMessage =
                "Unable to prepare the bird batch record.";

        }

    }

}


// Retrieve bird records
if($search !== ""){

    /*
    Search by batch name or bird type.
    The percentage symbols allow partial matches.
    */

    $searchValue = "%" . $search . "%";

    $recordsSql = "
        SELECT *
        FROM birds
        WHERE
            batch_name LIKE ?
            OR bird_type LIKE ?
        ORDER BY arrival_date DESC, id DESC
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );


    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "ss",
            $searchValue,
            $searchValue
        );

        mysqli_stmt_execute(
            $recordsStatement
        );

        $result =
            mysqli_stmt_get_result(
                $recordsStatement
            );

    }else{

        $result = false;

        $errorMessage =
            "The bird records could not be searched.";

    }

}else{

    // Display all records when there is no search
    $recordsSql = "
        SELECT *
        FROM birds
        ORDER BY arrival_date DESC, id DESC
    ";

    $result =
        mysqli_query(
            $conn,
            $recordsSql
        );

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Bird Management</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <h1>Bird Management</h1>

    <p>
        Add bird batches and manage existing bird records.
    </p>


    <!-- Success message -->

    <?php if($successMessage !== ""){ ?>

        <div class="form-message success-message">

            <?php
            echo htmlspecialchars(
                $successMessage
            );
            ?>

        </div>

    <?php } ?>


    <!-- Error message -->

    <?php if($errorMessage !== ""){ ?>

        <div class="form-message error-message">

            <?php
            echo htmlspecialchars(
                $errorMessage
            );
            ?>

        </div>

    <?php } ?>


    <!-- Add bird batch form -->

    <form method="POST" action="">


        <label for="batch_name">
            Batch Name
        </label>

        <input
            type="text"
            id="batch_name"
            name="batch_name"
            required
        >


        <label for="bird_type">
            Bird Type
        </label>

        <input
            type="text"
            id="bird_type"
            name="bird_type"
            required
        >


        <label for="quantity">
            Quantity
        </label>

        <input
            type="number"
            id="quantity"
            name="quantity"
            min="1"
            step="1"
            required
        >


        <label for="arrival_date">
            Arrival Date
        </label>

        <input
            type="date"
            id="arrival_date"
            name="arrival_date"
            max="<?php echo date('Y-m-d'); ?>"
            required
        >


        <br><br>


        <button
            type="submit"
            name="save"
            class="save-button"
        >

            Save Batch

        </button>


    </form>


    <br><br>


    <!-- Search section -->

    <div class="search-panel">

        <form method="GET" action="birds.php">

            <label for="search">
                Search Bird Records
            </label>

            <div class="search-row">

                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Enter batch name or bird type"
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                >

                <button
                    type="submit"
                    class="save-button"
                >
                    Search
                </button>

                <a
                    href="birds.php"
                    class="search-reset-button"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    <br>


    <h2>Bird Batch Records</h2>


    <?php if($search !== ""){ ?>

        <p class="search-result-text">

            Search results for:

            <strong>
                <?php
                echo htmlspecialchars(
                    $search
                );
                ?>
            </strong>

        </p>

    <?php } ?>


    <div class="table-responsive">

        <table
            border="1"
            cellpadding="10"
            cellspacing="0"
            width="100%"
        >

            <tr>

                <th>ID</th>

                <th>Batch Name</th>

                <th>Bird Type</th>

                <th>Quantity</th>

                <th>Arrival Date</th>

                <th>Actions</th>

            </tr>


            <?php if(
                $result &&
                mysqli_num_rows($result) > 0
            ){ ?>


                <?php while(
                    $row =
                    mysqli_fetch_assoc($result)
                ){ ?>

                    <tr>

                        <td>
                            <?php
                            echo (int) $row['id'];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['batch_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['bird_type']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo (int) $row['quantity'];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['arrival_date']
                            );
                            ?>
                        </td>

                        <td>

                            <a
                                href="edit_bird.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="delete_bird.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                                onclick="return confirm(
                                    'Are you sure you want to delete this batch?'
                                );"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php } ?>


            <?php }else{ ?>

                <tr>

                    <td
                        colspan="6"
                        class="empty-table-message"
                    >

                        <?php if($search !== ""){ ?>

                            No bird records matched your search.

                        <?php }else{ ?>

                            No bird batches have been added yet.

                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>


        </table>

    </div>


</div>


</body>

</html>