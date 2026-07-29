<?php

session_start();

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

include "config/database.php";

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){

    header("Location: vaccination.php");
    exit();

}

$id = (int) $_GET['id'];

$sql = "SELECT * FROM vaccination WHERE id = $id";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);

if(!$row){

    header("Location: vaccination.php");
    exit();

}

if(isset($_POST['update'])){

    $bird_batch = mysqli_real_escape_string($conn, $_POST['bird_batch']);

    $vaccine_name = mysqli_real_escape_string($conn, $_POST['vaccine_name']);

    $vaccination_date = mysqli_real_escape_string(
        $conn,
        $_POST['vaccination_date']
    );

    $next_due_date = mysqli_real_escape_string(
        $conn,
        $_POST['next_due_date']
    );

    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $sql = "UPDATE vaccination SET

            bird_batch = '$bird_batch',

            vaccine_name = '$vaccine_name',

            vaccination_date = '$vaccination_date',

            next_due_date = '$next_due_date',

            notes = '$notes'

            WHERE id = $id";

    mysqli_query($conn, $sql);

    header("Location: vaccination.php");

    exit();

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Edit Vaccination</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">

    <h1>Edit Vaccination Record</h1>

    <form method="POST">

        <label>Bird Batch</label>

        <input
            type="text"
            name="bird_batch"
            value="<?php echo htmlspecialchars($row['bird_batch']); ?>"
            required
        >

        <label>Vaccine Name</label>

        <input
            type="text"
            name="vaccine_name"
            value="<?php echo htmlspecialchars($row['vaccine_name']); ?>"
            required
        >

        <label>Vaccination Date</label>

        <input
            type="date"
            name="vaccination_date"
            value="<?php echo $row['vaccination_date']; ?>"
            required
        >

        <label>Next Due Date</label>

        <input
            type="date"
            name="next_due_date"
            value="<?php echo $row['next_due_date']; ?>"
            required
        >

        <label>Notes</label>

        <textarea
            name="notes"
            rows="4"
        ><?php echo htmlspecialchars($row['notes']); ?></textarea>

        <br><br>

        <button
            type="submit"
            name="update"
            class="save-button"
        >
            Update Vaccination
        </button>

    </form>

</div>

</body>

</html>