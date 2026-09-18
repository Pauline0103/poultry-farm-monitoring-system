<?php

session_start();


// Protect the page
if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}


include "config/database.php";


$errorMessage = "";


// Validate feed record ID
$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


if(!$id || $id < 1){

    header("Location: feed.php");

    exit();

}


// Retrieve the feed record securely
$selectSql = "
    SELECT
        id,
        feed_name,
        quantity,
        price,
        supplier,
        purchase_date
    FROM feed
    WHERE id = ?
    LIMIT 1
";

$selectStatement =
    mysqli_prepare(
        $conn,
        $selectSql
    );


if(!$selectStatement){

    header("Location: feed.php");

    exit();

}


mysqli_stmt_bind_param(
    $selectStatement,
    "i",
    $id
);

mysqli_stmt_execute(
    $selectStatement
);

$selectResult =
    mysqli_stmt_get_result(
        $selectStatement
    );

$row =
    mysqli_fetch_assoc(
        $selectResult
    );

mysqli_stmt_close(
    $selectStatement
);


// Record does not exist
if(!$row){

    header("Location: feed.php");

    exit();

}


// Preserve current values
$feedName =
    $row['feed_name'];

$quantity =
    $row['quantity'];

$price =
    $row['price'];

$supplier =
    $row['supplier'];

$purchaseDate =
    $row['purchase_date'];


// Update feed record
if(isset($_POST['update'])){

    $feedName = trim(
        $_POST['feed_name'] ?? ""
    );

    $quantity = trim(
        $_POST['quantity'] ?? ""
    );

    $price = trim(
        $_POST['price'] ?? ""
    );

    $supplier = trim(
        $_POST['supplier'] ?? ""
    );

    $purchaseDate = trim(
        $_POST['purchase_date'] ?? ""
    );


    // Required fields
    if(
        $feedName === "" ||
        $quantity === "" ||
        $price === "" ||
        $supplier === "" ||
        $purchaseDate === ""
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


    // Validate price per bag
    elseif(
        !is_numeric($price) ||
        (float) $price <= 0
    ){

        $errorMessage =
            "Price per bag must be greater than zero.";

    }


    // Validate purchase date
    elseif(
        !DateTime::createFromFormat(
            "Y-m-d",
            $purchaseDate
        ) ||
        DateTime::createFromFormat(
            "Y-m-d",
            $purchaseDate
        )->format("Y-m-d") !== $purchaseDate
    ){

        $errorMessage =
            "Please provide a valid purchase date.";

    }


    // Prevent future purchase date
    elseif(
        $purchaseDate >
        date("Y-m-d")
    ){

        $errorMessage =
            "The purchase date cannot be in the future.";

    }


    else{

        $quantityNumber =
            (int) $quantity;

        $priceNumber =
            (float) $price;

        $totalFeedCost =
            $quantityNumber *
            $priceNumber;


        mysqli_begin_transaction(
            $conn
        );


        try{

            // =====================================
            // Update Feed Management record
            // =====================================

            $updateSql = "
                UPDATE feed
                SET
                    feed_name = ?,
                    quantity = ?,
                    price = ?,
                    supplier = ?,
                    purchase_date = ?
                WHERE id = ?
            ";

            $updateStatement =
                mysqli_prepare(
                    $conn,
                    $updateSql
                );


            if(!$updateStatement){

                throw new Exception(
                    "Unable to prepare the feed update."
                );

            }


            mysqli_stmt_bind_param(
                $updateStatement,
                "sidssi",
                $feedName,
                $quantityNumber,
                $priceNumber,
                $supplier,
                $purchaseDate,
                $id
            );


            if(
                !mysqli_stmt_execute(
                    $updateStatement
                )
            ){

                throw new Exception(
                    "The feed record could not be updated."
                );

            }


            mysqli_stmt_close(
                $updateStatement
            );


            // =====================================
            // Prepare linked expense information
            // =====================================

            $expenseName =
                $feedName .
                " feed purchase";

            $expenseCategory =
                "Feed";

            $expenseDescription =
                $quantityNumber .
                " bag(s) at K" .
                number_format(
                    $priceNumber,
                    2
                ) .
                " per bag from " .
                $supplier;


            // =====================================
            // Check for existing linked expense
            // =====================================

            $checkExpenseSql = "
                SELECT id
                FROM expenses
                WHERE feed_id = ?
                LIMIT 1
            ";

            $checkExpenseStatement =
                mysqli_prepare(
                    $conn,
                    $checkExpenseSql
                );


            if(!$checkExpenseStatement){

                throw new Exception(
                    "Unable to check the linked feed expense."
                );

            }


            mysqli_stmt_bind_param(
                $checkExpenseStatement,
                "i",
                $id
            );

            mysqli_stmt_execute(
                $checkExpenseStatement
            );

            $checkExpenseResult =
                mysqli_stmt_get_result(
                    $checkExpenseStatement
                );

            $linkedExpense =
                mysqli_fetch_assoc(
                    $checkExpenseResult
                );

            mysqli_stmt_close(
                $checkExpenseStatement
            );


            // =====================================
            // Update linked expense if it exists
            // =====================================

            if($linkedExpense){

                $expenseUpdateSql = "
                    UPDATE expenses
                    SET
                        expense_name = ?,
                        expense_category = ?,
                        amount = ?,
                        expense_date = ?,
                        description = ?
                    WHERE feed_id = ?
                ";

                $expenseUpdateStatement =
                    mysqli_prepare(
                        $conn,
                        $expenseUpdateSql
                    );


                if(!$expenseUpdateStatement){

                    throw new Exception(
                        "Unable to prepare the linked expense update."
                    );

                }


                mysqli_stmt_bind_param(
                    $expenseUpdateStatement,
                    "ssdssi",
                    $expenseName,
                    $expenseCategory,
                    $totalFeedCost,
                    $purchaseDate,
                    $expenseDescription,
                    $id
                );


                if(
                    !mysqli_stmt_execute(
                        $expenseUpdateStatement
                    )
                ){

                    throw new Exception(
                        "The linked feed expense could not be updated."
                    );

                }


                mysqli_stmt_close(
                    $expenseUpdateStatement
                );

            }


            // =====================================
            // Create linked expense for older
            // Feed records that do not have one
            // =====================================

            else{

                $expenseInsertSql = "
                    INSERT INTO expenses
                    (
                        feed_id,
                        expense_name,
                        expense_category,
                        amount,
                        expense_date,
                        description
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

                $expenseInsertStatement =
                    mysqli_prepare(
                        $conn,
                        $expenseInsertSql
                    );


                if(!$expenseInsertStatement){

                    throw new Exception(
                        "Unable to prepare the feed expense."
                    );

                }


                mysqli_stmt_bind_param(
                    $expenseInsertStatement,
                    "issdss",
                    $id,
                    $expenseName,
                    $expenseCategory,
                    $totalFeedCost,
                    $purchaseDate,
                    $expenseDescription
                );


                if(
                    !mysqli_stmt_execute(
                        $expenseInsertStatement
                    )
                ){

                    throw new Exception(
                        "The feed expense could not be created."
                    );

                }


                mysqli_stmt_close(
                    $expenseInsertStatement
                );

            }


            mysqli_commit(
                $conn
            );


            header(
                "Location: feed.php?updated=1"
            );

            exit();


        }catch(Exception $exception){

            mysqli_rollback(
                $conn
            );

            $errorMessage =
                $exception->getMessage();

        }

    }

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

    <title>Edit Feed Record</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <div class="page-header clean-page-header">

        <div class="page-header-content">

            <h1>Edit Feed Record</h1>

            <p>
                Update feed purchase information.
            </p>

        </div>

    </div>


    <?php if($errorMessage !== ""){ ?>

        <div class="form-message error-message">

            <?php
            echo htmlspecialchars(
                $errorMessage
            );
            ?>

        </div>

    <?php } ?>


    <div class="module-card">

        <div class="module-card-header">

            <div>

                <h2>Feed Details</h2>

                <p>
                    Make the required changes
                    and save the updated record.
                </p>

            </div>

        </div>


        <form
            method="POST"
            action=""
            class="modern-module-form"
        >

            <div class="form-grid">


                <div class="form-field">

                    <label for="feed_name">
                        Feed Name
                    </label>

                    <input
                        type="text"
                        id="feed_name"
                        name="feed_name"
                        value="<?php
                        echo htmlspecialchars(
                            $feedName
                        );
                        ?>"
                        required
                    >

                </div>


                <div class="form-field">

                    <label for="supplier">
                        Supplier
                    </label>

                    <input
                        type="text"
                        id="supplier"
                        name="supplier"
                        value="<?php
                        echo htmlspecialchars(
                            $supplier
                        );
                        ?>"
                        required
                    >

                </div>


                <div class="form-field">

                    <label for="quantity">
                        Quantity (Bags)
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        value="<?php
                        echo htmlspecialchars(
                            $quantity
                        );
                        ?>"
                        min="1"
                        step="1"
                        required
                    >

                </div>


                <div class="form-field">

                    <label for="price">
                        Price per Bag (K)
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        value="<?php
                        echo htmlspecialchars(
                            $price
                        );
                        ?>"
                        min="0.01"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-field">

                    <label for="purchase_date">
                        Purchase Date
                    </label>

                    <input
                        type="date"
                        id="purchase_date"
                        name="purchase_date"
                        value="<?php
                        echo htmlspecialchars(
                            $purchaseDate
                        );
                        ?>"
                        max="<?php
                        echo date('Y-m-d');
                        ?>"
                        required
                    >

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    name="update"
                    class="primary-action-button"
                >
                    Update Feed
                </button>


                <a
                    href="feed.php"
                    class="secondary-action-button"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>


</div>


</body>

</html>