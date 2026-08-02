<?php

session_start();

include "config/database.php";

$errorMessage = "";


// Redirect users who are already logged in
if(isset($_SESSION['user_id'])){

    header("Location: dashboard.php");

    exit();

}


// Process the login form
if($_SERVER["REQUEST_METHOD"] === "POST"){

    $username = trim(
        $_POST['username'] ?? ""
    );

    $password =
        $_POST['password'] ?? "";


    if($username === "" || $password === ""){

        $errorMessage =
            "Please enter both your username and password.";

    }else{

        $sql = "
            SELECT
                id,
                username,
                email,
                password
            FROM users
            WHERE username = ?
            LIMIT 1
        ";

        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if($stmt){

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute(
                $stmt
            );

            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            if(
                $result &&
                mysqli_num_rows($result) === 1
            ){

                $user =
                    mysqli_fetch_assoc(
                        $result
                    );


                if(
                    password_verify(
                        $password,
                        $user['password']
                    )
                ){

                    session_regenerate_id(true);

                    $_SESSION['user_id'] =
                        (int) $user['id'];

                    $_SESSION['username'] =
                        $user['username'];

                    $_SESSION['email'] =
                        $user['email'];

                    header(
                        "Location: dashboard.php"
                    );

                    exit();

                }else{

                    $errorMessage =
                        "Invalid username or password.";

                }

            }else{

                $errorMessage =
                    "Invalid username or password.";

            }

            mysqli_stmt_close(
                $stmt
            );

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

    <title>Login | Poultry Management System</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body class="login-body">


<div class="modern-login-page">


    <!-- Left image section -->

    <div class="login-image-section">

        <div class="login-image-overlay">

            <div class="login-brand">

                <div class="login-logo">
                    🐔
                </div>

                <h1>
                    Poultry Farm
                    <span>Management System</span>
                </h1>

                <p>
                    Monitor bird batches, feed, sales,
                    vaccinations, mortality and farm expenses
                    from one secure system.
                </p>

            </div>

        </div>

    </div>


    <!-- Right login section -->

    <div class="login-form-section">

        <div class="modern-login-card">


            <div class="login-card-heading">

                <div class="mobile-login-logo">
                    🐔
                </div>

                <h2>Welcome Back</h2>

                <p>
                    Sign in to continue managing your poultry farm.
                </p>

            </div>


            <?php if($errorMessage !== ""){ ?>

                <div class="login-error-message">

                    <?php
                    echo htmlspecialchars(
                        $errorMessage
                    );
                    ?>

                </div>

            <?php } ?>


            <form
                method="POST"
                action=""
                class="modern-login-form"
            >


                <div class="login-form-group">

                    <label for="username">
                        Username
                    </label>

                    <div class="login-input-wrapper">

                        <span class="login-input-icon">
                            👤
                        </span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            value="<?php
                            echo htmlspecialchars(
                                $_POST['username'] ?? ""
                            );
                            ?>"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>


                <div class="login-form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="login-input-wrapper">

                        <span class="login-input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <div class="login-options">

                    <span>
                        Secure account login
                    </span>

                    <a href="change_password.php">
                        Change password
                    </a>

                </div>


                <button
                    type="submit"
                    class="modern-login-button"
                >

                    Login

                    <span>
                        →
                    </span>

                </button>


            </form>


            <div class="login-security-note">

                <span>🔐</span>

                <p>
                    Your password is securely protected.
                </p>

            </div>


            <div class="login-footer">

                <p>
                    Poultry Farm Management System
                </p>

                <span>
                    © <?php echo date("Y"); ?>
                    All rights reserved.
                </span>

            </div>


        </div>

    </div>


</div>


<script>

const passwordInput =
    document.getElementById("password");

const passwordToggle =
    document.getElementById("passwordToggle");

passwordToggle.addEventListener(
    "click",
    function(){

        const isPassword =
            passwordInput.type === "password";

        passwordInput.type =
            isPassword ? "text" : "password";

        passwordToggle.textContent =
            isPassword ? "Hide" : "Show";

        passwordToggle.setAttribute(
            "aria-label",
            isPassword
                ? "Hide password"
                : "Show password"
        );

    }
);

</script>


</body>

</html>