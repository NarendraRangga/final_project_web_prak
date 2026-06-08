<?php
session_start();
require 'db.php';

// Protect the page: Redirect if not logged in OR if the user is NOT an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Status Updates from the admin
if (isset($_POST['update_status'])) {
    $transaction_id = $_POST['transaction_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE transactions SET status='$new_status' WHERE id='$transaction_id'";
    mysqli_query($conn, $update_query);
}

// Fetch all transactions with related user, car, and service data using JOIN
$query = "
    SELECT t.id as trans_id, u.username, c.brand, c.license_plate, s.name as service_name, t.status, t.transaction_date 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN cars c ON t.car_id = c.id
    JOIN service_types s ON t.service_id = s.id
    ORDER BY t.transaction_date DESC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Autofix</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .admin-container { max-width: 1000px; margin: 80px auto; padding: 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: var(--shadow); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--light-gray); }
        th { background-color: var(--space-cadet); color: var(--white); font-family: var(--ff-chakra-petch); text-transform: uppercase; }
        tr:hover { background-color: #f9f9f9; }
        .status-form { display: flex; gap: 10px; }
        select { padding: 5px; border-radius: 4px; }
        .update-btn { background-color: var(--international-orange-engineering); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container admin-container">
        <div class="header-top">
            <h2 class="h2">Admin Control Panel</h2>
            <div>
                <a href="manage_users.php" class="btn-link" style="margin-right: 15px; display: inline-block;">Manage Users</a>
                <a href="manage_services.php" class="btn-link" style="margin-right: 15px; display: inline-block;">Manage Services</a>
                <a href="logout.php" class="btn-link" style="color: red; display: inline-block;">Logout</a>
            </div>
        </div>

        <p class="section-subtitle :light">Manage Transactions</p>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($row['transaction_date'])); ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo $row['brand'] . " <br><small>(" . $row['license_plate'] . ")</small>"; ?></td>
                    <td><?php echo $row['service_name']; ?></td>
                    <td>
                        <strong style="color: <?php echo $row['status'] == 'completed' ? 'green' : 'orange'; ?>;">
                            <?php echo ucfirst($row['status']); ?>
                        </strong>
                    </td>
                    <td>
                        <form method="POST" action="" class="status-form">
                            <input type="hidden" name="transaction_id" value="<?php echo $row['trans_id']; ?>">
                            <select name="status">
                                <option value="pending" <?php if($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                <option value="in_progress" <?php if($row['status'] == 'in_progress') echo 'selected'; ?>>In Progress</option>
                                <option value="completed" <?php if($row['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                            </select>
                            <button type="submit" name="update_status" class="update-btn">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($result) == 0): ?>
                    <tr><td colspan="6" style="text-align: center;">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>