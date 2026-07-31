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
$birdBatch = "";

$numberDead = "";

$causeOfDeath = "";

$mortalityDate = "";

$notes = "";


// Save mortality record
if(isset($_POST['save'])){

    $birdBatch = trim(
        $_POST['bird_batch'] ?? ""
    );

    $numberDead = trim(
        $_POST['number_dead'] ?? ""
    );

    $causeOfDeath = trim(
        $_POST['cause_of_death'] ?? ""
    );

    $mortalityDate = trim(
        $_POST['mortality_date'] ?? ""
    );

    $notes = trim(
        $_POST['notes'] ?? ""
    );


    // Check required fields
    if(
        $birdBatch === "" ||
        $numberDead === "" ||
        $causeOfDeath === "" ||
        $mortalityDate === ""
    ){

        $errorMessage =
            "Please complete all the required fields.";

    }


    // Validate number of dead birds
    elseif(
        !filter_var(
            $numberDead,
            FILTER_VALIDATE_INT
        ) ||
        (int) $numberDead <= 0
    ){

        $errorMessage =
            "The number of dead birds must be a whole number greater than zero.";

    }


    // Prevent future mortality dates
    elseif(
        $mortalityDate >
        date("Y-m-d")
    ){

        $errorMessage =
            "The mortality date cannot be in the future.";

    }


    else{

        $numberDeadValue =
            (int) $numberDead;


        $insertSql = "
            INSERT INTO mortality
            (
                bird_batch,
                number_dead,
                cause_of_death,
                mortality_date,
                notes
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
                "sisss",
                $birdBatch,
                $numberDeadValue,
                $causeOfDeath,
                $mortalityDate,
                $notes
            );


            if(
                mysqli_stmt_execute(
                    $insertStatement
                )
            ){

                $successMessage =
                    "Mortality record saved successfully.";


                // Clear form after saving
                $birdBatch = "";

                $numberDead = "";

                $causeOfDeath = "";

                $mortalityDate = "";

                $notes = "";

            }else{

                $errorMessage =
                    "The mortality record could not be saved.";

            }

            mysqli_stmt_close(
                $insertStatement
            );

        }else{

            $errorMessage =
                "Unable to prepare the mortality record.";

        }

    }

}

// Count matching mortality records

if(
    $search !== "" &&
    $filterDate !== ""
){

    $searchValue =
        "%" . $search . "%";

    $countSql = "
        SELECT COUNT(*) AS total
        FROM mortality
        WHERE
        (
            bird_batch LIKE ?
            OR cause_of_death LIKE ?
        )
        AND mortality_date = ?
    ";

    $countStatement =
        mysqli_prepare(
            $conn,
            $countSql
        );

    mysqli_stmt_bind_param(
        $countStatement,
        "sss",
        $searchValue,
        $searchValue,
        $filterDate
    );

}elseif($search !== ""){

    $searchValue =
        "%" . $search . "%";

    $countSql = "
        SELECT COUNT(*) AS total
        FROM mortality
        WHERE
            bird_batch LIKE ?
            OR cause_of_death LIKE ?
    ";

    $countStatement =
        mysqli_prepare(
            $conn,
            $countSql
        );

    mysqli_stmt_bind_param(
        $countStatement,
        "ss",
        $searchValue,
        $searchValue
    );

}elseif($filterDate !== ""){

    $countSql = "
        SELECT COUNT(*) AS total
        FROM mortality
        WHERE mortality_date = ?
    ";

    $countStatement =
        mysqli_prepare(
            $conn,
            $countSql
        );

    mysqli_stmt_bind_param(
        $countStatement,
        "s",
        $filterDate
    );

}else{

    $countSql = "
        SELECT COUNT(*) AS total
        FROM mortality
    ";

    $countStatement =
        mysqli_prepare(
            $conn,
            $countSql
        );

}

mysqli_stmt_execute(
    $countStatement
);

$countResult =
    mysqli_stmt_get_result(
        $countStatement
    );

$totalRecords =
    mysqli_fetch_assoc(
        $countResult
    )['total'];

$totalPages = max(
    1,
    (int) ceil(
        $totalRecords /
        $recordsPerPage
    )
);

if($page > $totalPages){

    $page = $totalPages;

}

$offset =
    ($page - 1) *
    $recordsPerPage;

mysqli_stmt_close(
    $countStatement
);

// Retrieve mortality records
if(
    $search !== "" &&
    $filterDate !== ""
){

    $searchValue =
        "%" . $search . "%";

    $recordsSql = "
        SELECT *
        FROM mortality
        WHERE
        (
            bird_batch LIKE ?
            OR cause_of_death LIKE ?
        )
        AND mortality_date = ?
        ORDER BY mortality_date DESC, id DESC
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
            "The mortality records could not be searched.";

    }

}elseif($search !== ""){

    $searchValue =
        "%" . $search . "%";

    $recordsSql = "
        SELECT *
        FROM mortality
        WHERE
            bird_batch LIKE ?
            OR cause_of_death LIKE ?
        ORDER BY mortality_date DESC, id DESC
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
            "The mortality records could not be searched.";

    }

}elseif($filterDate !== ""){

    $recordsSql = "
        SELECT *
        FROM mortality
        WHERE mortality_date = ?
        ORDER BY mortality_date DESC, id DESC
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
            "The mortality records could not be filtered.";

    }

}else{

    $recordsSql = "
        SELECT *
        FROM mortality
        ORDER BY mortality_date DESC, id DESC
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
            "The mortality records could not be retrieved.";

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

    <title>Mortality Management</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <h1>Mortality Management</h1>

    <p>
        Record birds that have died and the possible cause of death.
    </p>


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


    <form method="POST" action="">


        <label for="bird_batch">
            Bird Batch
        </label>

        <input
            type="text"
            id="bird_batch"
            name="bird_batch"
            placeholder="Example: Batch A"
            value="<?php
            echo htmlspecialchars(
                $birdBatch
            );
            ?>"
            required
        >


        <label for="number_dead">
            Number of Dead Birds
        </label>

        <input
            type="number"
            id="number_dead"
            name="number_dead"
            min="1"
            step="1"
            placeholder="Example: 3"
            value="<?php
            echo htmlspecialchars(
                $numberDead
            );
            ?>"
            required
        >


        <label for="cause_of_death">
            Cause of Death
        </label>

        <input
            type="text"
            id="cause_of_death"
            name="cause_of_death"
            placeholder="Example: Respiratory infection"
            value="<?php
            echo htmlspecialchars(
                $causeOfDeath
            );
            ?>"
            required
        >


        <label for="mortality_date">
            Mortality Date
        </label>

        <input
            type="date"
            id="mortality_date"
            name="mortality_date"
            max="<?php echo date('Y-m-d'); ?>"
            value="<?php
            echo htmlspecialchars(
                $mortalityDate
            );
            ?>"
            required
        >


        <label for="notes">
            Notes
        </label>

        <textarea
            id="notes"
            name="notes"
            rows="4"
            placeholder="Enter any additional observations"
        ><?php
        echo htmlspecialchars(
            $notes
        );
        ?></textarea>


        <br><br>


        <button
            type="submit"
            name="save"
            class="save-button"
        >

            Save Mortality Record

        </button>


    </form>


    <br><br>


    <!-- Search panel -->

    <div class="search-panel">

        <form
            method="GET"
            action="mortality.php"
        >

            <div class="search-grid">


                <div>

                    <label for="search">
                        Search Mortality Records
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Bird batch or cause of death"
                        value="<?php
                        echo htmlspecialchars(
                            $search
                        );
                        ?>"
                    >

                </div>


                <div>

                    <label for="filter_date">
                        Mortality Date
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
                href="mortality.php"
                class="search-reset-button"
            >

                Reset

            </a>


        </form>

    </div>


    <br>


    <h2>Mortality Records</h2>


    <?php if(
        $search !== "" ||
        $filterDate !== ""
    ){ ?>

        <p class="search-result-text">
            Showing filtered mortality records.
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

    mortality records.

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

                <th>Bird Batch</th>

                <th>Number Dead</th>

                <th>Cause of Death</th>

                <th>Mortality Date</th>

                <th>Notes</th>

                <th>Actions</th>

            </tr>


            <?php

            $displayedTotalDeaths = 0;

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

                    $displayedTotalDeaths +=
                        (int) $row['number_dead'];

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
                                $row['bird_batch']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo (int) $row['number_dead'];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['cause_of_death']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['mortality_date']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['notes']
                            );
                            ?>
                        </td>

                        <td>

                            <a
                                href="edit_mortality.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="delete_mortality.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                                onclick="return confirm(
                                    'Delete this mortality record?'
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
                            $filterDate !== ""
                        ){ ?>

                            No mortality records matched your search.

                        <?php }else{ ?>

                            No mortality records have been added yet.

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

            <a href="mortality.php?<?php
            echo htmlspecialchars(
                http_build_query(
                    $previousParameters
                )
            );
            ?>">

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
                href="mortality.php?<?php
                echo htmlspecialchars(
                    http_build_query(
                        $pageParameters
                    )
                );
                ?>"
                class="<?php
                echo $pageNumber === $page
                    ? 'active'
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

            <a href="mortality.php?<?php
            echo htmlspecialchars(
                http_build_query(
                    $nextParameters
                )
            );
            ?>">

                Next

            </a>

        <?php } ?>

    </div>

<?php } ?>


    <div class="mortality-summary">

      <h3>
    Birds Lost on This Page
</h3>
        <p>
            <?php
            echo $displayedTotalDeaths;
            ?>
        </p>

    </div>


</div>


</body>

</html>