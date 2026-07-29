<?php

session_start();

include "config/database.php";

$id = $_GET['id'];

$sql = "SELECT * FROM feed WHERE id='$id'";

$result = mysqli_query($conn,$sql);

$row = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){

    $feed_name = $_POST['feed_name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $supplier = $_POST['supplier'];
    $purchase_date = $_POST['purchase_date'];

    $sql = "UPDATE feed SET

    feed_name='$feed_name',

    quantity='$quantity',

    price='$price',

    supplier='$supplier',

    purchase_date='$purchase_date'

    WHERE id='$id'";

    mysqli_query($conn,$sql);

    header("Location: feed.php");

    exit();

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Edit Feed</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">

<h2>Edit Feed Record</h2>

<form method="POST">

<label>Feed Name</label>

<input type="text" name="feed_name"
value="<?php echo $row['feed_name']; ?>" required>

<label>Quantity</label>

<input type="number" name="quantity"
value="<?php echo $row['quantity']; ?>" required>

<label>Price</label>

<input type="number" step="0.01"
name="price"
value="<?php echo $row['price']; ?>" required>

<label>Supplier</label>

<input type="text"
name="supplier"
value="<?php echo $row['supplier']; ?>" required>

<label>Purchase Date</label>

<input type="date"
name="purchase_date"
value="<?php echo $row['purchase_date']; ?>" required>

<br><br>

<button type="submit"
name="update"
class="save-button">

Update Feed

</button>

</form>

</div>

</body>

</html>