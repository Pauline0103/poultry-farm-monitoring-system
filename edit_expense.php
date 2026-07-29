<?php

session_start();

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

include "config/database.php";

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){

    header("Location: expenses.php");
    exit();

}

$id = (int) $_GET['id'];

$sql = "SELECT * FROM expenses WHERE id = $id";

$result = mysqli_query($conn, $sql);

$expense = mysqli_fetch_assoc($result);

if(!$expense){

    header("Location: expenses.php");
    exit();

}

if(isset($_POST['update'])){

    $expense_name = mysqli_real_escape_string(
        $conn,
        $_POST['expense_name']
    );

    $expense_category = mysqli_real_escape_string(
        $conn,
        $_POST['expense_category']
    );

    $amount = (float) $_POST['amount'];

    $expense_date = mysqli_real_escape_string(
        $conn,
        $_POST['expense_date']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['description']
    );

    if($amount > 0){

        $sql = "UPDATE expenses SET

                expense_name = '$expense_name',

                expense_category = '$expense_category',

                amount = '$amount',

                expense_date = '$expense_date',

                description = '$description'

                WHERE id = $id";

        mysqli_query($conn, $sql);

        header("Location: expenses.php");

        exit();

    }

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Edit Expense</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">

    <h1>Edit Expense Record</h1>

    <form method="POST">

        <label>Expense Name</label>

        <input
            type="text"
            name="expense_name"
            value="<?php echo htmlspecialchars($expense['expense_name']); ?>"
            required
        >


        <label>Expense Category</label>

        <select name="expense_category" required>

            <option value="">
                Select an expense category
            </option>

            <option
                value="Feed"
                <?php if($expense['expense_category'] == "Feed") echo "selected"; ?>
            >
                Feed
            </option>

            <option
                value="Vaccines"
                <?php if($expense['expense_category'] == "Vaccines") echo "selected"; ?>
            >
                Vaccines
            </option>

            <option
                value="Medicine"
                <?php if($expense['expense_category'] == "Medicine") echo "selected"; ?>
            >
                Medicine
            </option>

            <option
                value="Transport"
                <?php if($expense['expense_category'] == "Transport") echo "selected"; ?>
            >
                Transport
            </option>

            <option
                value="Utilities"
                <?php if($expense['expense_category'] == "Utilities") echo "selected"; ?>
            >
                Electricity and Water
            </option>

            <option
                value="Equipment"
                <?php if($expense['expense_category'] == "Equipment") echo "selected"; ?>
            >
                Equipment
            </option>

            <option
                value="Labour"
                <?php if($expense['expense_category'] == "Labour") echo "selected"; ?>
            >
                Labour
            </option>

            <option
                value="Other"
                <?php if($expense['expense_category'] == "Other") echo "selected"; ?>
            >
                Other
            </option>

        </select>


        <label>Amount</label>

        <input
            type="number"
            name="amount"
            step="0.01"
            min="0.01"
            value="<?php echo $expense['amount']; ?>"
            required
        >


        <label>Expense Date</label>

        <input
            type="date"
            name="expense_date"
            value="<?php echo $expense['expense_date']; ?>"
            required
        >


        <label>Description</label>

        <textarea
            name="description"
            rows="4"
        ><?php echo htmlspecialchars($expense['description']); ?></textarea>


        <button
            type="submit"
            name="update"
            class="save-button"
        >
            Update Expense
        </button>

    </form>

</div>

</body>

</html>