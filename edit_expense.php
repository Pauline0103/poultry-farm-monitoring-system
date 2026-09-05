<?php

session_start();


// Protect page
if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}


include "config/database.php";


$errorMessage = "";


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


// Retrieve expense securely
$selectSql = "
    SELECT
        id,
        expense_name,
        expense_category,
        amount,
        expense_date,
        description
    FROM expenses
    WHERE id = ?
    LIMIT 1
";

$selectStatement =
    mysqli_prepare(
        $conn,
        $selectSql
    );


if(!$selectStatement){

    header("Location: expenses.php");

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

$expense =
    mysqli_fetch_assoc(
        $selectResult
    );

mysqli_stmt_close(
    $selectStatement
);


// Record does not exist
if(!$expense){

    header("Location: expenses.php");

    exit();

}


// Preserve current values
$expenseName =
    $expense['expense_name'];

$expenseCategory =
    $expense['expense_category'];

$amount =
    $expense['amount'];

$expenseDate =
    $expense['expense_date'];

$description =
    $expense['description'];


// Allowed categories
$allowedCategories = [
    "Feed",
    "Vaccines",
    "Medicine",
    "Transport",
    "Utilities",
    "Equipment",
    "Labour",
    "Other"
];


// Update expense record
if(isset($_POST['update'])){

    $expenseName = trim(
        $_POST['expense_name'] ?? ""
    );

    $expenseCategory = trim(
        $_POST['expense_category'] ?? ""
    );

    $amount = trim(
        $_POST['amount'] ?? ""
    );

    $expenseDate = trim(
        $_POST['expense_date'] ?? ""
    );

    $description = trim(
        $_POST['description'] ?? ""
    );


    // Required fields
    if(
        $expenseName === "" ||
        $expenseCategory === "" ||
        $amount === "" ||
        $expenseDate === ""
    ){

        $errorMessage =
            "Please complete all the required fields.";

    }


    // Validate category
    elseif(
        !in_array(
            $expenseCategory,
            $allowedCategories,
            true
        )
    ){

        $errorMessage =
            "Please select a valid expense category.";

    }


    // Validate amount
    elseif(
        !is_numeric($amount) ||
        (float) $amount <= 0
    ){

        $errorMessage =
            "The expense amount must be greater than zero.";

    }


    // Validate expense date
    elseif(
        !DateTime::createFromFormat(
            "Y-m-d",
            $expenseDate
        )
    ){

        $errorMessage =
            "Please provide a valid expense date.";

    }


    // Prevent future expense date
    elseif(
        $expenseDate >
        date("Y-m-d")
    ){

        $errorMessage =
            "The expense date cannot be in the future.";

    }


    else{

        $amountValue =
            (float) $amount;


        $updateSql = "
            UPDATE expenses
            SET
                expense_name = ?,
                expense_category = ?,
                amount = ?,
                expense_date = ?,
                description = ?
            WHERE id = ?
        ";

        $updateStatement =
            mysqli_prepare(
                $conn,
                $updateSql
            );


        if($updateStatement){

            mysqli_stmt_bind_param(
                $updateStatement,
                "ssdssi",
                $expenseName,
                $expenseCategory,
                $amountValue,
                $expenseDate,
                $description,
                $id
            );


            if(
                mysqli_stmt_execute(
                    $updateStatement
                )
            ){

                mysqli_stmt_close(
                    $updateStatement
                );

                header(
                    "Location: expenses.php?updated=1"
                );

                exit();

            }else{

                $errorMessage =
                    "The expense record could not be updated.";

            }


            mysqli_stmt_close(
                $updateStatement
            );

        }else{

            $errorMessage =
                "Unable to prepare the expense update.";

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

    <title>Edit Expense Record</title>

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

            <h1>Edit Expense Record</h1>

            <p>
                Update farm expense information.
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

                <h2>Expense Details</h2>

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

                    <label for="expense_name">
                        Expense Name
                    </label>

                    <input
                        type="text"
                        id="expense_name"
                        name="expense_name"
                        value="<?php
                        echo htmlspecialchars(
                            $expenseName
                        );
                        ?>"
                        required
                    >

                </div>


                <div class="form-field">

                    <label for="expense_category">
                        Expense Category
                    </label>

                    <select
                        id="expense_category"
                        name="expense_category"
                        required
                    >

                        <option value="">
                            Select an expense category
                        </option>


                        <?php foreach(
                            $allowedCategories
                            as $category
                        ){ ?>

                            <option
                                value="<?php
                                echo htmlspecialchars(
                                    $category
                                );
                                ?>"
                                <?php
                                if(
                                    $expenseCategory ===
                                    $category
                                ){
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php

                                if(
                                    $category ===
                                    "Utilities"
                                ){

                                    echo
                                        "Electricity and Water";

                                }else{

                                    echo htmlspecialchars(
                                        $category
                                    );

                                }

                                ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>


                <div class="form-field">

                    <label for="amount">
                        Amount (K)
                    </label>

                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        min="0.01"
                        step="0.01"
                        value="<?php
                        echo htmlspecialchars(
                            $amount
                        );
                        ?>"
                        required
                    >

                </div>


                <div class="form-field">

                    <label for="expense_date">
                        Expense Date
                    </label>

                    <input
                        type="date"
                        id="expense_date"
                        name="expense_date"
                        value="<?php
                        echo htmlspecialchars(
                            $expenseDate
                        );
                        ?>"
                        max="<?php
                        echo date('Y-m-d');
                        ?>"
                        required
                    >

                </div>


                <div class="form-field full-width-field">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                    ><?php
                    echo htmlspecialchars(
                        $description
                    );
                    ?></textarea>

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    name="update"
                    class="primary-action-button"
                >
                    Update Expense
                </button>


                <a
                    href="expenses.php"
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