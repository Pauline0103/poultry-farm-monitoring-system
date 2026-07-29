<?php

session_start();

include "config/database.php";

$id = $_GET['id'];

$sql = "SELECT * FROM birds WHERE id='$id'";

$result = mysqli_query($conn,$sql);

$row = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){

$batch_name = $_POST['batch_name'];

$bird_type = $_POST['bird_type'];

$quantity = $_POST['quantity'];

$arrival_date = $_POST['arrival_date'];

$sql = "UPDATE birds SET

batch_name='$batch_name',

bird_type='$bird_type',

quantity='$quantity',

arrival_date='$arrival_date'

WHERE id='$id'";

mysqli_query($conn,$sql);

header("Location: birds.php");

exit();

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Edit Bird Batch</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="container">

<h2>Edit Bird Batch</h2>

<form method="POST">

<label>Batch Name</label>

<input type="text"

name="batch_name"

value="<?php echo $row['batch_name']; ?>"

required>

<label>Bird Type</label>

<input type="text"

name="bird_type"

value="<?php echo $row['bird_type']; ?>"

required>

<label>Quantity</label>

<input type="number"

name="quantity"

value="<?php echo $row['quantity']; ?>"

required>

<label>Arrival Date</label>

<input type="date"

name="arrival_date"

value="<?php echo $row['arrival_date']; ?>"

required>

<br><br>

<button type="submit"

name="update"

class="save-button">

Update Batch

</button>

</form>

</div>

</body>

</html>