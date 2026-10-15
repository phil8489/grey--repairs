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

// Retrieve registered customers only.
$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone, created_at
     FROM users
     WHERE role = 'customer'
     ORDER BY created_at DESC"
);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Customers | Grey Repairs</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px #ddd;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #172554;
            color: white;
        }

        tr:hover {
            background: #f1f5f9;
        }

        a {
            color: #1d4ed8;
        }
    </style>
<link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="container">

    <h1>Grey Repairs - Manage Customers</h1>

    <div class="panel">

        <h2>Registered Customers</h2>

        <p>
            Total customers:
            <strong><?php echo $result->num_rows; ?></strong>
        </p>

        <?php if ($result->num_rows === 0): ?>

            <p>No customers have registered yet.</p>

        <?php else: ?>

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date Registered</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($customer = $result->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo (int) $customer["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($customer["full_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($customer["email"]); ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $customer["phone"] ?? "Not provided"
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($customer["created_at"]); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

    <p>
        <a href="dashboard.php">Back to Admin Dashboard</a>
    </p>

    <p>
        <a href="../logout.php">Logout</a>
    </p>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>