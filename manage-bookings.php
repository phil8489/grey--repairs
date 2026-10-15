<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "technician"
) {
    header("Location: login.php");
    exit();
}

require_once "../config/database.php";

$user_id = (int) $_SESSION["user_id"];
$message = "";
$message_type = "info";

// Create a security token.
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Find the technician's profile.
$stmt = $conn->prepare(
    "SELECT id
     FROM technicians
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$profile_result = $stmt->get_result();
$profile = $profile_result->fetch_assoc();
$stmt->close();

if (!$profile) {
    die("Technician profile not found.");
}

$technician_id = (int) $profile["id"];

// Handle accept, reject, and complete actions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";
    $booking_id = filter_input(
        INPUT_POST,
        "booking_id",
        FILTER_VALIDATE_INT
    );

    $action = $_POST["action"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $message = "Invalid request. Refresh the page and try again.";
        $message_type = "error";

    } elseif (!$booking_id || $booking_id < 1) {
        $message = "Invalid booking selected.";
        $message_type = "error";

    } else {

        // Only allow these three actions.
        $new_status = "";

        if ($action === "accept") {
            $new_status = "accepted";
        } elseif ($action === "reject") {
            $new_status = "rejected";
        } elseif ($action === "complete") {
            $new_status = "completed";
        }

        if ($new_status === "") {
            $message = "Invalid action.";
            $message_type = "error";

        } else {

            // Pending bookings can be accepted or rejected.
            // Accepted bookings can be marked completed.
            if (
                $new_status === "accepted" ||
                $new_status === "rejected"
            ) {
                $old_status = "pending";
            } else {
                $old_status = "accepted";
            }

            $stmt = $conn->prepare(
                "UPDATE bookings
                 SET status = ?
                 WHERE id = ?
                   AND technician_id = ?
                   AND status = ?"
            );

            $stmt->bind_param(
                "siis",
                $new_status,
                $booking_id,
                $technician_id,
                $old_status
            );

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $message = "Booking status updated to " .
                    $new_status . ".";
                $message_type = "success";
            } else {
                $message = "The booking could not be updated. It may already have been processed.";
                $message_type = "error";
            }

            $stmt->close();
        }
    }
}

// Retrieve bookings assigned to this technician.
$stmt = $conn->prepare(
    "SELECT
        b.id,
        b.device_type,
        b.problem_description,
        b.appointment_date,
        b.appointment_time,
        b.service_location,
        b.status,
        b.created_at,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
     FROM bookings b
     INNER JOIN users u
        ON b.customer_id = u.id
     WHERE b.technician_id = ?
     ORDER BY
        CASE b.status
            WHEN 'pending' THEN 1
            WHEN 'accepted' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'rejected' THEN 4
            WHEN 'cancelled' THEN 5
            ELSE 6
        END,
        b.appointment_date ASC,
        b.appointment_time ASC"
);

$stmt->bind_param("i", $technician_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Bookings</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .booking {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px #ddd;
        }

        .message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
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

        .status {
            font-weight: bold;
            text-transform: capitalize;
        }

        button {
            padding: 10px 14px;
            margin: 5px 5px 5px 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }

        .accept {
            background: #198754;
        }

        .reject {
            background: #dc3545;
        }

        .complete {
            background: #0d6efd;
        }

        a {
            color: #0056b3;
        }
    </style>
<link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="container">

    <h1>Grey Repairs - Manage Bookings</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!
    </p>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows === 0): ?>

        <p>You have no repair bookings yet.</p>

    <?php else: ?>

        <?php while ($booking = $result->fetch_assoc()): ?>

            <div class="booking">

                <h2>
                    Booking #<?php echo (int) $booking["id"]; ?>
                </h2>

                <p>
                    <strong>Customer:</strong>
                    <?php echo htmlspecialchars($booking["customer_name"]); ?>
                </p>

                <p>
                    <strong>Customer email:</strong>
                    <?php echo htmlspecialchars($booking["customer_email"] ?? "Not provided"); ?>
                </p>

                <p>
                    <strong>Customer phone:</strong>
                    <?php echo htmlspecialchars($booking["customer_phone"] ?? "Not provided"); ?>
                </p>

                <p>
                    <strong>Device:</strong>
                    <?php echo htmlspecialchars($booking["device_type"]); ?>
                </p>

                <p>
                    <strong>Problem description:</strong><br>
                    <?php
                    echo nl2br(
                        htmlspecialchars($booking["problem_description"])
                    );
                    ?>
                </p>

                <p>
                    <strong>Appointment date:</strong>
                    <?php echo htmlspecialchars($booking["appointment_date"]); ?>
                </p>

                <p>
                    <strong>Appointment time:</strong>
                    <?php
                    echo htmlspecialchars(
                        date("h:i A", strtotime($booking["appointment_time"]))
                    );
                    ?>
                </p>

                <p>
                    <strong>Service location:</strong>
                    <?php echo htmlspecialchars($booking["service_location"]); ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <span class="status">
                        <?php echo htmlspecialchars($booking["status"]); ?>
                    </span>
                </p>

                <?php if ($booking["status"] === "pending"): ?>

                    <form method="POST" action="manage-bookings.php">

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php echo (int) $booking["id"]; ?>"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                        >

                        <button
                            class="accept"
                            type="submit"
                            name="action"
                            value="accept"
                        >
                            Accept Booking
                        </button>

                        <button
                            class="reject"
                            type="submit"
                            name="action"
                            value="reject"
                        >
                            Reject Booking
                        </button>

                    </form>

                <?php elseif ($booking["status"] === "accepted"): ?>

                    <form method="POST" action="manage-bookings.php">

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php echo (int) $booking["id"]; ?>"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                        >

                        <button
                            class="complete"
                            type="submit"
                            name="action"
                            value="complete"
                        >
                            Mark as Completed
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

    <p>
        <a href="dashboard.php">Back to Technician Dashboard</a>
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