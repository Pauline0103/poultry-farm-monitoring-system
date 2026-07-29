<?php

session_start();

if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}

include "config/database.php";


// Variables for messages
$successMessage = "";

$errorMessage = "";

// Search and filter values
$search = trim($_GET['search'] ?? "");

$filterDate = trim($_GET['filter_date'] ?? "");

$sort = $_GET['sort'] ?? "newest";

if(
    isset($_GET['updated']) &&
    $_GET['updated'] === "1"
){

    $successMessage =
        "Sale updated successfully.";

}

if(
    isset($_GET['deleted']) &&
    $_GET['deleted'] == "1"
){

    $successMessage =
        "Sale deleted successfully.";

}

if(
    isset($_GET['deleted']) &&
    $_GET['deleted'] == "0"
){

    $errorMessage =
        "Unable to delete the sale.";

}


// Variables used to preserve form information
$customerName = "";

$birdBatch = "";

$birdsSold = "";

$pricePerBird = "";

$saleDate = "";


// Record a new sale
if(isset($_POST['save'])){


    // Receive and clean form information
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


    // Validate required fields
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


    // Validate customer name
    elseif(strlen($customerName) < 2){

        $errorMessage =
            "Customer name must contain at least two characters.";

    }


    // Validate birds sold
    elseif(
        !filter_var(
            $birdsSold,
            FILTER_VALIDATE_INT
        ) ||
        (int) $birdsSold <= 0
    ){

        $errorMessage =
            "Birds sold must be a whole number greater than zero.";

    }


    // Validate price per bird
    elseif(
        !is_numeric($pricePerBird) ||
        (float) $pricePerBird <= 0
    ){

        $errorMessage =
            "Price per bird must be greater than zero.";

    }


    // Validate sale date
    elseif(
        !DateTime::createFromFormat(
            'Y-m-d',
            $saleDate
        )
    ){

        $errorMessage =
            "Please provide a valid sale date.";

    }


    // Prevent future sale dates
    elseif($saleDate > date('Y-m-d')){

        $errorMessage =
            "The sale date cannot be in the future.";

    }


    else{


        // Convert numeric values to correct data types
        $birdsSoldNumber =
            (int) $birdsSold;

        $pricePerBirdNumber =
            (float) $pricePerBird;


        /*
        Start a database transaction.

        This helps prevent two sales from changing
        the same batch stock at exactly the same time.
        */
        mysqli_begin_transaction($conn);


        // Find the selected bird batch
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

        $batchStatement =
            mysqli_prepare(
                $conn,
                $batchQuery
            );


        if(!$batchStatement){

            mysqli_rollback($conn);

            $errorMessage =
                "The batch could not be checked. Please try again.";

        }else{


            mysqli_stmt_bind_param(
                $batchStatement,
                "s",
                $birdBatch
            );

            mysqli_stmt_execute(
                $batchStatement
            );

            $batchResult =
                mysqli_stmt_get_result(
                    $batchStatement
                );

            $batchRecord =
                mysqli_fetch_assoc(
                    $batchResult
                );

            mysqli_stmt_close(
                $batchStatement
            );


            // Confirm that the selected batch exists
            if(!$batchRecord){

                mysqli_rollback($conn);

                $errorMessage =
                    "The selected bird batch does not exist.";

            }else{


                $batchQuantity =
                    (int) $batchRecord['quantity'];


                // Calculate birds already sold from this batch
                $soldQuery = "
                    SELECT
                        COALESCE(
                            SUM(birds_sold),
                            0
                        ) AS total_sold
                    FROM sales
                    WHERE bird_batch = ?
                ";

                $soldStatement =
                    mysqli_prepare(
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
                        "s",
                        $birdBatch
                    );

                    mysqli_stmt_execute(
                        $soldStatement
                    );

                    $soldResult =
                        mysqli_stmt_get_result(
                            $soldStatement
                        );

                    $soldRecord =
                        mysqli_fetch_assoc(
                            $soldResult
                        );

                    $alreadySold =
                        (int) (
                            $soldRecord['total_sold'] ?? 0
                        );

                    mysqli_stmt_close(
                        $soldStatement
                    );


                    // Calculate mortality for this batch
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

                        $alreadyDead =
                            (int) (
                                $mortalityRecord['total_dead'] ?? 0
                            );

                        mysqli_stmt_close(
                            $mortalityStatement
                        );


                        // Calculate available birds
                        $availableBirds =
                            $batchQuantity -
                            $alreadySold -
                            $alreadyDead;


                        if($availableBirds < 0){

                            $availableBirds = 0;

                        }


                        // Prevent sale when no birds remain
                        if($availableBirds === 0){

                            mysqli_rollback($conn);

                            $errorMessage =
                                "No birds are available in the selected batch.";

                        }


                        // Prevent selling more than available stock
                        elseif(
                            $birdsSoldNumber >
                            $availableBirds
                        ){

                            mysqli_rollback($conn);

                            $errorMessage =
                                "You cannot sell " .
                                $birdsSoldNumber .
                                " birds. Only " .
                                $availableBirds .
                                " bird(s) remain in " .
                                htmlspecialchars(
                                    $birdBatch
                                ) .
                                ".";

                        }


                        else{


                            // Calculate the sale total
                            $totalAmount =
                                $birdsSoldNumber *
                                $pricePerBirdNumber;


                            // Insert the validated sale
                            $insertQuery = "
                                INSERT INTO sales
                                (
                                    customer_name,
                                    bird_batch,
                                    birds_sold,
                                    price_per_bird,
                                    total_amount,
                                    sale_date
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )
                            ";

                            $insertStatement =
                                mysqli_prepare(
                                    $conn,
                                    $insertQuery
                                );


                            if(!$insertStatement){

                                mysqli_rollback($conn);

                                $errorMessage =
                                    "The sale could not be prepared.";

                            }else{


                                mysqli_stmt_bind_param(
                                    $insertStatement,
                                    "ssidds",
                                    $customerName,
                                    $birdBatch,
                                    $birdsSoldNumber,
                                    $pricePerBirdNumber,
                                    $totalAmount,
                                    $saleDate
                                );


                                if(
                                    mysqli_stmt_execute(
                                        $insertStatement
                                    )
                                ){

                                    mysqli_commit($conn);

                                    $successMessage =
                                        "Sale recorded successfully. " .
                                        $birdsSoldNumber .
                                        " bird(s) were sold from " .
                                        htmlspecialchars(
                                            $birdBatch
                                        ) .
                                        ".";


                                    // Clear the form after successful insertion
                                    $customerName = "";

                                    $birdBatch = "";

                                    $birdsSold = "";

                                    $pricePerBird = "";

                                    $saleDate = "";

                                }else{

                                    mysqli_rollback($conn);

                                    $errorMessage =
                                        "The sale could not be saved. Please try again.";

                                }


                                mysqli_stmt_close(
                                    $insertStatement
                                );

                            }

                        }

                    }

                }

            }

        }

    }

}


// Retrieve bird batches for the dropdown menu
$batchListQuery = "
    SELECT
        batch_name,
        quantity
    FROM birds
    ORDER BY arrival_date DESC, id DESC
";

$batchListResult =
    mysqli_query(
        $conn,
        $batchListQuery
    );

?>

<!DOCTYPE html>

<html>

<head>

    <title>Sales Management</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<?php include "includes/sidebar.php"; ?>


<div class="content">

    <h1>Sales Management</h1>

    <p>
        Record poultry sales and monitor customer transactions.
    </p>


    <!-- Success message -->

    <?php if($successMessage !== ""){ ?>

        <div class="form-message success-message">

            <?php echo $successMessage; ?>

        </div>

    <?php } ?>


    <!-- Error message -->

    <?php if($errorMessage !== ""){ ?>

        <div class="form-message error-message">

            <?php echo $errorMessage; ?>

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


            <?php if(
                $batchListResult &&
                mysqli_num_rows(
                    $batchListResult
                ) > 0
            ){ ?>


                <?php while(
                    $batch =
                    mysqli_fetch_assoc(
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


        <?php if(
            !$batchListResult ||
            mysqli_num_rows(
                $batchListResult
            ) === 0
        ){ ?>

            <p class="form-help error-text">

                No bird batches are available.

                <a href="birds.php">
                    Add a bird batch first.
                </a>

            </p>

        <?php }else{ ?>

            <p class="form-help">

                Select the batch from which the birds were sold.
                The system will automatically check its available stock.

            </p>

        <?php } ?>


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
            name="save"
            class="save-button"
            <?php
            if(
                !$batchListResult ||
                mysqli_num_rows(
                    $batchListResult
                ) === 0
            ){

                echo "disabled";

            }
            ?>
        >

            Record Sale

        </button>

    </form>


    <br><br>


    <div class="records-heading">

        <div>


        <div class="search-panel">

<form method="GET">

<div class="search-grid">

<div>

<label>Search Customer</label>

<input
type="text"
name="search"
placeholder="Customer name..."
value="<?php echo htmlspecialchars($search); ?>">

</div>

<div>

<label>Sale Date</label>

<input
type="date"
name="filter_date"
value="<?php echo htmlspecialchars($filterDate); ?>">

</div>

<div>

<label>Sort</label>

<select name="sort">

<option
value="newest"
<?php if($sort=="newest") echo "selected"; ?>>

Newest First

</option>

<option
value="oldest"
<?php if($sort=="oldest") echo "selected"; ?>>

Oldest First

</option>

</select>

</div>

</div>

<br>

<button
class="save-button"
type="submit">

Search

</button>

<a
href="sales.php"
class="cancel-button">

Clear

</a>

</form>

</div>

<br>
            <h2>Sales Records</h2>

            <p>
                All sales recorded in the system.
            </p>

        </div>

    </div>


    <div class="table-responsive">

        <table
            border="1"
            cellpadding="10"
            cellspacing="0"
            width="100%"
        >

            <tr>

                <th>ID</th>
                <th>Customer</th>
                <th>Bird Batch</th>
                <th>Birds Sold</th>
                <th>Price Per Bird</th>
                <th>Total Amount</th>
                <th>Sale Date</th>
                <th>Actions</th>

            </tr>


            <?php

            $salesQuery = "
SELECT *
FROM sales
WHERE 1=1
";

if($search != ""){

    $search =
    mysqli_real_escape_string(
        $conn,
        $search
    );

    $salesQuery .= "
    AND
    customer_name
    LIKE '%$search%'
    ";

}

if($filterDate != ""){

    $filterDate =
    mysqli_real_escape_string(
        $conn,
        $filterDate
    );

    $salesQuery .= "
    AND
    sale_date='$filterDate'
    ";

}

if($sort=="oldest"){

    $salesQuery .= "
    ORDER BY sale_date ASC,id ASC
    ";

}else{

    $salesQuery .= "
    ORDER BY sale_date DESC,id DESC
    ";

}

            $salesResult =
                mysqli_query(
                    $conn,
                    $salesQuery
                );

            ?>


            <?php if(
                $salesResult &&
                mysqli_num_rows(
                    $salesResult
                ) > 0
            ){ ?>


                <?php while(
                    $row =
                    mysqli_fetch_assoc(
                        $salesResult
                    )
                ){ ?>

                    <tr>

                        <td>
                            <?php echo (int) $row['id']; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['customer_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['bird_batch']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo (int) $row['birds_sold'];
                            ?>
                        </td>

                        <td>
                            K<?php
                            echo number_format(
                                $row['price_per_bird'],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            K<?php
                            echo number_format(
                                $row['total_amount'],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['sale_date']
                            );
                            ?>
                        </td>

                        <td>

                            <a
                                href="edit_sale.php?id=<?php
                                    echo (int) $row['id'];
                                ?>"
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="delete_sale.php?id=<?php
                                    echo (int) $row['id'];
                                ?>"
                               onclick="return confirm(
'Delete this sale permanently? This action cannot be undone.'
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
                        colspan="8"
                        class="empty-table-message"
                    >

                        No sales records have been added yet.

                    </td>

                </tr>

            <?php } ?>


        </table>

    </div>

</div>

</body>

</html>