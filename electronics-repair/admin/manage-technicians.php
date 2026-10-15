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

$message = "";
$message_type = "info";

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Update technician availability.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $technician_id = filter_input(
        INPUT_POST,
        "technician_id",
        FILTER_VALIDATE_INT
    );
    $availability = $_POST["availability"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $message = "Invalid request. Refresh the page and try again.";
        $message_type = "error";

    } elseif (!$technician_id || $technician_id < 1) {
        $message = "Invalid technician selected.";
        $message_type = "error";

    } elseif (
        !in_array($availability, ["available", "unavailable"], true)
    ) {
        $message = "Invalid availability selected.";
        $message_type = "error";

    } else {
        $stmt = $conn->prepare(
            "UPDATE technicians
             SET availability = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "si",
            $availability,
            $technician_id
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "Technician availability updated successfully.";
                $message_type = "success";
            } else {
                // Check whether the technician exists and already has
                // the selected availability.
                $check = $conn->prepare(
                    "SELECT id FROM technicians WHERE id = ?"
                );
                $check->bind_param("i", $technician_id);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;
                $check->close();

                if ($exists) {
                    $message = "The technician already has that availability.";
                } else {
                    $message = "Technician not found.";
                    $message_type = "error";
                }
            }
        } else {
            $message = "Could not update availability. Please try again.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

// Retrieve all technician profiles.
$stmt = $conn->prepare(
    "SELECT
        t.id,
        u.full_name,
        u.email,
        u.phone,
        t.specialization,
        t.location,
        t.experience,
        t.description,
        t.availability,
        t.created_at
     FROM technicians t
     INNER JOIN users u ON u.id = t.user_id
     WHERE u.role = 'technician'
     ORDER BY t.created_at DESC"
);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Technicians | Grey Repairs</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #222;
        }

        .container {
            max-width: 1150px;
            margin: auto;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 2px 6px #ddd;
        }

        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        th {
            background: #172554;
            color: white;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
        }

        button {
            background: #1d4ed8;
            color: white;
            padding: 9px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 8px;
        }

        select {
            padding: 8px;
            max-width: 100%;
        }

        a {
            color: #1d4ed8;
        }
    </style>
<link rel="stylesheet" href="../style.css">
</head>

<body>
<div class="container">

    <h1>Grey Repairs - Manage Technicians</h1>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Registered Technicians</h2>

        <p>
            Total technicians:
            <strong><?php echo $result->num_rows; ?></strong>
        </p>

        <?php if ($result->num_rows === 0): ?>
            <p>No technicians have registered yet.</p>
        <?php else: ?>

            <table>
                <thead>
                <tr>
                    <th>Name and Contact</th>
                    <th>Specialization</th>
                    <th>Location</th>
                    <th>Experience</th>
                    <th>Description</th>
                    <th>Availability</th>
                    <th>Update</th>
                </tr>
                </thead>

                <tbody>
                <?php while ($technician = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong>
                                <?php echo htmlspecialchars($technician["full_name"]); ?>
                            </strong>
                            <br>
                            <?php echo htmlspecialchars($technician["email"]); ?>
                            <br>
                            <?php
                            echo htmlspecialchars(
                                $technician["phone"] ?? "No phone provided"
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($technician["specialization"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($technician["location"]); ?>
                        </td>

                        <td>
                            <?php echo (int) $technician["experience"]; ?> years
                        </td>

                        <td>
                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $technician["description"] ?? ""
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($technician["availability"]); ?>
                        </td>

                        <td>
                            <form method="POST" action="manage-technicians.php">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="technician_id"
                                    value="<?php echo (int) $technician["id"]; ?>"
                                >

                                <select name="availability" required>
                                    <option
                                        value="available"
                                        <?php echo $technician["availability"] === "available" ? "selected" : ""; ?>
                                    >
                                        Available
                                    </option>

                                    <option
                                        value="unavailable"
                                        <?php echo $technician["availability"] === "unavailable" ? "selected" : ""; ?>
                                    >
                                        Unavailable
                                    </option>
                                </select>

                                <br>

                                <button type="submit">
                                    Save Availability
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

    <p><a href="dashboard.php">Back to Admin Dashboard</a></p>
    <p><a href="../logout.php">Logout</a></p>

</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>