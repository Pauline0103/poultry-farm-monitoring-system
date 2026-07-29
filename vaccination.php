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


// Preserve form values
$birdBatch = "";

$vaccineName = "";

$vaccinationDate = "";

$nextDueDate = "";

$notes = "";


// Save vaccination record
if(isset($_POST['save'])){

    $birdBatch = trim(
        $_POST['bird_batch'] ?? ""
    );

    $vaccineName = trim(
        $_POST['vaccine_name'] ?? ""
    );

    $vaccinationDate = trim(
        $_POST['vaccination_date'] ?? ""
    );

    $nextDueDate = trim(
        $_POST['next_due_date'] ?? ""
    );

    $notes = trim(
        $_POST['notes'] ?? ""
    );


    // Check required fields
    if(
        $birdBatch === "" ||
        $vaccineName === "" ||
        $vaccinationDate === "" ||
        $nextDueDate === ""
    ){

        $errorMessage =
            "Please complete all the required fields.";

    }


    // Prevent future vaccination date
    elseif(
        $vaccinationDate >
        date("Y-m-d")
    ){

        $errorMessage =
            "The vaccination date cannot be in the future.";

    }


    // Next due date cannot be earlier
    elseif(
        $nextDueDate <
        $vaccinationDate
    ){

        $errorMessage =
            "The next due date cannot be earlier than the vaccination date.";

    }


    else{

        $insertSql = "
            INSERT INTO vaccination
            (
                bird_batch,
                vaccine_name,
                vaccination_date,
                next_due_date,
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
                "sssss",
                $birdBatch,
                $vaccineName,
                $vaccinationDate,
                $nextDueDate,
                $notes
            );


            if(
                mysqli_stmt_execute(
                    $insertStatement
                )
            ){

                $successMessage =
                    "Vaccination record saved successfully.";


                // Clear form after saving
                $birdBatch = "";

                $vaccineName = "";

                $vaccinationDate = "";

                $nextDueDate = "";

                $notes = "";

            }else{

                $errorMessage =
                    "The vaccination record could not be saved.";

            }

            mysqli_stmt_close(
                $insertStatement
            );

        }else{

            $errorMessage =
                "Unable to prepare the vaccination record.";

        }

    }

}


// Retrieve vaccination records
if(
    $search !== "" &&
    $filterDate !== ""
){

    $searchValue =
        "%" . $search . "%";

    $recordsSql = "
        SELECT *
        FROM vaccination
        WHERE
        (
            bird_batch LIKE ?
            OR vaccine_name LIKE ?
        )
        AND vaccination_date = ?
        ORDER BY vaccination_date DESC, id DESC
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );


    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "sss",
            $searchValue,
            $searchValue,
            $filterDate
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
            "The vaccination records could not be searched.";

    }

}elseif($search !== ""){

    $searchValue =
        "%" . $search . "%";

    $recordsSql = "
        SELECT *
        FROM vaccination
        WHERE
            bird_batch LIKE ?
            OR vaccine_name LIKE ?
        ORDER BY vaccination_date DESC, id DESC
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );


    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "ss",
            $searchValue,
            $searchValue
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
            "The vaccination records could not be searched.";

    }

}elseif($filterDate !== ""){

    $recordsSql = "
        SELECT *
        FROM vaccination
        WHERE vaccination_date = ?
        ORDER BY vaccination_date DESC, id DESC
    ";

    $recordsStatement =
        mysqli_prepare(
            $conn,
            $recordsSql
        );


    if($recordsStatement){

        mysqli_stmt_bind_param(
            $recordsStatement,
            "s",
            $filterDate
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
            "The vaccination records could not be filtered.";

    }

}else{

    $recordsSql = "
        SELECT *
        FROM vaccination
        ORDER BY vaccination_date DESC, id DESC
    ";

    $result =
        mysqli_query(
            $conn,
            $recordsSql
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

    <title>Vaccination Management</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <h1>Vaccination Management</h1>

    <p>
        Record vaccinations and monitor upcoming vaccine due dates.
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
            value="<?php
            echo htmlspecialchars(
                $birdBatch
            );
            ?>"
            required
        >


        <label for="vaccine_name">
            Vaccine Name
        </label>

        <input
            type="text"
            id="vaccine_name"
            name="vaccine_name"
            value="<?php
            echo htmlspecialchars(
                $vaccineName
            );
            ?>"
            required
        >


        <label for="vaccination_date">
            Vaccination Date
        </label>

        <input
            type="date"
            id="vaccination_date"
            name="vaccination_date"
            value="<?php
            echo htmlspecialchars(
                $vaccinationDate
            );
            ?>"
            max="<?php echo date('Y-m-d'); ?>"
            required
        >


        <label for="next_due_date">
            Next Due Date
        </label>

        <input
            type="date"
            id="next_due_date"
            name="next_due_date"
            value="<?php
            echo htmlspecialchars(
                $nextDueDate
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

            Save Vaccination

        </button>


    </form>


    <br><br>


    <!-- Search panel -->

    <div class="search-panel">

        <form
            method="GET"
            action="vaccination.php"
        >

            <div class="search-grid">


                <div>

                    <label for="search">
                        Search Vaccination Records
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Bird batch or vaccine name"
                        value="<?php
                        echo htmlspecialchars(
                            $search
                        );
                        ?>"
                    >

                </div>


                <div>

                    <label for="filter_date">
                        Vaccination Date
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
                href="vaccination.php"
                class="search-reset-button"
            >

                Reset

            </a>


        </form>

    </div>


    <br>


    <h2>Vaccination Records</h2>


    <?php if(
        $search !== "" ||
        $filterDate !== ""
    ){ ?>

        <p class="search-result-text">
            Showing filtered vaccination records.
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

                <th>Bird Batch</th>

                <th>Vaccine</th>

                <th>Vaccination Date</th>

                <th>Next Due Date</th>

                <th>Status</th>

                <th>Notes</th>

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


                    <?php

                    $today =
                        date("Y-m-d");

                    $threeDaysLater =
                        date(
                            "Y-m-d",
                            strtotime("+3 days")
                        );


                    if(
                        $row['next_due_date'] <
                        $today
                    ){

                        $status = "Overdue";

                        $statusClass =
                            "status-overdue";

                    }elseif(
                        $row['next_due_date'] ===
                        $today
                    ){

                        $status = "Due Today";

                        $statusClass =
                            "status-today";

                    }elseif(
                        $row['next_due_date'] <=
                        $threeDaysLater
                    ){

                        $status = "Due Soon";

                        $statusClass =
                            "status-soon";

                    }else{

                        $status = "Scheduled";

                        $statusClass =
                            "status-scheduled";

                    }

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
                            echo htmlspecialchars(
                                $row['vaccine_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['vaccination_date']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['next_due_date']
                            );
                            ?>
                        </td>

                        <td>

                            <span
                                class="<?php
                                echo htmlspecialchars(
                                    $statusClass
                                );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $status
                                );
                                ?>

                            </span>

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
                                href="edit_vaccination.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="delete_vaccination.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                                onclick="return confirm(
                                    'Are you sure you want to delete this vaccination record?'
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

                        <?php if(
                            $search !== "" ||
                            $filterDate !== ""
                        ){ ?>

                            No vaccination records matched your search.

                        <?php }else{ ?>

                            No vaccination records have been added yet.

                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>


        </table>

    </div>


</div>


</body>

</html>