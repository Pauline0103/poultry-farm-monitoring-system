<?php

session_start();

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

include "config/database.php";

$id = (int)$_GET['id'];

$result = mysqli_query(
$conn,
"SELECT * FROM mortality WHERE id=$id"
);

$row = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){

    $bird_batch = mysqli_real_escape_string(
    $conn,
    $_POST['bird_batch']);

    $number_dead = (int)$_POST['number_dead'];

    $cause_of_death = mysqli_real_escape_string(
    $conn,
    $_POST['cause_of_death']);

    $mortality_date = $_POST['mortality_date'];

    $notes = mysqli_real_escape_string(
    $conn,
    $_POST['notes']);

    mysqli_query($conn,

    "UPDATE mortality SET

    bird_batch='$bird_batch',

    number_dead='$number_dead',

    cause_of_death='$cause_of_death',

    mortality_date='$mortality_date',

    notes='$notes'

    WHERE id=$id");

    header("Location: mortality.php");

    exit();

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Edit Mortality</title>

<link rel="stylesheet"
href="assets/css/style.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">

<h1>Edit Mortality Record</h1>

<form method="POST">

<label>Bird Batch</label>

<input
type="text"
name="bird_batch"
value="<?php echo htmlspecialchars($row['bird_batch']); ?>"
required>

<label>Number Dead</label>

<input
type="number"
name="number_dead"
value="<?php echo $row['number_dead']; ?>"
required>

<label>Cause of Death</label>

<input
type="text"
name="cause_of_death"
value="<?php echo htmlspecialchars($row['cause_of_death']); ?>"
required>

<label>Mortality Date</label>

<input
type="date"
name="mortality_date"
value="<?php echo $row['mortality_date']; ?>"
required>

<label>Notes</label>

<textarea
name="notes"
rows="4"><?php echo htmlspecialchars($row['notes']); ?></textarea>

<br><br>

<button
type="submit"
name="update"
class="save-button">

Update Record

</button>

</form>

</div>

</body>

</html>