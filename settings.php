<?php

session_start();

include "config/database.php";


// Protect the page
if(!isset($_SESSION['user_id'])){

    header("Location: login.php");

    exit();

}


// Store the logged-in user's ID
$userId = (int) $_SESSION['user_id'];


// Create message variables
$successMessage = "";

$errorMessage = "";


// Retrieve the current user's information
$sql = "SELECT id, username, email, created_at
        FROM users
        WHERE id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);


if(!$stmt){

    die("Unable to load the user account.");

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// If the user no longer exists
if(!$user){

    session_unset();

    session_destroy();

    header("Location: login.php");

    exit();

}


// Store values for the form
$username = $user['username'];

$email = $user['email'];


// Process the update form
if($_SERVER["REQUEST_METHOD"] === "POST"){

    $username = trim(
        $_POST['username'] ?? ""
    );

    $email = trim(
        $_POST['email'] ?? ""
    );


    // Check for empty fields
    if($username === "" || $email === ""){

        $errorMessage =
            "Please enter both your username and email address.";

    }


    // Check username length
    elseif(strlen($username) < 3){

        $errorMessage =
            "The username must contain at least 3 characters.";

    }


    // Check username maximum length
    elseif(strlen($username) > 100){

        $errorMessage =
            "The username must not exceed 100 characters.";

    }


    // Validate email address
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){

        $errorMessage =
            "Please enter a valid email address.";

    }


    // Check email maximum length
    elseif(strlen($email) > 150){

        $errorMessage =
            "The email address must not exceed 150 characters.";

    }


    else{

        /*
        Check whether another user already has
        the entered username or email address.
        */

        $checkSql = "SELECT id
                     FROM users
                     WHERE
                     (username = ? OR email = ?)
                     AND id != ?
                     LIMIT 1";

        $checkStmt = mysqli_prepare(
            $conn,
            $checkSql
        );


        if($checkStmt){

            mysqli_stmt_bind_param(
                $checkStmt,
                "ssi",
                $username,
                $email,
                $userId
            );

            mysqli_stmt_execute(
                $checkStmt
            );

            $checkResult =
                mysqli_stmt_get_result(
                    $checkStmt
                );

            $duplicateUser =
                mysqli_fetch_assoc(
                    $checkResult
                );

            mysqli_stmt_close(
                $checkStmt
            );


            if($duplicateUser){

                $errorMessage =
                    "That username or email address is already being used.";

            }else{

                // Update the user's account
                $updateSql = "UPDATE users
                              SET username = ?,
                                  email = ?
                              WHERE id = ?";

                $updateStmt =
                    mysqli_prepare(
                        $conn,
                        $updateSql
                    );


                if($updateStmt){

                    mysqli_stmt_bind_param(
                        $updateStmt,
                        "ssi",
                        $username,
                        $email,
                        $userId
                    );


                    if(
                        mysqli_stmt_execute(
                            $updateStmt
                        )
                    ){

                        // Update the session details
                        $_SESSION['username'] =
                            $username;

                        $_SESSION['email'] =
                            $email;


                        $successMessage =
                            "Your account information was updated successfully.";


                        // Refresh the stored user data
                        $user['username'] =
                            $username;

                        $user['email'] =
                            $email;

                    }else{

                        $errorMessage =
                            "The account could not be updated. Please try again.";

                    }

                    mysqli_stmt_close(
                        $updateStmt
                    );

                }else{

                    $errorMessage =
                        "Unable to prepare the account update.";

                }

            }

        }else{

            $errorMessage =
                "Unable to check the account information.";

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

    <title>Account Settings</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">


    <div class="settings-page">


        <div class="settings-header">

            <div>

                <h1>Account Settings</h1>

                <p>
                    Update your username and email address.
                </p>

            </div>

        </div>


        <?php if($successMessage !== ""){ ?>

            <div class="settings-success-message">

                <?php
                echo htmlspecialchars(
                    $successMessage
                );
                ?>

            </div>

        <?php } ?>


        <?php if($errorMessage !== ""){ ?>

            <div class="settings-error-message">

                <?php
                echo htmlspecialchars(
                    $errorMessage
                );
                ?>

            </div>

        <?php } ?>


        <div class="settings-card">


            <h2>Account Information</h2>


            <form method="POST" action="">


                <div class="settings-form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php
                        echo htmlspecialchars(
                            $username
                        );
                        ?>"
                        maxlength="100"
                        required
                    >

                    <small>
                        Your username must contain at least 3 characters.
                    </small>

                </div>


                <div class="settings-form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php
                        echo htmlspecialchars(
                            $email
                        );
                        ?>"
                        maxlength="150"
                        required
                    >

                    <small>
                        Enter a valid email address.
                    </small>

                </div>


                <div class="settings-form-group">

                    <label>
                        Account Created
                    </label>

                    <input
                        type="text"
                        value="<?php
                        echo htmlspecialchars(
                            $user['created_at']
                        );
                        ?>"
                        disabled
                    >

                    <small>
                        The account creation date cannot be changed.
                    </small>

                </div>


                <div class="settings-actions">

                    <button
                        type="submit"
                        class="settings-save-button"
                    >
                        Save Changes
                    </button>


                    <a
                        href="profile.php"
                        class="settings-cancel-button"
                    >
                        Cancel
                    </a>

                </div>


            </form>


        </div>


        <div class="settings-security-card">

            <div>

                <h3>Password and Security</h3>

                <p>
                    Keep your account secure by changing your password regularly.
                </p>

            </div>

            <a href="change_password.php">
                Change Password
            </a>

        </div>


    </div>


</div>


</body>

</html>