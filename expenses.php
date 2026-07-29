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


// Search and filter values
$search = trim($_GET['search'] ?? "");
$filterCategory = trim($_GET['filter_category'] ?? "");
$filterDate = trim($_GET['filter_date'] ?? "");


// Form values
$expenseName = "";
$expenseCategory = "";
$amount = "";
$expenseDate = "";
$description = "";


// Save a new expense
if(isset($_POST['save'])){

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


    // Check required fields
    if(
        $expenseName === "" ||
        $expenseCategory === "" ||
        $amount === "" ||
        $expenseDate === ""
    ){

        $errorMessage =
            "Please complete all the required fields.";

    }


    // Validate expense amount
    elseif(
        !is_numeric($amount) ||
        (float) $amount <= 0
    ){

        $errorMessage =
            "The expense amount must be greater than zero.";

    }


    // Prevent future expense dates
    elseif(
        $expenseDate > date("Y-m-d")
    ){

        $errorMessage =
            "The expense date cannot be in the future.";

    }


    else{

        $amountValue = (float) $amount;


        $insertSql = "
            INSERT INTO expenses
            (
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
                "ssdss",
                $expenseName,
                $expenseCategory,
                $amountValue,
                $expenseDate,
                $description
            );


            if(
                mysqli_stmt_execute(
                    $insertStatement
                )
            ){

                $successMessage =
                    "Expense record saved successfully.";


                // Clear form after successful saving
                $expenseName = "";
                $expenseCategory = "";
                $amount = "";
                $expenseDate = "";
                $description = "";

            }else{

                $errorMessage =
                    "The expense record could not be saved.";

            }


            mysqli_stmt_close(
                $insertStatement
            );

        }else{

            $errorMessage =
                "Unable to prepare the expense record.";

        }

    }

}


// Prepare the search query
$conditions = [];
$parameters = [];
$parameterTypes = "";


// Search by expense name or description
if($search !== ""){

    $conditions[] = "
        (
            expense_name LIKE ?
            OR description LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $parameters[] = $searchValue;
    $parameters[] = $searchValue;

    $parameterTypes .= "ss";

}


// Filter by category
if($filterCategory !== ""){

    $conditions[] =
        "expense_category = ?";

    $parameters[] =
        $filterCategory;

    $parameterTypes .= "s";

}


// Filter by expense date
if($filterDate !== ""){

    $conditions[] =
        "expense_date = ?";

    $parameters[] =
        $filterDate;

    $parameterTypes .= "s";

}


// Build final SELECT query
$recordsSql = "
    SELECT *
    FROM expenses
";


if(count($conditions) > 0){

    $recordsSql .= "
        WHERE " .
        implode(
            " AND ",
            $conditions
        );

}


$recordsSql .= "
    ORDER BY expense_date DESC, id DESC
";


$recordsStatement =
    mysqli_prepare(
        $conn,
        $recordsSql
    );


if($recordsStatement){

    if(count($parameters) > 0){

        mysqli_stmt_bind_param(
            $recordsStatement,
            $parameterTypes,
            ...$parameters
        );

    }


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
        "The expense records could not be retrieved.";

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

    <title>Expenses Management</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <h1>Expenses Management</h1>

    <p>
        Record and monitor all poultry farm expenses.
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


    <!-- Add expense form -->

    <form method="POST" action="">


        <label for="expense_name">
            Expense Name
        </label>

        <input
            type="text"
            id="expense_name"
            name="expense_name"
            placeholder="Example: Starter feed"
            value="<?php
            echo htmlspecialchars(
                $expenseName
            );
            ?>"
            required
        >


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

            <option
                value="Feed"
                <?php
                if($expenseCategory === "Feed"){
                    echo "selected";
                }
                ?>
            >
                Feed
            </option>

            <option
                value="Vaccines"
                <?php
                if($expenseCategory === "Vaccines"){
                    echo "selected";
                }
                ?>
            >
                Vaccines
            </option>

            <option
                value="Medicine"
                <?php
                if($expenseCategory === "Medicine"){
                    echo "selected";
                }
                ?>
            >
                Medicine
            </option>

            <option
                value="Transport"
                <?php
                if($expenseCategory === "Transport"){
                    echo "selected";
                }
                ?>
            >
                Transport
            </option>

            <option
                value="Utilities"
                <?php
                if($expenseCategory === "Utilities"){
                    echo "selected";
                }
                ?>
            >
                Electricity and Water
            </option>

            <option
                value="Equipment"
                <?php
                if($expenseCategory === "Equipment"){
                    echo "selected";
                }
                ?>
            >
                Equipment
            </option>

            <option
                value="Labour"
                <?php
                if($expenseCategory === "Labour"){
                    echo "selected";
                }
                ?>
            >
                Labour
            </option>

            <option
                value="Other"
                <?php
                if($expenseCategory === "Other"){
                    echo "selected";
                }
                ?>
            >
                Other
            </option>

        </select>


        <label for="amount">
            Amount
        </label>

        <input
            type="number"
            id="amount"
            name="amount"
            step="0.01"
            min="0.01"
            placeholder="Example: 850.00"
            value="<?php
            echo htmlspecialchars(
                $amount
            );
            ?>"
            required
        >


        <label for="expense_date">
            Expense Date
        </label>

        <input
            type="date"
            id="expense_date"
            name="expense_date"
            max="<?php echo date('Y-m-d'); ?>"
            value="<?php
            echo htmlspecialchars(
                $expenseDate
            );
            ?>"
            required
        >


        <label for="description">
            Description
        </label>

        <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="Enter additional information about this expense"
        ><?php
        echo htmlspecialchars(
            $description
        );
        ?></textarea>


        <br><br>


        <button
            type="submit"
            name="save"
            class="save-button"
        >

            Save Expense

        </button>


    </form>


    <br><br>


    <!-- Search and filter panel -->

    <div class="search-panel">

        <form
            method="GET"
            action="expenses.php"
        >

            <div class="expense-search-grid">


                <div>

                    <label for="search">
                        Search Expense Records
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Expense name or description"
                        value="<?php
                        echo htmlspecialchars(
                            $search
                        );
                        ?>"
                    >

                </div>


                <div>

                    <label for="filter_category">
                        Expense Category
                    </label>

                    <select
                        id="filter_category"
                        name="filter_category"
                    >

                        <option value="">
                            All categories
                        </option>

                        <option
                            value="Feed"
                            <?php
                            if($filterCategory === "Feed"){
                                echo "selected";
                            }
                            ?>
                        >
                            Feed
                        </option>

                        <option
                            value="Vaccines"
                            <?php
                            if($filterCategory === "Vaccines"){
                                echo "selected";
                            }
                            ?>
                        >
                            Vaccines
                        </option>

                        <option
                            value="Medicine"
                            <?php
                            if($filterCategory === "Medicine"){
                                echo "selected";
                            }
                            ?>
                        >
                            Medicine
                        </option>

                        <option
                            value="Transport"
                            <?php
                            if($filterCategory === "Transport"){
                                echo "selected";
                            }
                            ?>
                        >
                            Transport
                        </option>

                        <option
                            value="Utilities"
                            <?php
                            if($filterCategory === "Utilities"){
                                echo "selected";
                            }
                            ?>
                        >
                            Electricity and Water
                        </option>

                        <option
                            value="Equipment"
                            <?php
                            if($filterCategory === "Equipment"){
                                echo "selected";
                            }
                            ?>
                        >
                            Equipment
                        </option>

                        <option
                            value="Labour"
                            <?php
                            if($filterCategory === "Labour"){
                                echo "selected";
                            }
                            ?>
                        >
                            Labour
                        </option>

                        <option
                            value="Other"
                            <?php
                            if($filterCategory === "Other"){
                                echo "selected";
                            }
                            ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <div>

                    <label for="filter_date">
                        Expense Date
                    </label>

                    <input
                        type="date"
                        id="filter_date"
                        name="filter_date"
                        value="<?php
                        echo htmlspecialchars(
                            $filterDate
                        );
                        ?>"
                    >

                </div>


            </div>


            <br>


            <button
                type="submit"
                class="save-button"
            >

                Search

            </button>


            <a
                href="expenses.php"
                class="search-reset-button"
            >

                Reset

            </a>


        </form>

    </div>


    <br>


    <h2>Expense Records</h2>


    <?php if(
        $search !== "" ||
        $filterCategory !== "" ||
        $filterDate !== ""
    ){ ?>

        <p class="search-result-text">
            Showing filtered expense records.
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

                <th>Expense Name</th>

                <th>Category</th>

                <th>Amount</th>

                <th>Expense Date</th>

                <th>Description</th>

                <th>Actions</th>

            </tr>


            <?php

            $displayedTotalExpenses = 0;

            ?>


            <?php if(
                $result &&
                mysqli_num_rows($result) > 0
            ){ ?>


                <?php while(
                    $row =
                    mysqli_fetch_assoc($result)
                ){ ?>


                    <?php

                    $displayedTotalExpenses +=
                        (float) $row['amount'];

                    ?>


                    <tr>

                        <td>
                            <?php
                            echo (int) $row['id'];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['expense_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['expense_category']
                            );
                            ?>
                        </td>

                        <td>
                            K<?php
                            echo number_format(
                                $row['amount'],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['expense_date']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['description']
                            );
                            ?>
                        </td>

                        <td>

                            <a
                                href="edit_expense.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="delete_expense.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                                onclick="return confirm(
                                    'Are you sure you want to delete this expense record?'
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
                        colspan="7"
                        class="empty-table-message"
                    >

                        <?php if(
                            $search !== "" ||
                            $filterCategory !== "" ||
                            $filterDate !== ""
                        ){ ?>

                            No expense records matched your search.

                        <?php }else{ ?>

                            No expense records have been added yet.

                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>


        </table>

    </div>


    <!-- Expense total -->

    <div class="expense-summary">

        <h3>

            <?php if(
                $search !== "" ||
                $filterCategory !== "" ||
                $filterDate !== ""
            ){ ?>

                Total for Displayed Expenses

            <?php }else{ ?>

                Total Expenses

            <?php } ?>

        </h3>

        <p>

            K<?php
            echo number_format(
                $displayedTotalExpenses,
                2
            );
            ?>

        </p>

    </div>


</div>


</body>

</html>