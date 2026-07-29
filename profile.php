<?php

session_start();


// Protect the page from users who are not logged in
if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}


// Store the logged-in username
$username = $_SESSION['username'];

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Profile</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<?php include "includes/sidebar.php"; ?>


<div class="content">

    <div class="profile-page">


        <div class="profile-header">

            <div class="profile-avatar">

                <?php

                echo strtoupper(
                    substr(
                        htmlspecialchars($username),
                        0,
                        1
                    )
                );

                ?>

            </div>


            <div>

                <h1>User Profile</h1>

                <p>
                    View your poultry system account information.
                </p>

            </div>

        </div>


        <div class="profile-card">

            <h2>Account Information</h2>


            <div class="profile-information-row">

                <span>Username</span>

                <strong>

                    <?php
                    echo htmlspecialchars($username);
                    ?>

                </strong>

            </div>


            <div class="profile-information-row">

                <span>Account status</span>

                <strong class="profile-active-status">
                    Active
                </strong>

            </div>


            <div class="profile-information-row">

                <span>System access</span>

                <strong>
                    Poultry Farm Management System
                </strong>

            </div>


            <div class="profile-information-row">

                <span>Current session</span>

                <strong>
                    Logged in
                </strong>

            </div>


           <div class="profile-actions">

    <a
        href="dashboard.php"
        class="profile-dashboard-button"
    >
        Return to Dashboard
    </a>

    <a
        href="settings.php"
        class="profile-settings-button"
    >
        Account Settings
    </a>

    <a
        href="logout.php"
        class="profile-logout-button"
    >
        Logout
    </a>

</div>

        </div>


        <div class="profile-note">

            <h3>Account Security</h3>

            <p>
                Always log out after using the system on a shared computer.
            </p>

        </div>


    </div>

</div>


</body>

</html>