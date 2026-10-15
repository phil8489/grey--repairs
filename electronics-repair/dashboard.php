
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";

// Count registered customers.
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'customer'"
);
$total_customers = $result->fetch_assoc()["total"];

// Count registered technicians.
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'technician'"
);
$total_technicians = $result->fetch_assoc()["total"];

// Count all bookings.
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM bookings"
);
$total_bookings = $result->fetch_assoc()["total"];

// Count pending bookings.
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE status = 'pending'"
);
$pending_bookings = $result->fetch_assoc()["total"];

// Get recent bookings.
$stmt = $conn->prepare(
    "SELECT
        b.id,
        b.device_type,
        b.appointment_date,
        b.appointment_time,
        b.status,
        customer.full_name AS customer_name,
        technician_user.full_name AS technician_name
     FROM bookings b
     INNER JOIN users customer
        ON b.customer_id = customer.id
     INNER JOIN technicians t
        ON b.technician_id = t.id
     INNER JOIN users technician_user
        ON t.user_id = technician_user.id
     ORDER BY b.created_at DESC
     LIMIT 10"
);

$stmt->execute();
$bookings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #222;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .header {
            background: #172554;
            color: white;
            padding: 22px;
            border-radius: 8px;
        }

        .statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px #ddd;
        }

        .stat-card h3 {
            margin-top: 0;
            color: #555;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #1d4ed8;
        }

        .panel {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #e8edf5;
        }

        .navigation a {
            display: inline-block;
            background: white;
            padding: 12px 16px;
            margin: 5px 5px 5px 0;
            border-radius: 5px;
            text-decoration: none;
            color: #1d4ed8;
            border: 1px solid #ccd5e5;
        }

        .navigation a:hover {
            background: #e8edf5;
        }

        .logout {
            color: white;
        }
    </style>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Admin Dashboard</h1>

        <p>
            Welcome,
            <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
        </p>

        <a class="logout" href="../logout.php">Logout</a>
    </div>

    <div class="statistics">

        <div class="stat-card">
            <h3>Total Customers</h3>
            <div class="number">
                <?php echo (int) $total_customers; ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>Total Technicians</h3>
            <div class="number">
                <?php echo (int) $total_technicians; ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>Total Bookings</h3>
            <div class="number">
                <?php echo (int) $total_bookings; ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>Pending Bookings</h3>
            <div class="number">
                <?php echo (int) $pending_bookings; ?>
            </div>
        </div>

    </div>

    <div class="panel">
        <h2>Administration</h2>

        <div class="navigation">
            <a href="manage-customers.php">Manage Customers</a>
            <a href="manage-technicians.php">Manage Technicians</a>
            <a href="manage-bookings.php">Manage Bookings</a>
        </div>

        <p>
            These management pages will be created in the next steps.
        </p>
    </div>

    <div class="panel">
        <h2>Recent Bookings</h2>

        <?php if ($bookings->num_rows === 0): ?>

            <p>No bookings have been submitted yet.</p>

        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Technician</th>
                        <th>Device</th>
                        <th>Appointment</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($booking = $bookings->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo (int) $booking["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking["customer_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking["technician_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking["device_type"]); ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $booking["appointment_date"]
                                );
                                ?>
                                <br>
                                <?php
                                echo htmlspecialchars(
                                    date(
                                        "h:i A",
                                        strtotime($booking["appointment_time"])
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking["status"]); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>
    </div>

    <p>
        <a href="../index.php">Visit Main Website</a>
    </p>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>