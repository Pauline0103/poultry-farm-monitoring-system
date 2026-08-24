<?php

session_start();

if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}

include "config/database.php";

// Retrieve bird batches for the mortality form
$birdBatches = [];

$batchSql = "
    SELECT
        id,
        batch_name,
        quantity
    FROM birds
    ORDER BY arrival_date DESC, id DESC
";

$batchResult = mysqli_query(
    $conn,
    $batchSql
);

if($batchResult){

    while(
        $batchRow =
        mysqli_fetch_assoc($batchResult)
    ){

        $birdBatches[] = $batchRow;

    }

}



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
if(isset($_POST['save']))

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


    /*
    Start a transaction so that the selected batch
    is checked before the mortality record is saved.
    */
    mysqli_begin_transaction($conn);


    // Find the selected bird batch
    $batchCheckSql = "
        SELECT
            id,
            batch_name,
            quantity
        FROM birds
        WHERE batch_name = ?
        LIMIT 1
        FOR UPDATE
    ";

    $batchCheckStatement =
        mysqli_prepare(
            $conn,
            $batchCheckSql
        );


    if(!$batchCheckStatement){

        mysqli_rollback($conn);

        $errorMessage =
            "The selected bird batch could not be checked.";

    }else{


        mysqli_stmt_bind_param(
            $batchCheckStatement,
            "s",
            $birdBatch
        );

        mysqli_stmt_execute(
            $batchCheckStatement
        );

        $batchCheckResult =
            mysqli_stmt_get_result(
                $batchCheckStatement
            );

        $batchRecord =
            mysqli_fetch_assoc(
                $batchCheckResult
            );

        mysqli_stmt_close(
            $batchCheckStatement
        );


        if(!$batchRecord){

            mysqli_rollback($conn);

            $errorMessage =
                "The selected bird batch does not exist.";

        }else{


            $batchQuantity =
                (int) $batchRecord['quantity'];


            // Calculate birds already sold
            $soldSql = "
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
                    $soldSql
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


                // Calculate previous mortality
                $previousMortalitySql = "
                    SELECT
                        COALESCE(
                            SUM(number_dead),
                            0
                        ) AS total_dead
                    FROM mortality
                    WHERE bird_batch = ?
                ";

                $previousMortalityStatement =
                    mysqli_prepare(
                        $conn,
                        $previousMortalitySql
                    );


                if(!$previousMortalityStatement){

                    mysqli_rollback($conn);

                    $errorMessage =
                        "Previous mortality records could not be checked.";

                }else{


                    mysqli_stmt_bind_param(
                        $previousMortalityStatement,
                        "s",
                        $birdBatch
                    );

                    mysqli_stmt_execute(
                        $previousMortalityStatement
                    );

                    $previousMortalityResult =
                        mysqli_stmt_get_result(
                            $previousMortalityStatement
                        );

                    $previousMortalityRecord =
                        mysqli_fetch_assoc(
                            $previousMortalityResult
                        );

                    $alreadyDead =
                        (int) (
                            $previousMortalityRecord['total_dead'] ?? 0
                        );

                    mysqli_stmt_close(
                        $previousMortalityStatement
                    );


                    // Calculate birds currently available
                    $availableBirds =
                        $batchQuantity -
                        $alreadySold -
                        $alreadyDead;


                    if($availableBirds < 0){

                        $availableBirds = 0;

                    }


                    // No birds remain
                    if($availableBirds === 0){

                        mysqli_rollback($conn);

                        $errorMessage =
                            "No birds remain in the selected batch.";

                    }


                    // Mortality cannot exceed remaining stock
                    elseif(
                        $numberDeadValue >
                        $availableBirds
                    ){

                        mysqli_rollback($conn);

                        $errorMessage =
                            "You cannot record " .
                            $numberDeadValue .
                            " dead bird(s). Only " .
                            $availableBirds .
                            " bird(s) remain in " .
                            htmlspecialchars(
                                $birdBatch
                            ) .
                            ".";

                    }


                    else{


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

                                mysqli_commit($conn);

                                $successMessage =
                                    "Mortality record saved successfully. " .
                                    $numberDeadValue .
                                    " bird(s) were recorded from " .
                                    htmlspecialchars(
                                        $birdBatch
                                    ) .
                                    ".";


                                $birdBatch = "";
                                $numberDead = "";
                                $causeOfDeath = "";
                                $mortalityDate = "";
                                $notes = "";

                            }else{

                                mysqli_rollback($conn);

                                $errorMessage =
                                    "The mortality record could not be saved.";

                            }


                            mysqli_stmt_close(
                                $insertStatement
                            );

                        }else{

                            mysqli_rollback($conn);

                            $errorMessage =
                                "Unable to prepare the mortality record.";

                        }

                    }

                }

            }

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


    <div class="page-header clean-page-header">

    <div class="page-header-content">

        <h1>Mortality Management</h1>

        <p>
            Record bird losses, document possible causes
            and monitor mortality records.
        </p>

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

            <h2>Record Mortality</h2>

            <p>
                Record bird losses and document the possible
                cause of death.
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

    <label for="bird_batch">
        Bird Batch
    </label>

    <select
        id="bird_batch"
        name="bird_batch"
        required
    >

        <option value="">
            Select bird batch
        </option>

        <?php foreach(
            $birdBatches as $batch
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

    </select>

</div>

            <div class="form-field">

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

            </div>


            <div class="form-field">

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

            </div>


            <div class="form-field">

                <label for="mortality_date">
                    Mortality Date
                </label>

                <input
                    type="date"
                    id="mortality_date"
                    name="mortality_date"
                    max="<?php
                    echo date('Y-m-d');
                    ?>"
                    value="<?php
                    echo htmlspecialchars(
                        $mortalityDate
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-field full-width-field">

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

            </div>

        </div>


        <div class="form-actions">

            <button
                type="submit"
                name="save"
                class="primary-action-button"
            >
                Save Mortality Record
            </button>

        </div>

    </form>

</div>


    <!-- Search panel -->

  <div class="module-card search-module-card">

    <div class="module-card-header">

        <div>

            <h2>Search Mortality Records</h2>

            <p>
                Search by bird batch or cause of death
                and filter records by mortality date.
            </p>

        </div>

    </div>

    <form
        method="GET"
        action="mortality.php"
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
                    placeholder="Bird batch or cause of death"
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                >

            </div>

            <div class="modern-search-field">

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

        <div class="modern-search-actions">

            <button
                type="submit"
                class="primary-action-button"
            >
                Search
            </button>

            <a
                href="mortality.php"
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

            <h2>Mortality Records</h2>

            <p>
                Review bird losses, causes of death and mortality dates.
            </p>

        </div>

    </div>


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

    <div class="table-actions">

        <a
            href="edit_mortality.php?id=<?php
            echo (int) $row['id'];
            ?>"
            class="table-action-button edit-action"
        >
            Edit
        </a>

        <a
            href="delete_mortality.php?id=<?php
            echo (int) $row['id'];
            ?>"
            class="table-action-button delete-action"
            onclick="return confirm(
                'Delete this mortality record?'
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

           <a
    href="mortality.php?<?php
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
                href="mortality.php?<?php
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
    href="mortality.php?<?php
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

</div>


</body>

</html>