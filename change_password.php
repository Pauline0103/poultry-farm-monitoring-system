<?php

session_start();

include "config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$success = "";
$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if(empty($currentPassword) || empty($newPassword) || empty($confirmPassword)){

        $error = "Please fill in all fields.";

    }elseif(strlen($newPassword) < 6){

        $error = "New password must be at least 6 characters.";

    }elseif($newPassword != $confirmPassword){

        $error = "New passwords do not match.";

    }else{

        $sql = "SELECT password FROM users WHERE id=?";

        $stmt = mysqli_prepare($conn,$sql);

        mysqli_stmt_bind_param($stmt,"i",$userId);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        if(password_verify($currentPassword,$user['password'])){

            $hashedPassword = password_hash($newPassword,PASSWORD_DEFAULT);

            $update = "UPDATE users SET password=? WHERE id=?";

            $stmt2 = mysqli_prepare($conn,$update);

            mysqli_stmt_bind_param($stmt2,"si",$hashedPassword,$userId);

            if(mysqli_stmt_execute($stmt2)){

                $success = "Password changed successfully.";

            }else{

                $error = "Unable to update password.";

            }

        }else{

            $error = "Current password is incorrect.";

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Change Password</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">

<div class="settings-page">

<h1>Change Password</h1>

<?php if($success!=""){ ?>

<div class="settings-success-message">
<?php echo $success; ?>
</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="settings-error-message">
<?php echo $error; ?>
</div>

<?php } ?>

<div class="settings-card">

<form method="POST">

<div class="settings-form-group">

<label>Current Password</label>

<input type="password" name="current_password" required>

</div>

<div class="settings-form-group">

<label>New Password</label>

<input type="password" name="new_password" required>

</div>

<div class="settings-form-group">

<label>Confirm New Password</label>

<input type="password" name="confirm_password" required>

</div>

<div class="settings-actions">

<button class="settings-save-button" type="submit">

Change Password

</button>

<a href="settings.php" class="settings-cancel-button">

Back

</a>

</div>

</form>

</div>

</div>

</div>

</body>

</html>