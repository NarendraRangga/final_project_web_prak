<?php
session_start();
require 'db.php';

// Protect the page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- HANDLE BOOKING CANCELLATION ---
if (isset($_GET['cancel'])) {
    $cancel_id = $_GET['cancel'];
    
    // Security check: Only delete if it belongs to this user AND is still pending
    $delete_query = "DELETE FROM transactions WHERE id='$cancel_id' AND user_id='$user_id' AND status='pending'";
    if (mysqli_query($conn, $delete_query)) {
        $success_msg = "Booking canceled successfully.";
    } else {
        $error_msg = "Could not cancel booking.";
    }
}

// --- HANDLE NEW BOOKING SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_id = $_POST['service_id'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $license_plate = $_POST['license_plate'];

    $car_query = "INSERT INTO cars (user_id, brand, model, license_plate) VALUES ('$user_id', '$brand', '$model', '$license_plate')";
    mysqli_query($conn, $car_query);
    $car_id = mysqli_insert_id($conn);

    $trans_query = "INSERT INTO transactions (user_id, car_id, service_id, status) VALUES ('$user_id', '$car_id', '$service_id', 'pending')";
    
    if (mysqli_query($conn, $trans_query)) {
        $success_msg = "Service booked successfully!";
    } else {
        $error_msg = "Error booking service.";
    }
}

// Fetch services for the dropdown
$services_result = mysqli_query($conn, "SELECT * FROM service_types");

// Fetch the user's booking history
// Notice we added 't.id as trans_id' so we can reference the specific transaction for cancellation
$history_query = "
    SELECT t.id as trans_id, t.transaction_date, c.brand, c.license_plate, s.name as service_name, t.status 
    FROM transactions t
    JOIN cars c ON t.car_id = c.id
    JOIN service_types s ON t.service_id = s.id
    WHERE t.user_id = '$user_id'
    ORDER BY t.transaction_date DESC
";
$history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Autofix</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .dashboard-container { max-width: 800px; margin: 80px auto; padding: 30px; box-shadow: var(--shadow); border-radius: 8px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: var(--eerie-black); }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--light-gray); border-radius: 5px; font-family: var(--ff-mulish); }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--light-gray); }
        th { background-color: var(--space-cadet); color: var(--white); font-family: var(--ff-chakra-petch); text-transform: uppercase; }
        tr:hover { background-color: #f9f9f9; }
        .cancel-btn { color: red; font-size: 1.4rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <div class="header-top">
            <h2 class="h2">Welcome, <?php echo $_SESSION['username']; ?>!</h2>
            <a href="logout.php" class="btn-link" style="color: red;">Logout</a>
        </div>
        
        <p class="section-subtitle :light">Book a Service</p>

        <?php 
        if(isset($success_msg)) echo "<p style='color: green; font-weight: bold; margin-bottom: 15px;'>$success_msg</p>"; 
        if(isset($error_msg)) echo "<p style='color: red; font-weight: bold; margin-bottom: 15px;'>$error_msg</p>"; 
        ?>

        <form method="POST" action="">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Car Brand</label>
                    <input type="text" name="brand" placeholder="e.g. Toyota" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Car Model</label>
                    <input type="text" name="model" placeholder="e.g. Camry" required>
                </div>
            </div>

            <div class="form-group">
                <label>License Plate</label>
                <input type="text" name="license_plate" placeholder="e.g. B 1234 XYZ" required>
            </div>

            <div class="form-group">
                <label>Select Service</label>
                <select name="service_id" required>
                    <option value="">-- Choose a Service --</option>
                    <?php while($row = mysqli_fetch_assoc($services_result)): ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo $row['name'] . " ($" . $row['price'] . ")"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 10px;">Submit Booking</button>
        </form>

        <hr style="margin: 40px 0; border: 0; border-top: 1px solid var(--light-gray);">

        <p class="section-subtitle :light">My Booking History</p>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vehicle</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($history = mysqli_fetch_assoc($history_result)): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($history['transaction_date'])); ?></td>
                    <td><?php echo $history['brand'] . " (" . $history['license_plate'] . ")"; ?></td>
                    <td><?php echo $history['service_name']; ?></td>
                    <td>
                        <strong style="color: <?php echo $history['status'] == 'completed' ? 'green' : 'orange'; ?>;">
                            <?php echo ucfirst($history['status']); ?>
                        </strong>
                    </td>
                    <td>
                        <?php if($history['status'] == 'pending'): ?>
                            <a href="user_dashboard.php?cancel=<?php echo $history['trans_id']; ?>" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                        <?php else: ?>
                            <span style="color: var(--cadet-blue-creyola); font-size: 1.4rem;">Locked</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($history_result) == 0): ?>
                    <tr><td colspan="5" style="text-align: center;">You have no past bookings.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html><?php
session_start();
require 'db.php';

// Protect the page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_id = $_POST['service_id'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $license_plate = $_POST['license_plate'];

    $car_query = "INSERT INTO cars (user_id, brand, model, license_plate) VALUES ('$user_id', '$brand', '$model', '$license_plate')";
    mysqli_query($conn, $car_query);
    $car_id = mysqli_insert_id($conn);

    $trans_query = "INSERT INTO transactions (user_id, car_id, service_id, status) VALUES ('$user_id', '$car_id', '$service_id', 'pending')";
    
    if (mysqli_query($conn, $trans_query)) {
        $success_msg = "Service booked successfully!";
    } else {
        $error_msg = "Error booking service.";
    }
}

$services_result = mysqli_query($conn, "SELECT * FROM service_types");

$history_query = "
    SELECT t.transaction_date, c.brand, c.license_plate, s.name as service_name, t.status 
    FROM transactions t
    JOIN cars c ON t.car_id = c.id
    JOIN service_types s ON t.service_id = s.id
    WHERE t.user_id = '$user_id'
    ORDER BY t.transaction_date DESC
";
$history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Autofix</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .dashboard-container { max-width: 800px; margin: 80px auto; padding: 30px; box-shadow: var(--shadow); border-radius: 8px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: var(--eerie-black); }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--light-gray); border-radius: 5px; font-family: var(--ff-mulish); }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        /* Table Styles for History */
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--light-gray); }
        th { background-color: var(--space-cadet); color: var(--white); font-family: var(--ff-chakra-petch); text-transform: uppercase; }
        tr:hover { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <div class="header-top">
            <h2 class="h2">Welcome, <?php echo $_SESSION['username']; ?>!</h2>
            <a href="logout.php" class="btn-link" style="color: red;">Logout</a>
        </div>
        
        <p class="section-subtitle :light">Book a Service</p>

        <?php 
        if(isset($success_msg)) echo "<p style='color: green; font-weight: bold; margin-bottom: 15px;'>$success_msg</p>"; 
        if(isset($error_msg)) echo "<p style='color: red; font-weight: bold; margin-bottom: 15px;'>$error_msg</p>"; 
        ?>

        <form method="POST" action="">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Car Brand</label>
                    <input type="text" name="brand" placeholder="e.g. Toyota" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Car Model</label>
                    <input type="text" name="model" placeholder="e.g. Camry" required>
                </div>
            </div>

            <div class="form-group">
                <label>License Plate</label>
                <input type="text" name="license_plate" placeholder="e.g. B 1234 XYZ" required>
            </div>

            <div class="form-group">
                <label>Select Service</label>
                <select name="service_id" required>
                    <option value="">-- Choose a Service --</option>
                    <?php while($row = mysqli_fetch_assoc($services_result)): ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo $row['name'] . " ($" . $row['price'] . ")"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 10px;">Submit Booking</button>
        </form>

        <hr style="margin: 40px 0; border: 0; border-top: 1px solid var(--light-gray);">

        <p class="section-subtitle :light">My Booking History</p>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vehicle</th>
                    <th>Service</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($history = mysqli_fetch_assoc($history_result)): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($history['transaction_date'])); ?></td>
                    <td><?php echo $history['brand'] . " (" . $history['license_plate'] . ")"; ?></td>
                    <td><?php echo $history['service_name']; ?></td>
                    <td>
                        <strong style="color: <?php echo $history['status'] == 'completed' ? 'green' : 'orange'; ?>;">
                            <?php echo ucfirst($history['status']); ?>
                        </strong>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($history_result) == 0): ?>
                    <tr><td colspan="4" style="text-align: center;">You have no past bookings.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>