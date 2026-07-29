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


// Retrieve feed records
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
            "The feed records could not be searched.";

    }

}elseif($filterDate !== ""){

    $recordsSql = "
        SELECT *
        FROM feed
        WHERE purchase_date = ?
        ORDER BY purchase_date DESC, id DESC
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
            "The feed records could not be filtered.";

    }

}else{

    $recordsSql = "
        SELECT *
        FROM feed
        ORDER BY purchase_date DESC, id DESC
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

    <title>Feed Management</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <h1>Feed Management</h1>

    <p>
        Add feed purchases and manage existing feed records.
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


        <label for="price">
            Price (K)
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
            max="<?php echo date('Y-m-d'); ?>"
            required
        >


        <br><br>


        <button
            type="submit"
            name="save"
            class="save-button"
        >

            Save Feed

        </button>


    </form>


    <br><br>


    <div class="search-panel">

        <form method="GET" action="feed.php">

            <div class="search-grid">


                <div>

                    <label for="search">
                        Search Feed Records
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


                <div>

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


            <br>


            <button
                type="submit"
                class="save-button"
            >

                Search

            </button>


            <a
                href="feed.php"
                class="search-reset-button"
            >

                Reset

            </a>


        </form>

    </div>


    <br>


    <h2>Feed Records</h2>


    <?php if(
        $search !== "" ||
        $filterDate !== ""
    ){ ?>

        <p class="search-result-text">

            Showing filtered feed records.

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

                            <a
                                href="edit_feed.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="delete_feed.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                                onclick="return confirm(
                                    'Delete this feed record?'
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

                            No feed records matched your search.

                        <?php }else{ ?>

                            No feed records have been added yet.

                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>


        </table>

    </div>


</div>


</body>

</html>