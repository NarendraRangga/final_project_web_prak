<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $check_query = "SELECT * FROM users WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $error = "Username already exists. Please choose another.";
    } else {
        $insert_query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'user')";
        if (mysqli_query($conn, $insert_query)) {
            header("Location: login.php?msg=registered");
            exit();
        } else {
            $error = "Registration failed. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Autofix</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .login-container { max-width: 400px; margin: 100px auto; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; border-radius: 8px;}
        .login-container input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid var(--light-gray); border-radius: 4px;}
        .link-text { margin-top: 15px; display: block; font-size: 1.4rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="h2 section-title">Create Account</h2>
        <?php if(isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Choose a Username" required>
            <input type="password" name="password" placeholder="Create a Password" required>
            <button type="submit" class="btn" style="width:100%; justify-content:center;">Register</button>
        </form>
        <a href="login.php" class="link-text">Already have an account? Login here</a>
    </div>
</body>
</html>