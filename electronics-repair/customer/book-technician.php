
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
require_once __DIR__ . "/../config/security.php";

$message = "";

$technician_id = filter_input(
    INPUT_GET,
    "technician_id",
    FILTER_VALIDATE_INT
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $technician_id = filter_input(
        INPUT_POST,
        "technician_id",
        FILTER_VALIDATE_INT
    );
}

$technician = null;

if ($technician_id && $technician_id > 0) {
    $stmt = $conn->prepare(
        "SELECT t.id, u.full_name, t.specialization, t.location
         FROM technicians t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.id = ?
           AND u.role = 'technician'
           AND t.availability = 'available'"
    );

    $stmt->bind_param("i", $technician_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $technician = $result->fetch_assoc();

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();

    $device_type = trim($_POST["device_type"] ?? "");
    $problem_description = trim(
        $_POST["problem_description"] ?? ""
    );
    $appointment_date = $_POST["appointment_date"] ?? "";
    $appointment_time = $_POST["appointment_time"] ?? "";
    $service_location = trim(
        $_POST["service_location"] ?? ""
    );

    if (!$technician) {
        $message = "This technician is unavailable. Please choose another.";

    } elseif (
        $device_type === "" ||
        $problem_description === "" ||
        $appointment_date === "" ||
        $appointment_time === "" ||
        $service_location === ""
    ) {
        $message = "Please fill in all fields.";

    } elseif (
        !DateTime::createFromFormat(
            '!Y-m-d',
            $appointment_date
        ) ||
        DateTime::createFromFormat(
            '!Y-m-d',
            $appointment_date
        )->format('Y-m-d') !== $appointment_date
    ) {
        $message = "Please enter a valid appointment date.";

    } elseif ($appointment_date < date("Y-m-d")) {
        $message = "Appointment date cannot be in the past.";

    } elseif (
        !preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            $appointment_time
        )
    ) {
        $message = "Please enter a valid appointment time.";

    } else {

        $customer_id = $_SESSION["user_id"];

        $stmt = $conn->prepare(
            "INSERT INTO bookings (
                customer_id,
                technician_id,
                device_type,
                problem_description,
                appointment_date,
                appointment_time,
                service_location
            ) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iisssss",
            $customer_id,
            $technician_id,
            $device_type,
            $problem_description,
            $appointment_date,
            $appointment_time,
            $service_location
        );

        if ($stmt->execute()) {
            $new_booking_id = $conn->insert_id;

            header(
                "Location: my-bookings.php?created=" .
                $new_booking_id
            );
            exit();

        } else {
            $message = "Booking failed. Please try again.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Technician</title>
<link rel="stylesheet" href="../style.css">
</head>

<body>

    <h1>Book a Repair - Grey Repairs</h1>

    <h2>Book a Technician</h2>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if (!$technician): ?>

        <p>
            No available technician was selected.
            Please return to the technician directory.
        </p>

        <a href="find-technicians.php">Find a Technician</a>

    <?php else: ?>

        <h3>
            Selected Technician:
            <?php echo htmlspecialchars($technician["full_name"]); ?>
        </h3>

        <p>
            Specialization:
            <?php echo htmlspecialchars($technician["specialization"]); ?>
        </p>

        <p>
            Location:
            <?php echo htmlspecialchars($technician["location"]); ?>
        </p>

        <form method="POST" action="book-technician.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <input
                type="hidden"
                name="technician_id"
                value="<?php echo (int)$technician["id"]; ?>"
            >

            <label>Electronic Device:</label><br>

            <select name="device_type" required>
                <option value="">Select a device</option>
                <option value="Mobile Phone">Mobile Phone</option>
                <option value="Laptop">Laptop</option>
                <option value="Desktop Computer">Desktop Computer</option>
                <option value="Television">Television</option>
                <option value="Refrigerator">Refrigerator</option>
                <option value="Audio Equipment">Audio Equipment</option>
                <option value="Other">Other</option>
            </select>

            <br><br>

            <label>Describe the Problem:</label><br>

            <textarea
                name="problem_description"
                rows="5"
                cols="40"
                maxlength="3000"
                required
            ></textarea>

            <br><br>

            <label>Appointment Date:</label><br>

            <input
                type="date"
                name="appointment_date"
                min="<?php echo date('Y-m-d'); ?>"
                required
            >

            <br><br>

            <label>Appointment Time:</label><br>

            <input type="time" name="appointment_time" required>

            <br><br>

            <label>Service Location:</label><br>

            <input
                type="text"
                name="service_location"
                maxlength="255"
                placeholder="Enter your address or service area"
                required
            >

            <br><br>

            <button type="submit">Submit Booking</button>

        </form>

    <?php endif; ?>

    <p>
        <a href="find-technicians.php">Back to Technicians</a>
    </p>

</body>
</html>
