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


// Preserve form values
$batchName = "";
$birdType = "";
$quantity = "";
$arrivalDate = "";


// Search value
$search = trim($_GET['search'] ?? "");


// Pagination settings
$recordsPerPage = 10;

$page = filter_input(
    INPUT_GET,
    "page",
    FILTER_VALIDATE_INT
);

if(!$page || $page < 1){

    $page = 1;

}


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


    // Validate the arrival date
    elseif(
        !DateTime::createFromFormat(
            "Y-m-d",
            $arrivalDate
        )
    ){

        $errorMessage =
            "Please provide a valid arrival date.";

    }


    // Prevent future arrival dates
    elseif($arrivalDate > date("Y-m-d")){

        $errorMessage =
            "The arrival date cannot be in the future.";

    }


    else{

        $quantityNumber = (int) $quantity;


        // Check whether the batch name already exists
        $checkSql = "
            SELECT id
            FROM birds
            WHERE batch_name = ?
            LIMIT 1
        ";

        $checkStatement =
            mysqli_prepare(
                $conn,
                $checkSql
            );


        if(!$checkStatement){

            $errorMessage =
                "The batch name could not be checked.";

        }else{

            mysqli_stmt_bind_param(
                $checkStatement,
                "s",
                $batchName
            );

            mysqli_stmt_execute(
                $checkStatement
            );

            $checkResult =
                mysqli_stmt_get_result(
                    $checkStatement
                );

            $existingBatch =
                mysqli_fetch_assoc(
                    $checkResult
                );

            mysqli_stmt_close(
                $checkStatement
            );


            if($existingBatch){

                $errorMessage =
                    "A bird batch with this name already exists.";

            }else{

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


                        // Clear the form after saving
                        $batchName = "";
                        $birdType = "";
                        $quantity = "";
                        $arrivalDate = "";

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

    }

}


// -----------------------------------------
// Count records for pagination
// -----------------------------------------

$totalRecords = 0;


if($search !== ""){

    $searchValue =
        "%" . $search . "%";

    $countSql = "
        SELECT COUNT(*) AS total
        FROM birds
        WHERE
            batch_name LIKE ?
            OR bird_type LIKE ?
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
            (int) ($countRow['total'] ?? 0);

        mysqli_stmt_close(
            $countStatement
        );

    }else{

        $errorMessage =
            "The number of bird records could not be calculated.";

    }

}else{

    $countSql = "
        SELECT COUNT(*) AS total
        FROM birds
    ";

    $countResult =
        mysqli_query(
            $conn,
            $countSql
        );


    if($countResult){

        $countRow =
            mysqli_fetch_assoc(
                $countResult
            );

        $totalRecords =
            (int) ($countRow['total'] ?? 0);

    }else{

        $errorMessage =
            "The number of bird records could not be calculated.";

    }

}


// Calculate total pages
$totalPages = (int) ceil(
    $totalRecords / $recordsPerPage
);


// Always keep at least one page
if($totalPages < 1){

    $totalPages = 1;

}


// Prevent page numbers greater than total pages
if($page > $totalPages){

    $page = $totalPages;

}


// Calculate database offset
$offset =
    ($page - 1) * $recordsPerPage;


// -----------------------------------------
// Retrieve records for the current page
// -----------------------------------------

if($search !== ""){

    $recordsSql = "
        SELECT *
        FROM birds
        WHERE
            batch_name LIKE ?
            OR bird_type LIKE ?
        ORDER BY arrival_date DESC, id DESC
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
            "The bird records could not be searched.";

    }

}else{

    $recordsSql = "
        SELECT *
        FROM birds
        ORDER BY arrival_date DESC, id DESC
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
            "The bird records could not be retrieved.";

    }

}


// Calculate record range
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


/*
Create a pagination URL.

This function keeps the search value when moving
between pagination pages.
*/
function createBirdPageUrl(
    int $pageNumber,
    string $searchValue
): string {

    $queryParameters = [
        "page" => $pageNumber
    ];

    if($searchValue !== ""){

        $queryParameters["search"] =
            $searchValue;

    }

    return "birds.php?" .
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
            placeholder="Example: Batch A"
            value="<?php
            echo htmlspecialchars(
                $batchName
            );
            ?>"
            required
        >


        <label for="bird_type">
            Bird Type
        </label>

        <input
            type="text"
            id="bird_type"
            name="bird_type"
            placeholder="Example: Broilers"
            value="<?php
            echo htmlspecialchars(
                $birdType
            );
            ?>"
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
            placeholder="Example: 100"
            value="<?php
            echo htmlspecialchars(
                $quantity
            );
            ?>"
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
            value="<?php
            echo htmlspecialchars(
                $arrivalDate
            );
            ?>"
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

        <form
            method="GET"
            action="birds.php"
        >

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


    <!-- Record count -->

    <div class="pagination-information">

        <p>

            Showing

            <strong>
                <?php
                echo $firstDisplayedRecord;
                ?>
            </strong>

            to

            <strong>
                <?php
                echo $lastDisplayedRecord;
                ?>
            </strong>

            of

            <strong>
                <?php
                echo $totalRecords;
                ?>
            </strong>

            bird record(s)

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


    <!-- Pagination links -->

    <?php if($totalPages > 1){ ?>

        <nav
            class="pagination"
            aria-label="Bird records pagination"
        >


            <!-- Previous button -->

            <?php if($page > 1){ ?>

                <a
                    href="<?php
                    echo htmlspecialchars(
                        createBirdPageUrl(
                            $page - 1,
                            $search
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


            <!-- Page numbers -->

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
                        createBirdPageUrl(
                            1,
                            $search
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
                            createBirdPageUrl(
                                $pageNumber,
                                $search
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
                        createBirdPageUrl(
                            $totalPages,
                            $search
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
                        createBirdPageUrl(
                            $page + 1,
                            $search
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