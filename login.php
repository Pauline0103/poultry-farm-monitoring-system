<?php

session_start();

include "config/database.php";

$errorMessage = "";


// If the user is already logged in, go to the dashboard
if(isset($_SESSION['user_id'])){

    header("Location: dashboard.php");

    exit();

}


// Process the login form
if($_SERVER["REQUEST_METHOD"] === "POST"){

    $username = trim($_POST['username'] ?? "");

    $password = $_POST['password'] ?? "";


    // Check for empty fields
    if($username === "" || $password === ""){

        $errorMessage =
            "Please enter both your username and password.";

    }else{

        // Retrieve the user securely
        $sql = "SELECT id, username, email, password
                FROM users
                WHERE username = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);


        if($stmt){

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);


            if(
                $result &&
                mysqli_num_rows($result) === 1
            ){

                $user = mysqli_fetch_assoc($result);


                // Verify the entered password
                if(
                    password_verify(
                        $password,
                        $user['password']
                    )
                ){

                    // Create a fresh session ID after login
                    session_regenerate_id(true);


                    // Store important user details
                    $_SESSION['user_id'] =
                        (int) $user['id'];

                    $_SESSION['username'] =
                        $user['username'];

                    $_SESSION['email'] =
                        $user['email'];


                    header("Location: dashboard.php");

                    exit();

                }else{

                    $errorMessage =
                        "Invalid username or password.";

                }

            }else{

                $errorMessage =
                    "Invalid username or password.";

            }

            mysqli_stmt_close($stmt);

        }else{

            $errorMessage =
                "Unable to process your login at the moment.";

        }

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

    <title>Login</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<div class="container">

    <h1>Poultry Management System</h1>

    <h2>Welcome Back</h2>


    <?php if($errorMessage !== ""){ ?>

        <div class="login-error-message">

            <?php
            echo htmlspecialchars($errorMessage);
            ?>

        </div>

    <?php } ?>


    <form method="POST" action="">


        <label for="username">
            Username
        </label>

        <br>

        <input
            type="text"
            id="username"
            name="username"
            value="<?php
            echo htmlspecialchars(
                $_POST['username'] ?? ''
            );
            ?>"
            autocomplete="username"
            required
        >

        <br><br>


        <label for="password">
            Password
        </label>

        <br>

        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <br><br>


        <button type="submit">
            Login
        </button>


    </form>

</div>


</body>

</html>