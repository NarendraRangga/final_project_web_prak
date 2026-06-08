<?php
session_start();
require 'db.php';

// Protect the page: Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// --- 1. HANDLE ADD NEW USER ---
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Sticking to plain text as requested for this MVP
    $role = $_POST['role'];

    // Check if username already exists
    $check_query = "SELECT * FROM users WHERE username = '$username'";
    if (mysqli_num_rows(mysqli_query($conn, $check_query)) > 0) {
        $error_msg = "Username already exists!";
    } else {
        $insert_query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        if (mysqli_query($conn, $insert_query)) {
            $success_msg = "User added successfully!";
        } else {
            $error_msg = "Failed to add user.";
        }
    }
}

// --- 2. HANDLE DELETE USER ---
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Prevent the admin from deleting themselves
    if ($delete_id == $_SESSION['user_id']) {
        $error_msg = "You cannot delete your own admin account while logged in.";
    } else {
        $delete_query = "DELETE FROM users WHERE id='$delete_id'";
        if (mysqli_query($conn, $delete_query)) {
            header("Location: manage_users.php?msg=deleted");
            exit();
        } else {
            $error_msg = "Cannot delete this user because they have registered cars or bookings.";
        }
    }
}

// --- 3. HANDLE EDIT/UPDATE USER ---
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    
    // Only update password if they typed a new one
    $password_update = "";
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $password_update = ", password='$password'";
    }

    $update_query = "UPDATE users SET username='$username', role='$role' $password_update WHERE id='$id'";
    if (mysqli_query($conn, $update_query)) {
        $success_msg = "User updated successfully!";
    } else {
        $error_msg = "Failed to update user.";
    }
}

// Fetch all users
$users_result = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");

// Check if we are in "Edit" mode
$edit_mode = false;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($conn, "SELECT * FROM users WHERE id='$edit_id'");
    $edit_data = mysqli_fetch_assoc($edit_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Autofix</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .admin-container { max-width: 900px; margin: 80px auto; padding: 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .form-card { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid var(--light-gray); }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: var(--eerie-black); }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--light-gray); border-radius: 5px; font-family: var(--ff-mulish); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: var(--shadow); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--light-gray); }
        th { background-color: var(--space-cadet); color: var(--white); font-family: var(--ff-chakra-petch); text-transform: uppercase; }
        tr:hover { background-color: #f9f9f9; }
        .action-link { margin-right: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container admin-container">
        <div class="header-top">
            <h2 class="h2">Manage Users</h2>
            <div>
                <a href="admin_dashboard.php" class="btn-link" style="margin-right: 15px; display: inline-block;">Dashboard</a>
                <a href="manage_services.php" class="btn-link" style="margin-right: 15px; display: inline-block;">Services</a>
                <a href="logout.php" class="btn-link" style="color: red; display: inline-block;">Logout</a>
            </div>
        </div>

        <?php 
        if(isset($success_msg)) echo "<p style='color: green; font-weight: bold; margin-bottom: 15px;'>$success_msg</p>"; 
        if(isset($error_msg)) echo "<p style='color: red; font-weight: bold; margin-bottom: 15px;'>$error_msg</p>"; 
        if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<p style='color: green; font-weight: bold; margin-bottom: 15px;'>User deleted successfully!</p>"; 
        ?>

        <div class="form-card">
            <?php if($edit_mode): ?>
                <h3 class="h3" style="margin-bottom: 15px;">Edit User</h3>
                <form method="POST" action="manage_users.php">
                    <input type="hidden" name="user_id" value="<?php echo $edit_data['id']; ?>">
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 2;">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo $edit_data['username']; ?>" required>
                        </div>
                        <div class="form-group" style="flex: 2;">
                            <label>Password (Leave blank to keep current)</label>
                            <input type="password" name="password" placeholder="New Password">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="user" <?php if($edit_data['role'] == 'user') echo 'selected'; ?>>User</option>
                                <option value="admin" <?php if($edit_data['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_user" class="btn">Save Changes</button>
                    <a href="manage_users.php" style="display: inline-block; margin-top: 10px; color: var(--sonic-silver);">Cancel Edit</a>
                </form>
            <?php else: ?>
                <h3 class="h3" style="margin-bottom: 15px;">Add New User</h3>
                <form method="POST" action="manage_users.php">
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 2;">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Enter username" required>
                        </div>
                        <div class="form-group" style="flex: 2;">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn">Add User</button>
                </form>
            <?php endif; ?>
        </div>

        <p class="section-subtitle :light">Registered Users</p>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($users_result)): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td>
                        <strong style="color: <?php echo $row['role'] == 'admin' ? 'var(--international-orange-engineering)' : 'var(--cadet-blue-creyola)'; ?>;">
                            <?php echo ucfirst($row['role']); ?>
                        </strong>
                    </td>
                    <td>
                        <a href="manage_users.php?edit=<?php echo $row['id']; ?>" class="action-link" style="color: var(--international-orange-engineering);">Edit</a>
                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                            <a href="manage_users.php?delete=<?php echo $row['id']; ?>" class="action-link" style="color: red;" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>