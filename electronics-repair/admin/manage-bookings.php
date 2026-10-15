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

// Handle cancellation requests.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $booking_id = filter_input(
        INPUT_POST,
        "booking_id",
        FILTER_VALIDATE_INT
    );

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
        // Only pending or accepted bookings may be cancelled.
        $stmt = $conn->prepare(
            "UPDATE bookings
             SET status = 'cancelled'
             WHERE id = ?
               AND status IN ('pending', 'accepted')"
        );

        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Booking cancelled successfully.";
            $message_type = "success";
        } else {
            $message = "This booking cannot be cancelled. It may already have been processed.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

// Retrieve all bookings and related customer/technician details.
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
        customer.full_name AS customer_name,
        customer.email AS customer_email,
        technician_user.full_name AS technician_name,
        t.specialization
     FROM bookings b
     INNER JOIN users customer
        ON b.customer_id = customer.id
     INNER JOIN technicians t
        ON b.technician_id = t.id
     INNER JOIN users technician_user
        ON t.user_id = technician_user.id
     ORDER BY b.created_at DESC"
);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Bookings | Grey Repairs</title>

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
            box-shadow: 0 2px 6px #ddd;
            overflow-x: auto;
        }

        .booking {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 18px;
        }

        .status {
            font-weight: bold;
            text-transform: capitalize;
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
            background: #dc3545;
            color: white;
            padding: 10px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #b02a37;
        }

        a {
            color: #1d4ed8;
        }
    </style>
<link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="container">

    <h1>Grey Repairs - Manage All Bookings</h1>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="panel">

        <h2>All Repair Bookings</h2>

        <p>
            Total bookings:
            <strong><?php echo $result->num_rows; ?></strong>
        </p>

        <?php if ($result->num_rows === 0): ?>

            <p>No repair bookings have been submitted yet.</p>

        <?php else: ?>

            <?php while ($booking = $result->fetch_assoc()): ?>

                <div class="booking">

                    <h3>
                        Booking #<?php echo (int) $booking["id"]; ?>
                    </h3>

                    <p>
                        <strong>Customer:</strong>
                        <?php echo htmlspecialchars($booking["customer_name"]); ?>
                    </p>

                    <p>
                        <strong>Customer email:</strong>
                        <?php echo htmlspecialchars($booking["customer_email"]); ?>
                    </p>

                    <p>
                        <strong>Technician:</strong>
                        <?php echo htmlspecialchars($booking["technician_name"]); ?>
                    </p>

                    <p>
                        <strong>Specialization:</strong>
                        <?php echo htmlspecialchars($booking["specialization"]); ?>
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
                            date(
                                "h:i A",
                                strtotime($booking["appointment_time"])
                            )
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

                    <p>
                        <strong>Date submitted:</strong>
                        <?php echo htmlspecialchars($booking["created_at"]); ?>
                    </p>

                    <?php
                    if (
                        $booking["status"] === "pending" ||
                        $booking["status"] === "accepted"
                    ):
                    ?>

                        <form
                            method="POST"
                            action="manage-bookings.php"
                            onsubmit="return confirm('Are you sure you want to cancel this booking?');"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                            >

                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?php echo (int) $booking["id"]; ?>"
                            >

                            <button type="submit">
                                Cancel Booking
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endwhile; ?>

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