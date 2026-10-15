
<?php
require_once "../config/database.php";
require_once __DIR__ . "/../config/security.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    $specialization = trim($_POST["specialization"]);
    $location = trim($_POST["location"]);
    $experience = filter_var(
        $_POST["experience"],
        FILTER_VALIDATE_INT
    );
    $description = trim($_POST["description"]);

    if (
        $full_name == "" ||
        $email == "" ||
        $phone == "" ||
        $password == "" ||
        $specialization == "" ||
        $location == "" ||
        $_POST["experience"] === ""
    ) {
        $message = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";

    } elseif (
        $experience === false ||
        $experience < 0 ||
        $experience > 80
    ) {
        $message = "Enter a valid number of years of experience.";

    } else {

        try {
            $conn->begin_transaction();

            // Create the technician's user account.
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, email, phone, password, role)
                VALUES (?, ?, ?, ?, 'technician')"
            );

            $stmt->bind_param(
                "ssss",
                $full_name,
                $email,
                $phone,
                $hashed_password
            );

            $stmt->execute();

            // Get the ID of the new user.
            $user_id = $conn->insert_id;

            $stmt->close();

            // Create the technician's professional profile.
            $stmt = $conn->prepare(
                "INSERT INTO technicians
                (user_id, specialization, location,
                 experience, description)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "issis",
                $user_id,
                $specialization,
                $location,
                $experience,
                $description
            );

            $stmt->execute();
            $stmt->close();

            // Save both records together.
            $conn->commit();

            $message = "Technician registration successful!";

        } catch (mysqli_sql_exception $e) {

            $conn->rollback();

            if ($e->getCode() == 1062) {
                $message = "This email address is already registered.";
            } else {
                error_log($e->getMessage());
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | Grey Repairs</title>
<link rel="stylesheet" href="../style.css">
</head>

<body>

    <h1>Join Grey Repairs</h1>

    <h2>Technician Registration</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST" action="register.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <label>Full Name:</label><br>
        <input type="text" name="full_name" maxlength="100" required>
        <br><br>

        <label>Email Address:</label><br>
        <input type="email" name="email" maxlength="100" required>
        <br><br>

        <label>Phone Number:</label><br>
        <input type="tel" name="phone" maxlength="20" required>
        <br><br>

        <label>Password:</label><br>
        <input type="password" name="password" minlength="8" required>
        <br><br>

        <label>Specialization:</label><br>
        <select name="specialization" required>
            <option value="">Select specialization</option>
            <option value="Mobile Phones">Mobile Phones</option>
            <option value="Laptops and Computers">Laptops and Computers</option>
            <option value="Televisions">Televisions</option>
            <option value="Refrigerators">Refrigerators</option>
            <option value="Audio Equipment">Audio Equipment</option>
            <option value="Other Electronics">Other Electronics</option>
        </select>
        <br><br>

        <label>Location or Service Area:</label><br>
        <input type="text" name="location" maxlength="150" required>
        <br><br>

        <label>Years of Experience:</label><br>
        <input type="number" name="experience" min="0" max="80" required>
        <br><br>

        <label>Describe Your Services:</label><br>
        <textarea name="description" rows="4" cols="40"
            placeholder="Describe your repair skills and services"></textarea>
        <br><br>

        <button type="submit">Register as Technician</button>

    </form>

    <p>
        Already registered?
        <a href="login.php">Technician login</a>
    </p>

    <p>
        Are you a customer?
        <a href="../register.php">Register as a customer</a>
    </p>

</body>
</html>
