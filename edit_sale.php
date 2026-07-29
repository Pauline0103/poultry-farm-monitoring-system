<?php

session_start();


// Protect the page
if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}


include "config/database.php";


// Message variables
$errorMessage = "";

$successMessage = "";


// Validate the sale ID from the URL
if(
    !isset($_GET['id']) ||
    !filter_var(
        $_GET['id'],
        FILTER_VALIDATE_INT
    ) ||
    (int) $_GET['id'] <= 0
){

    header("Location: sales.php");

    exit();

}


$saleId = (int) $_GET['id'];


// Retrieve the existing sale
$saleQuery = "
    SELECT
        id,
        customer_name,
        bird_batch,
        birds_sold,
        price_per_bird,
        total_amount,
        sale_date
    FROM sales
    WHERE id = ?
    LIMIT 1
";

$saleStatement = mysqli_prepare(
    $conn,
    $saleQuery
);


if(!$saleStatement){

    die("The sale record could not be prepared.");

}


mysqli_stmt_bind_param(
    $saleStatement,
    "i",
    $saleId
);

mysqli_stmt_execute(
    $saleStatement
);

$saleResult = mysqli_stmt_get_result(
    $saleStatement
);

$saleRecord = mysqli_fetch_assoc(
    $saleResult
);

mysqli_stmt_close(
    $saleStatement
);


// Stop if the sale does not exist
if(!$saleRecord){

    header("Location: sales.php");

    exit();

}


// Store the existing values
$customerName = $saleRecord['customer_name'];

$birdBatch = $saleRecord['bird_batch'];

$birdsSold = $saleRecord['birds_sold'];

$pricePerBird = $saleRecord['price_per_bird'];

$saleDate = $saleRecord['sale_date'];


// Process the update form
if(isset($_POST['update'])){


    // Receive and clean form values
    $customerName = trim(
        $_POST['customer_name'] ?? ""
    );

    $birdBatch = trim(
        $_POST['bird_batch'] ?? ""
    );

    $birdsSold = trim(
        $_POST['birds_sold'] ?? ""
    );

    $pricePerBird = trim(
        $_POST['price_per_bird'] ?? ""
    );

    $saleDate = trim(
        $_POST['sale_date'] ?? ""
    );


    // Required-field validation
    if(
        $customerName === "" ||
        $birdBatch === "" ||
        $birdsSold === "" ||
        $pricePerBird === "" ||
        $saleDate === ""
    ){

        $errorMessage =
            "Please complete all the required fields.";

    }


    // Customer-name validation
    elseif(strlen($customerName) < 2){

        $errorMessage =
            "Customer name must contain at least two characters.";

    }


    // Birds-sold validation
    elseif(
        filter_var(
            $birdsSold,
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $birdsSold <= 0
    ){

        $errorMessage =
            "Birds sold must be a whole number greater than zero.";

    }


    // Price validation
    elseif(
        !is_numeric($pricePerBird) ||
        (float) $pricePerBird <= 0
    ){

        $errorMessage =
            "Price per bird must be greater than zero.";

    }


    // Date validation
    elseif(
        !DateTime::createFromFormat(
            "Y-m-d",
            $saleDate
        )
    ){

        $errorMessage =
            "Please provide a valid sale date.";

    }


    // Future-date validation
    elseif($saleDate > date("Y-m-d")){

        $errorMessage =
            "The sale date cannot be in the future.";

    }


    else{


        $birdsSoldNumber = (int) $birdsSold;

        $pricePerBirdNumber =
            (float) $pricePerBird;


        /*
        Start a transaction so that stock checking
        and updating happen as one controlled process.
        */
        mysqli_begin_transaction($conn);


        // Lock and retrieve the selected batch
        $batchQuery = "
            SELECT
                id,
                batch_name,
                quantity
            FROM birds
            WHERE batch_name = ?
            LIMIT 1
            FOR UPDATE
        ";

        $batchStatement = mysqli_prepare(
            $conn,
            $batchQuery
        );


        if(!$batchStatement){

            mysqli_rollback($conn);

            $errorMessage =
                "The selected batch could not be checked.";

        }else{


            mysqli_stmt_bind_param(
                $batchStatement,
                "s",
                $birdBatch
            );

            mysqli_stmt_execute(
                $batchStatement
            );

            $batchResult = mysqli_stmt_get_result(
                $batchStatement
            );

            $batchRecord = mysqli_fetch_assoc(
                $batchResult
            );

            mysqli_stmt_close(
                $batchStatement
            );


            // Confirm the batch exists
            if(!$batchRecord){

                mysqli_rollback($conn);

                $errorMessage =
                    "The selected bird batch does not exist.";

            }else{


                $batchQuantity =
                    (int) $batchRecord['quantity'];


                /*
                Calculate birds sold from this batch.

                The current sale is excluded because
                we are replacing its old quantity.
                */
                $soldQuery = "
                    SELECT
                        COALESCE(
                            SUM(birds_sold),
                            0
                        ) AS total_sold
                    FROM sales
                    WHERE bird_batch = ?
                    AND id != ?
                ";

                $soldStatement = mysqli_prepare(
                    $conn,
                    $soldQuery
                );


                if(!$soldStatement){

                    mysqli_rollback($conn);

                    $errorMessage =
                        "Previous sales could not be checked.";

                }else{


                    mysqli_stmt_bind_param(
                        $soldStatement,
                        "si",
                        $birdBatch,
                        $saleId
                    );

                    mysqli_stmt_execute(
                        $soldStatement
                    );

                    $soldResult = mysqli_stmt_get_result(
                        $soldStatement
                    );

                    $soldRecord = mysqli_fetch_assoc(
                        $soldResult
                    );

                    $otherSales =
                        (int) (
                            $soldRecord['total_sold'] ?? 0
                        );

                    mysqli_stmt_close(
                        $soldStatement
                    );


                    // Calculate mortality for the selected batch
                    $mortalityQuery = "
                        SELECT
                            COALESCE(
                                SUM(number_dead),
                                0
                            ) AS total_dead
                        FROM mortality
                        WHERE bird_batch = ?
                    ";

                    $mortalityStatement =
                        mysqli_prepare(
                            $conn,
                            $mortalityQuery
                        );


                    if(!$mortalityStatement){

                        mysqli_rollback($conn);

                        $errorMessage =
                            "Mortality records could not be checked.";

                    }else{


                        mysqli_stmt_bind_param(
                            $mortalityStatement,
                            "s",
                            $birdBatch
                        );

                        mysqli_stmt_execute(
                            $mortalityStatement
                        );

                        $mortalityResult =
                            mysqli_stmt_get_result(
                                $mortalityStatement
                            );

                        $mortalityRecord =
                            mysqli_fetch_assoc(
                                $mortalityResult
                            );

                        $totalDead =
                            (int) (
                                $mortalityRecord['total_dead'] ?? 0
                            );

                        mysqli_stmt_close(
                            $mortalityStatement
                        );


                        /*
                        Calculate how many birds can be assigned
                        to the sale being edited.
                        */
                        $availableForEditedSale =
                            $batchQuantity -
                            $otherSales -
                            $totalDead;


                        if($availableForEditedSale < 0){

                            $availableForEditedSale = 0;

                        }


                        // Prevent assigning too many birds
                        if(
                            $birdsSoldNumber >
                            $availableForEditedSale
                        ){

                            mysqli_rollback($conn);

                            $errorMessage =
                                "You cannot change this sale to " .
                                $birdsSoldNumber .
                                " birds. Only " .
                                $availableForEditedSale .
                                " bird(s) are available for this sale in " .
                                htmlspecialchars(
                                    $birdBatch
                                ) .
                                ".";

                        }else{


                            // Recalculate the total amount
                            $totalAmount =
                                $birdsSoldNumber *
                                $pricePerBirdNumber;


                            // Update the sale safely
                            $updateQuery = "
                                UPDATE sales
                                SET
                                    customer_name = ?,
                                    bird_batch = ?,
                                    birds_sold = ?,
                                    price_per_bird = ?,
                                    total_amount = ?,
                                    sale_date = ?
                                WHERE id = ?
                            ";

                            $updateStatement =
                                mysqli_prepare(
                                    $conn,
                                    $updateQuery
                                );


                            if(!$updateStatement){

                                mysqli_rollback($conn);

                                $errorMessage =
                                    "The sale update could not be prepared.";

                            }else{


                                mysqli_stmt_bind_param(
                                    $updateStatement,
                                    "ssiddsi",
                                    $customerName,
                                    $birdBatch,
                                    $birdsSoldNumber,
                                    $pricePerBirdNumber,
                                    $totalAmount,
                                    $saleDate,
                                    $saleId
                                );


                                if(
                                    mysqli_stmt_execute(
                                        $updateStatement
                                    )
                                ){

                                    mysqli_commit($conn);

                                    mysqli_stmt_close(
                                        $updateStatement
                                    );


                                    /*
                                    Return to the sales page after
                                    a successful update.
                                    */
                                    header(
                                        "Location: sales.php?updated=1"
                                    );

                                    exit();

                                }else{

                                    mysqli_rollback($conn);

                                    $errorMessage =
                                        "The sale could not be updated. Please try again.";

                                    mysqli_stmt_close(
                                        $updateStatement
                                    );

                                }

                            }

                        }

                    }

                }

            }

        }

    }

}


// Retrieve bird batches for the dropdown
$batchListQuery = "
    SELECT
        batch_name,
        quantity
    FROM birds
    ORDER BY arrival_date DESC, id DESC
";

$batchListResult = mysqli_query(
    $conn,
    $batchListQuery
);

?>

<!DOCTYPE html>

<html>

<head>

    <title>Edit Sale</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<?php include "includes/sidebar.php"; ?>


<div class="content">

    <h1>Edit Sale</h1>

    <p>
        Update the selected sales record.
    </p>


    <?php if($errorMessage !== ""){ ?>

        <div class="form-message error-message">

            <?php echo $errorMessage; ?>

        </div>

    <?php } ?>


    <?php if($successMessage !== ""){ ?>

        <div class="form-message success-message">

            <?php echo $successMessage; ?>

        </div>

    <?php } ?>


    <form method="POST" action="">


        <label for="customer_name">
            Customer Name
        </label>

        <input
            type="text"
            id="customer_name"
            name="customer_name"
            value="<?php
                echo htmlspecialchars(
                    $customerName
                );
            ?>"
            minlength="2"
            maxlength="100"
            required
        >


        <label for="bird_batch">
            Bird Batch
        </label>

        <select
            id="bird_batch"
            name="bird_batch"
            required
        >

            <option value="">
                Select a bird batch
            </option>


            <?php if($batchListResult){ ?>

                <?php while(
                    $batch = mysqli_fetch_assoc(
                        $batchListResult
                    )
                ){ ?>

                    <option
                        value="<?php
                            echo htmlspecialchars(
                                $batch['batch_name']
                            );
                        ?>"
                        <?php
                        if(
                            $birdBatch ===
                            $batch['batch_name']
                        ){

                            echo "selected";

                        }
                        ?>
                    >

                        <?php
                        echo htmlspecialchars(
                            $batch['batch_name']
                        );
                        ?>

                        — Originally

                        <?php
                        echo (int) $batch['quantity'];
                        ?>

                        bird(s)

                    </option>

                <?php } ?>

            <?php } ?>

        </select>


        <p class="form-help">

            The system will check the available birds before
            updating the sale.

        </p>


        <label for="birds_sold">
            Birds Sold
        </label>

        <input
            type="number"
            id="birds_sold"
            name="birds_sold"
            value="<?php
                echo htmlspecialchars(
                    $birdsSold
                );
            ?>"
            min="1"
            step="1"
            required
        >


        <label for="price_per_bird">
            Price Per Bird (K)
        </label>

        <input
            type="number"
            id="price_per_bird"
            name="price_per_bird"
            value="<?php
                echo htmlspecialchars(
                    $pricePerBird
                );
            ?>"
            min="0.01"
            step="0.01"
            required
        >


        <label for="sale_date">
            Sale Date
        </label>

        <input
            type="date"
            id="sale_date"
            name="sale_date"
            value="<?php
                echo htmlspecialchars(
                    $saleDate
                );
            ?>"
            max="<?php echo date('Y-m-d'); ?>"
            required
        >


        <br><br>


        <button
            type="submit"
            name="update"
            class="save-button"
        >

            Update Sale

        </button>


        <a
            href="sales.php"
            class="cancel-button"
        >

            Cancel

        </a>

    </form>

</div>

</body>

</html>