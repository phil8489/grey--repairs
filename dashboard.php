
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

$user_id = $_SESSION["user_id"];
$full_name = $_SESSION["full_name"];

$stmt = $conn->prepare(
    "SELECT specialization, location, experience,
            description, availability
     FROM technicians
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$technician = $result->fetch_assoc();

$stmt->close();

if (!$technician) {
    exit("Technician profile not found. Contact the administrator.");
}

?>

<?php
// Add this after retrieving the technician profile,
// but before the HTML output.

$availability_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $new_availability = $_POST["availability"] ?? "";

    if (
        !is_string($token) ||
        !isset($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $availability_message = "Invalid request. Refresh the page.";

    } elseif (
        !in_array(
            $new_availability,
            ["available", "unavailable"],
            true
        )
    ) {
        $availability_message = "Invalid availability.";

    } else {
        $update = $conn->prepare(
            "UPDATE technicians
             SET availability = ?
             WHERE user_id = ?"
        );

        $update->bind_param(
            "si",
            $new_availability,
            $_SESSION["user_id"]
        );

        if ($update->execute()) {
            $availability_message = "Availability updated.";

            // Refresh the profile variable used by the dashboard.
            $profile_stmt = $conn->prepare(
                "SELECT id, specialization, location, experience,
                        description, availability
                 FROM technicians
                 WHERE user_id = ?"
            );

            $profile_stmt->bind_param(
                "i",
                $_SESSION["user_id"]
            );

            $profile_stmt->execute();
            $profile_result = $profile_stmt->get_result();
            $technician = $profile_result->fetch_assoc();

            $profile_stmt->close();
        } else {
            $availability_message = "Could not update availability.";
        }

        $update->close();
    }
}

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard | Grey Repairs</title>
<link rel="stylesheet" href="../style.css">
</head>

<body>

    <h1>Grey Repairs - Technician Dashboard</h1>

    <h2>
        Welcome, <?php echo htmlspecialchars($full_name); ?>!
    </h2>

    <h3>Your Professional Profile</h3>

    <p>
        <strong>Specialization:</strong>
        <?php echo htmlspecialchars($technician["specialization"]); ?>
    </p>

    <p>
        <strong>Location:</strong>
        <?php echo htmlspecialchars($technician["location"]); ?>
    </p>

    <p>
        <strong>Experience:</strong>
        <?php echo (int)$technician["experience"]; ?> years
    </p>

    <p>
        <strong>Description:</strong>
        <?php echo nl2br(htmlspecialchars($technician["description"] ?? "")); ?>
    </p>

    <p>
        <strong>Availability:</strong>
        <?php echo htmlspecialchars($technician["availability"]); ?>
    </p>

    <hr>

    <h3>Technician Menu</h3>

    <ul>
        <li><a href="manage-bookings.php">View Booking Requests</a></li>
        <li><a href="manage-bookings.php">Manage Appointments</a></li>
        <li><a href="#availability-form">Update Availability</a></li>
    </ul>

    
<h2>My Availability</h2>

<p>
    <?php echo htmlspecialchars($availability_message); ?>
</p>

<form id="availability-form" method="POST" action="dashboard.php">
    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
    >

    <label for="availability">Availability:</label>

    <select name="availability" id="availability" required>
        <option value="available"
            <?php echo $technician["availability"] === "available"
                ? "selected" : ""; ?>>
            Available
        </option>

        <option value="unavailable"
            <?php echo $technician["availability"] === "unavailable"
                ? "selected" : ""; ?>>
            Unavailable
        </option>
    </select>

    <button type="submit">Update Availability</button>
</form>


    <p>
        <a href="../logout.php">Logout</a>
    </p>

</body>
</html>
