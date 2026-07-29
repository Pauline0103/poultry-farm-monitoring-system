<?php

include "config/database.php";

if(isset($_POST['username'])){

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password)
            VALUES ('$username', '$email', '$password')";

    if(mysqli_query($conn, $sql)){

        echo "User saved successfully.";

    }else{

        echo "Error: " . mysqli_error($conn);

    }

}

?>

<!DOCTYPE html>
<html>

<head>

<title>Register</title>

</head>

<body>

<h2>Create User</h2>

<form method="POST" action="">

<label>Username</label>

<br>

<input type="text" name="username">

<br><br>

<label>Email</label>

<br>

<input type="email" name="email">

<br><br>

<label>Password</label>

<br>

<input type="password" name="password">

<br><br>

<button type="submit">Save User</button>

</form>

</body>

</html>