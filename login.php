<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Autofix</title>
    <link rel="stylesheet" href="./assets/css/style.css"> <style>

        .login-container { max-width: 400px; margin: 100px auto; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        .login-container input { width: 100%; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="login-container">
        
        <a href="index.php" style="display: block; text-align: left; margin-bottom: 20px; color: var(--sonic-silver); font-weight: bold; text-decoration: none;">
            &larr; Back to Home
        </a>

        <h2 class="h2 section-title">Login to Autofix</h2>
        
        <?php if(isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'registered') { echo "<p style='color:green;'>Registration successful! Please login.</p>"; } ?>
        
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn" style="width:100%; justify-content:center">Login</button>
        </form>
        
        <a href="register.php" class="link-text" style="display:block; margin-top:15px; font-size:1.4rem;">Need an account? Register</a>
    </div>
</body>
</html>