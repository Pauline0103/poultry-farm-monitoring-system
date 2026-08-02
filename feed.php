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


// Search values
$search = trim($_GET['search'] ?? "");

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


// Preserve form values
$feedName = "";

$quantity = "";

$price = "";

$supplier = "";

$purchaseDate = "";


// Save a new feed record
if(isset($_POST['save'])){

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


    // Check for empty fields
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


    // Validate price
    elseif(
        !is_numeric($price) ||
        (float) $price <= 0
    ){

        $errorMessage =
            "Price must be greater than zero.";

    }


    // Prevent future purchase dates
    elseif($purchaseDate > date("Y-m-d")){

        $errorMessage =
            "The purchase date cannot be in the future.";

    }


    else{

        $quantityNumber = (int) $quantity;

        $priceNumber = (float) $price;


        $insertSql = "
            INSERT INTO feed
            (
                feed_name,
                quantity,
                price,
                supplier,
                purchase_date
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
                "sidss",
                $feedName,
                $quantityNumber,
                $priceNumber,
                $supplier,
                $purchaseDate
            );


            if(
                mysqli_stmt_execute(
                    $insertStatement
                )
            ){

                $successMessage =
                    "Feed record saved successfully.";


                // Clear form values after saving
                $feedName = "";

                $quantity = "";

                $price = "";

                $supplier = "";

                $purchaseDate = "";

            }else{

                $errorMessage =
                    "The feed record could not be saved.";

            }

            mysqli_stmt_close(
                $insertStatement
            );

        }else{

            $errorMessage =
                "Unable to prepare the feed record.";

        }

    }

}


// =========================================
// Count records for pagination
// =========================================

$totalRecords = 0;

if(
    $search !== "" &&
    $filterDate !== ""
){

    $searchValue =
        "%" . $search . "%";

    $countSql = "
        SELECT COUNT(*) AS total
        FROM feed
        WHERE
        (
            feed_name LIKE ?
            OR supplier LIKE ?
        )
        AND purchase_date = ?
    ";

    $countStatement =
        mysqli_prepare(
            $conn,
            $countSql
        );

    if($countStatement){

        mysqli_stmt_bind_param(
            $countStatement,
            "sss",
            $searchValue,
            $searchValue,
            $filterDate
        );

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
            (int) $countRow['total'];

        mysqli_stmt_close(
            $countStatement
        );

    }

}elseif($search !== ""){

    $searchValue =
        "%" . $search . "%";

    $countSql = "
        SELECT COUNT(*) AS total
        FROM feed
        WHERE
            feed_name LIKE ?
            OR supplier LIKE ?
    ";

    $countStatement =
        mysqli_prepare(
            $conn,
            $countSql
        );

    if($countStatement){

        mysqli_stmt_bind_param(
            $countStatement,
            "ss",
            $searchValue,
            $searchValue
        );

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
            (int) $countRow['total'];

        mysqli_stmt_close(
            $countStatement
        );

    }

}elseif($filterDate !== ""){

    $countSql = "
        SELECT COUNT(*) AS total
        FROM feed
        WHERE purchase_date = ?
    ";

    $countStatement =
        mysqli_prepare(
            $conn,
            $countSql
        );

    if($countStatement){

        mysqli_stmt_bind_param(
            $countStatement,
            "s",
            $filterDate
        );

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
            (int) $countRow['total'];

        mysqli_stmt_close(
            $countStatement
        );

    }

}else{

    $countSql =
        "SELECT COUNT(*) AS total FROM feed";

    $countResult =
        mysqli_query(
            $conn,
            $countSql
        );

    $countRow =
        mysqli_fetch_assoc(
            $countResult
        );

    $totalRecords =
        (int) $countRow['total'];

}

$totalPages = ceil(
    $totalRecords /
    $recordsPerPage
);

if($totalPages < 1){

    $totalPages = 1;

}

if($page > $totalPages){

    $page = $totalPages;

}

$offset =
    ($page - 1) *
    $recordsPerPage;

// =========================================
// Retrieve feed records with pagination
// =========================================

if(
    $search !== "" &&
    $filterDate !== ""
){

    $searchValue =
        "%" . $search . "%";

    $recordsSql = "
        SELECT *
        FROM feed
        WHERE
        (
            feed_name LIKE ?
            OR supplier LIKE ?
        )
        AND purchase_date = ?
        ORDER BY purchase_date DESC, id DESC
        LIMIT ? OFFSET ?
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );

    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "sssii",
            $searchValue,
            $searchValue,
            $filterDate,
            $recordsPerPage,
            $offset
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
            "The feed records could not be searched.";

    }

}elseif($search !== ""){

    $searchValue =
        "%" . $search . "%";

    $recordsSql = "
        SELECT *
        FROM feed
        WHERE
            feed_name LIKE ?
            OR supplier LIKE ?
        ORDER BY purchase_date DESC, id DESC
        LIMIT ? OFFSET ?
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );

    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "ssii",
            $searchValue,
            $searchValue,
            $recordsPerPage,
            $offset
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
            "The feed records could not be searched.";

    }

}elseif($filterDate !== ""){

    $recordsSql = "
        SELECT *
        FROM feed
        WHERE purchase_date = ?
        ORDER BY purchase_date DESC, id DESC
        LIMIT ? OFFSET ?
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );

    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "sii",
            $filterDate,
            $recordsPerPage,
            $offset
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
            "The feed records could not be filtered.";

    }

}else{

    $recordsSql = "
        SELECT *
        FROM feed
        ORDER BY purchase_date DESC, id DESC
        LIMIT ? OFFSET ?
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );

    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "ii",
            $recordsPerPage,
            $offset
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
            "The feed records could not be retrieved.";

    }

}

// Calculate the visible record range
if($totalRecords > 0){

    $firstDisplayedRecord =
        $offset + 1;

    $lastDisplayedRecord =
        min(
            $offset + $recordsPerPage,
            $totalRecords
        );

}else{

    $firstDisplayedRecord = 0;

    $lastDisplayedRecord = 0;

}


// Create pagination links while preserving filters
function createFeedPageUrl(
    int $pageNumber,
    string $searchValue,
    string $dateValue
): string {

    $queryParameters = [
        "page" => $pageNumber
    ];

    if($searchValue !== ""){

        $queryParameters["search"] =
            $searchValue;

    }

    if($dateValue !== ""){

        $queryParameters["filter_date"] =
            $dateValue;

    }

    return "feed.php?" .
        http_build_query(
            $queryParameters
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

    <title>Feed Management</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <div class="page-header">

    <div class="page-header-content">

        <span class="page-badge">
            🌽 Feed Management
        </span>

        <h1>
            Feed Management
        </h1>

        <p>
            Record feed purchases, monitor suppliers and keep accurate feeding records for every production cycle.
        </p>

    </div>

    <div class="page-header-icon">

        🌾

    </div>

</div>


    <?php if($successMessage !== ""){ ?>

        <div class="form-message success-message">

            <?php
            echo htmlspecialchars(
                $successMessage
            );
            ?>

        </div>

    <?php } ?>


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

            <h2>Add Feed Purchase</h2>

            <p>
                Record new feed stock purchased for your poultry farm.
            </p>

        </div>

        <span class="module-card-icon">
            🌾
        </span>

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
                    value="<?php echo htmlspecialchars($feedName); ?>"
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
                    value="<?php echo htmlspecialchars($supplier); ?>"
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
                    value="<?php echo htmlspecialchars($quantity); ?>"
                    min="1"
                    step="1"
                    required
                >

            </div>

            <div class="form-field">

                <label for="price">
                    Price (K)
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    value="<?php echo htmlspecialchars($price); ?>"
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
                    value="<?php echo htmlspecialchars($purchaseDate); ?>"
                    max="<?php echo date('Y-m-d'); ?>"
                    required
                >

            </div>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                name="save"
                class="primary-action-button"
            >

                Save Feed Record

            </button>

        </div>

    </form>

</div>
    <br><br>


    <div class="module-card search-module-card">

    <div class="module-card-header">

        <div>

            <h2>Search Feed Records</h2>

            <p>
                Search by feed name or supplier and filter by purchase date.
            </p>

        </div>

        <span class="module-card-icon">
            🔎
        </span>

    </div>


    <form
        method="GET"
        action="feed.php"
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
                    placeholder="Feed name or supplier"
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                >

            </div>


            <div class="modern-search-field">

                <label for="filter_date">
                    Purchase Date
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
                href="feed.php"
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

        <h2>Feed Records</h2>

        <p>
            Review feed purchases, suppliers, quantities and purchase dates.
        </p>

    </div>

    <span class="records-card-icon">
        📋
    </span>

</div>


    <?php if(
        $search !== "" ||
        $filterDate !== ""
    ){ ?>

        <p class="search-result-text">

            Showing filtered feed records.

        </p>

    <?php } ?>
    </div>

    <div class="pagination-information">

    <p>

        Showing

        <strong>
            <?php echo $firstDisplayedRecord; ?>
        </strong>

        to

        <strong>
            <?php echo $lastDisplayedRecord; ?>
        </strong>

        of

        <strong>
            <?php echo $totalRecords; ?>
        </strong>

        feed record(s)

    </p>

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

                <th>Feed Name</th>

                <th>Quantity</th>

                <th>Price</th>

                <th>Supplier</th>

                <th>Purchase Date</th>

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
                                $row['feed_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo (int) $row['quantity'];
                            ?>
                        </td>

                        <td>
                            K<?php
                            echo number_format(
                                $row['price'],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['supplier']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['purchase_date']
                            );
                            ?>
                        </td>

                       <td>

    <div class="table-actions">

        <a
            href="edit_feed.php?id=<?php
            echo (int) $row['id'];
            ?>"
            class="table-action-button edit-action"
        >
            Edit
        </a>

        <a
            href="delete_feed.php?id=<?php
            echo (int) $row['id'];
            ?>"
            class="table-action-button delete-action"
            onclick="return confirm(
                'Delete this feed record?'
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
                            $filterDate !== ""
                        ){ ?>

                            No feed records matched your search.

                        <?php }else{ ?>

                            No feed records have been added yet.

                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>


        </table>

       
    </div>

     <?php if($totalPages > 1){ ?>

    <nav
        class="pagination"
        aria-label="Feed records pagination"
    >


        <!-- Previous button -->

        <?php if($page > 1){ ?>

            <a
                href="<?php
                echo htmlspecialchars(
                    createFeedPageUrl(
                        $page - 1,
                        $search,
                        $filterDate
                    )
                );
                ?>"
                class="pagination-link"
            >
                Previous
            </a>

        <?php }else{ ?>

            <span
                class="pagination-link pagination-disabled"
            >
                Previous
            </span>

        <?php } ?>


        <!-- Determine visible page numbers -->

        <?php

        $startPage = max(
            1,
            $page - 2
        );

        $endPage = min(
            $totalPages,
            $page + 2
        );

        ?>


        <?php if($startPage > 1){ ?>

            <a
                href="<?php
                echo htmlspecialchars(
                    createFeedPageUrl(
                        1,
                        $search,
                        $filterDate
                    )
                );
                ?>"
                class="pagination-link"
            >
                1
            </a>

            <?php if($startPage > 2){ ?>

                <span class="pagination-dots">
                    ...
                </span>

            <?php } ?>

        <?php } ?>


        <?php for(
            $pageNumber = $startPage;
            $pageNumber <= $endPage;
            $pageNumber++
        ){ ?>

            <?php if($pageNumber === $page){ ?>

                <span
                    class="pagination-link pagination-active"
                >
                    <?php echo $pageNumber; ?>
                </span>

            <?php }else{ ?>

                <a
                    href="<?php
                    echo htmlspecialchars(
                        createFeedPageUrl(
                            $pageNumber,
                            $search,
                            $filterDate
                        )
                    );
                    ?>"
                    class="pagination-link"
                >
                    <?php echo $pageNumber; ?>
                </a>

            <?php } ?>

        <?php } ?>


        <?php if($endPage < $totalPages){ ?>

            <?php if(
                $endPage <
                $totalPages - 1
            ){ ?>

                <span class="pagination-dots">
                    ...
                </span>

            <?php } ?>

            <a
                href="<?php
                echo htmlspecialchars(
                    createFeedPageUrl(
                        $totalPages,
                        $search,
                        $filterDate
                    )
                );
                ?>"
                class="pagination-link"
            >
                <?php echo $totalPages; ?>
            </a>

        <?php } ?>


        <!-- Next button -->

        <?php if($page < $totalPages){ ?>

            <a
                href="<?php
                echo htmlspecialchars(
                    createFeedPageUrl(
                        $page + 1,
                        $search,
                        $filterDate
                    )
                );
                ?>"
                class="pagination-link"
            >
                Next
            </a>

        <?php }else{ ?>

            <span
                class="pagination-link pagination-disabled"
            >
                Next
            </span>

        <?php } ?>


    </nav>

<?php } ?>


</div>


</body>

</html>