<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";

$customer_id = (int) $_SESSION["user_id"];
$message = "";
$message_type = "info";

// Create a security token for form submissions.
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Handle booking cancellation.
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
        $message = "Invalid request. Please refresh the page.";
        $message_type = "error";

    } elseif (!$booking_id || $booking_id < 1) {
        $message = "Invalid booking selected.";
        $message_type = "error";

    } else {
        $stmt = $conn->prepare(
            "UPDATE bookings
             SET status = 'cancelled'
             WHERE id = ?
               AND customer_id = ?
               AND status = 'pending'"
        );

        $stmt->bind_param(
            "ii",
            $booking_id,
            $customer_id
        );

        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Your booking has been cancelled.";
            $message_type = "success";
        } else {
            $message = "This booking cannot be cancelled. It may already have been processed.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

// Confirm that a newly created booking belongs to this customer.
if (
    isset($_GET["created"]) &&
    filter_var($_GET["created"], FILTER_VALIDATE_INT)
) {
    $created_id = (int) $_GET["created"];

    $stmt = $conn->prepare(
        "SELECT id
         FROM bookings
         WHERE id = ? AND customer_id = ?"
    );

    $stmt->bind_param(
        "ii",
        $created_id,
        $customer_id
    );

    $stmt->execute();
    $created_result = $stmt->get_result();

    if ($created_result->num_rows > 0) {
        $message = "Your booking was submitted successfully!";
        $message_type = "success";
    }

    $stmt->close();
}

// Retrieve all bookings belonging to the logged-in customer.
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
        u.full_name AS technician_name,
        t.specialization
     FROM bookings b
     INNER JOIN technicians t
        ON b.technician_id = t.id
     INNER JOIN users u
        ON t.user_id = u.id
     WHERE b.customer_id = ?
     ORDER BY b.created_at DESC"
);

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings</title>

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
            background: #dc3545;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #b02a37;
        }

        a {
            color: #0056b3;
        }
    </style>
<link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="container">

    <h1>My Bookings - Grey Repairs</h1>

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

        <p>You have not made any bookings yet.</p>

        <a href="find-technicians.php">Find a Technician</a>

    <?php else: ?>

        <?php while ($booking = $result->fetch_assoc()): ?>

            <div class="booking">

                <h2>
                    Booking #<?php echo (int) $booking["id"]; ?>
                </h2>

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
                    <strong>Problem:</strong><br>
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

                <p>
                    <strong>Booking submitted:</strong>
                    <?php echo htmlspecialchars($booking["created_at"]); ?>
                </p>

                <?php if ($booking["status"] === "pending"): ?>

                    <form method="POST" action="my-bookings.php"
                          onsubmit="return confirm('Are you sure you want to cancel this booking?');">

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

                        <button type="submit">Cancel Booking</button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

    <p>
        <a href="dashboard.php">Back to Dashboard</a>
    </p>

    <p>
        <a href="find-technicians.php">Find Another Technician</a>
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