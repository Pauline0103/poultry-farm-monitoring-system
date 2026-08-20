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

// Pagination
$recordsPerPage = 10;

$page = filter_input(
    INPUT_GET,
    "page",
    FILTER_VALIDATE_INT
);

if(!$page || $page < 1){

    $page = 1;

}


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


// =========================================
// Count matching expense records
// =========================================

$countSql = "
    SELECT COUNT(*) AS total
    FROM expenses
";

if(count($conditions) > 0){

    $countSql .= "
        WHERE " .
        implode(
            " AND ",
            $conditions
        );

}

$countStatement =
    mysqli_prepare(
        $conn,
        $countSql
    );

$totalRecords = 0;

if($countStatement){

    if(count($parameters) > 0){

        mysqli_stmt_bind_param(
            $countStatement,
            $parameterTypes,
            ...$parameters
        );

    }

    mysqli_stmt_execute(
        $countStatement
    );

    $countResult =
        mysqli_stmt_get_result(
            $countStatement
        );

    $countRow =
        mysqli_fetch_assoc(
            $countResult
        );

    $totalRecords =
        (int) (
            $countRow['total'] ?? 0
        );

    mysqli_stmt_close(
        $countStatement
    );

}else{

    $errorMessage =
        "The number of expense records could not be calculated.";

}


// Calculate the number of pages
$totalPages = max(
    1,
    (int) ceil(
        $totalRecords /
        $recordsPerPage
    )
);


// Prevent page numbers beyond the last page
if($page > $totalPages){

    $page = $totalPages;

}


// Calculate how many records MySQL should skip
$offset =
    ($page - 1) *
    $recordsPerPage;

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
    LIMIT ? OFFSET ?
";


$recordsStatement =
    mysqli_prepare(
        $conn,
        $recordsSql
    );


if($recordsStatement){

    /*
    Add the pagination values after any existing
    search and filter parameters.
    */

    $recordParameters = $parameters;

    $recordParameterTypes =
        $parameterTypes . "ii";

    $recordParameters[] =
        $recordsPerPage;

    $recordParameters[] =
        $offset;


    mysqli_stmt_bind_param(
        $recordsStatement,
        $recordParameterTypes,
        ...$recordParameters
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


    <div class="page-header clean-page-header">

    <div class="page-header-content">

        <h1>Expenses Management</h1>

        <p>
            Record and monitor poultry farm expenses
            and operating costs.
        </p>

    </div>

</div>

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


<div class="module-card">

    <div class="module-card-header">

        <div>

            <h2>Record Expense</h2>

            <p>
                Add a farm expense and classify it
                for accurate financial records.
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
                    placeholder="Example: Starter feed"
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

            </div>


            <div class="form-field">

                <label for="amount">
                    Amount (K)
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

            </div>


            <div class="form-field">

                <label for="expense_date">
                    Expense Date
                </label>

                <input
                    type="date"
                    id="expense_date"
                    name="expense_date"
                    max="<?php
                    echo date('Y-m-d');
                    ?>"
                    value="<?php
                    echo htmlspecialchars(
                        $expenseDate
                    );
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
                    placeholder="Enter additional information about this expense"
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
                name="save"
                class="primary-action-button"
            >
                Save Expense
            </button>

        </div>

    </form>

</div>


  <!-- Search and filter panel -->

<div class="module-card search-module-card">

    <div class="module-card-header">

        <div>

            <h2>Search Expense Records</h2>

            <p>
                Search expenses and filter records by
                category or expense date.
            </p>

        </div>

    </div>


    <form
        method="GET"
        action="expenses.php"
        class="modern-search-form"
    >

        <div class="modern-search-grid">


            <div class="modern-search-field">

                <label for="search">
                    Search
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


            <div class="modern-search-field">

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


            <div class="modern-search-field">

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


        <div class="modern-search-actions">

            <button
                type="submit"
                class="primary-action-button"
            >
                Search
            </button>

            <a
                href="expenses.php"
                class="secondary-action-button"
            >
                Reset
            </a>

        </div>

    </form>

</div>

    <br>


    <div class="records-card">

    <div class="records-card-header">

        <div>

            <h2>Expense Records</h2>

            <p>
                Review farm expenses, categories, amounts
                and transaction dates.
            </p>

        </div>

    </div>


    <?php if(
        $search !== "" ||
        $filterCategory !== "" ||
        $filterDate !== ""
    ){ ?>

        <p class="search-result-text">
            Showing filtered expense records.
        </p>

    <?php } ?>

    <?php

$startRecord = 0;
$endRecord = 0;

if($totalRecords > 0){

    $startRecord =
        $offset + 1;

    $endRecord =
        min(
            $offset + $recordsPerPage,
            $totalRecords
        );

}

?>

<p class="pagination-summary">

    Showing

    <strong>
        <?php echo $startRecord; ?>
    </strong>

    to

    <strong>
        <?php echo $endRecord; ?>
    </strong>

    of

    <strong>
        <?php echo $totalRecords; ?>
    </strong>

    expense records.

</p>


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

    <div class="table-actions">

        <a
            href="edit_expense.php?id=<?php
            echo (int) $row['id'];
            ?>"
            class="table-action-button edit-action"
        >
            Edit
        </a>

        <a
            href="delete_expense.php?id=<?php
            echo (int) $row['id'];
            ?>"
            class="table-action-button delete-action"
            onclick="return confirm(
                'Are you sure you want to delete this expense record?'
            );"
        >
            Delete
        </a>

    </div>

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

    <?php if($totalPages > 1){ ?>

    <div class="pagination">

        <?php

        $paginationParameters = [];

        if($search !== ""){

            $paginationParameters['search'] =
                $search;

        }

        if($filterCategory !== ""){

            $paginationParameters['filter_category'] =
                $filterCategory;

        }

        if($filterDate !== ""){

            $paginationParameters['filter_date'] =
                $filterDate;

        }

        ?>


        <?php if($page > 1){ ?>

            <?php

            $previousParameters =
                $paginationParameters;

            $previousParameters['page'] =
                $page - 1;

            ?>

           <a
    href="expenses.php?<?php
    echo htmlspecialchars(
        http_build_query(
            $previousParameters
        )
    );
    ?>"
    class="pagination-link"
>

                Previous

            </a>

        <?php } ?>


        <?php for(
            $pageNumber = 1;
            $pageNumber <= $totalPages;
            $pageNumber++
        ){ ?>

            <?php

            $pageParameters =
                $paginationParameters;

            $pageParameters['page'] =
                $pageNumber;

            ?>

            <a
                href="expenses.php?<?php
                echo htmlspecialchars(
                    http_build_query(
                        $pageParameters
                    )
                );
                ?>"
                class="pagination-link <?php
echo $pageNumber === $page
    ? 'pagination-active'
    : '';
?>"
            >

                <?php echo $pageNumber; ?>

            </a>

        <?php } ?>


        <?php if($page < $totalPages){ ?>

            <?php

            $nextParameters =
                $paginationParameters;

            $nextParameters['page'] =
                $page + 1;

            ?>

           <a
    href="expenses.php?<?php
    echo htmlspecialchars(
        http_build_query(
            $nextParameters
        )
    );
    ?>"
    class="pagination-link"
>
                Next

            </a>

        <?php } ?>

    </div>

<?php } ?>


    <!-- Expense total -->

  <div class="expense-summary">

    <h3>
        Total Expenses on This Page
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


</div> <!-- closes records-card -->


</div> <!-- closes content -->


</body>

</html>