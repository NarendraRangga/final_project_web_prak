<?php
session_start();
require 'db.php';

// Protect the page: Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// --- 1. HANDLE ADD NEW SERVICE ---
if (isset($_POST['add_service'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];

    if (!empty($name) && !empty($price)) {
        $insert_query = "INSERT INTO service_types (name, price) VALUES ('$name', '$price')";
        if (mysqli_query($conn, $insert_query)) {
            $success_msg = "Service added successfully!";
        } else {
            $error_msg = "Failed to add service.";
        }
    }
}

// --- 2. HANDLE DELETE SERVICE ---
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Optional: Check if there are existing transactions with this service before deleting
    // To keep it simple, we will just delete it, but in a real app, you'd want to handle foreign key constraints.
    $delete_query = "DELETE FROM service_types WHERE id='$delete_id'";
    if (mysqli_query($conn, $delete_query)) {
        header("Location: manage_services.php?msg=deleted");
        exit();
    } else {
        $error_msg = "Cannot delete this service because it is currently tied to customer bookings.";
    }
}

// --- 3. HANDLE EDIT/UPDATE SERVICE ---
if (isset($_POST['update_service'])) {
    $id = $_POST['service_id'];
    $name = trim($_POST['name']);
    $price = $_POST['price'];

    $update_query = "UPDATE service_types SET name='$name', price='$price' WHERE id='$id'";
    if (mysqli_query($conn, $update_query)) {
        $success_msg = "Service updated successfully!";
    } else {
        $error_msg = "Failed to update service.";
    }
}

// Fetch all services to display in the table
$services_result = mysqli_query($conn, "SELECT * FROM service_types ORDER BY name ASC");

// Check if we are in "Edit" mode
$edit_mode = false;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($conn, "SELECT * FROM service_types WHERE id='$edit_id'");
    $edit_data = mysqli_fetch_assoc($edit_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Services - Autofix</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .admin-container { max-width: 900px; margin: 80px auto; padding: 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .form-card { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid var(--light-gray); }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: var(--eerie-black); }
        .form-group input { width: 100%; padding: 10px; border: 1px solid var(--light-gray); border-radius: 5px; }
        
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
            <h2 class="h2">Manage Services</h2>
            <div>
                <a href="admin_dashboard.php" class="btn-link" style="margin-right: 15px; display: inline-block;">Back to Dashboard</a>
                <a href="manage_users.php" class="btn-link" style="margin-right: 15px; display: inline-block;">Users</a>
                <a href="logout.php" class="btn-link" style="color: red; display: inline-block;">Logout</a>
            </div>
        </div>

        <?php 
        if(isset($success_msg)) echo "<p style='color: green; font-weight: bold; margin-bottom: 15px;'>$success_msg</p>"; 
        if(isset($error_msg)) echo "<p style='color: red; font-weight: bold; margin-bottom: 15px;'>$error_msg</p>"; 
        if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<p style='color: green; font-weight: bold; margin-bottom: 15px;'>Service deleted successfully!</p>"; 
        ?>

        <div class="form-card">
            <?php if($edit_mode): ?>
                <h3 class="h3" style="margin-bottom: 15px;">Edit Service</h3>
                <form method="POST" action="manage_services.php">
                    <input type="hidden" name="service_id" value="<?php echo $edit_data['id']; ?>">
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 2;">
                            <label>Service Name</label>
                            <input type="text" name="name" value="<?php echo $edit_data['name']; ?>" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Price ($)</label>
                            <input type="number" step="0.01" name="price" value="<?php echo $edit_data['price']; ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="update_service" class="btn">Save Changes</button>
                    <a href="manage_services.php" style="display: inline-block; margin-top: 10px; color: var(--sonic-silver);">Cancel Edit</a>
                </form>
            <?php else: ?>
                <h3 class="h3" style="margin-bottom: 15px;">Add New Service</h3>
                <form method="POST" action="manage_services.php">
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 2;">
                            <label>Service Name</label>
                            <input type="text" name="name" placeholder="e.g. Oil Change" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Price ($)</label>
                            <input type="number" step="0.01" name="price" placeholder="e.g. 35.00" required>
                        </div>
                    </div>
                    <button type="submit" name="add_service" class="btn">Add Service</button>
                </form>
            <?php endif; ?>
        </div>

        <p class="section-subtitle :light">Current Services</p>
        <table>
            <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($services_result)): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                    <td>
                        <a href="manage_services.php?edit=<?php echo $row['id']; ?>" class="action-link" style="color: var(--international-orange-engineering);">Edit</a>
                        <a href="manage_services.php?delete=<?php echo $row['id']; ?>" class="action-link" style="color: red;" onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($services_result) == 0): ?>
                    <tr><td colspan="4" style="text-align: center;">No services found. Add one above!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>