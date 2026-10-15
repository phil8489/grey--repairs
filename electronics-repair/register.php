
<?php
require_once "config/database.php";
require_once __DIR__ . "/config/security.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    if (
        $full_name == "" ||
        $email == "" ||
        $phone == "" ||
        $password == ""
    ) {
        $message = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";

    } else {
        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, phone, password, role)
             VALUES (?, ?, ?, ?, 'customer')"
        );

        $stmt->bind_param(
            "ssss",
            $full_name,
            $email,
            $phone,
            $hashed_password
        );

        try {
            $stmt->execute();
            $message = "Registration successful! You can now log in.";
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                $message = "This email address is already registered.";
            } else {
                error_log("Grey Repairs customer registration error: " . $e->getMessage());
                $message = "Registration failed. Please try again.";
            }
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
    <title>Register | Grey Repairs</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>Join Grey Repairs</h1>

    <h2>Create Customer Account</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST" action="register.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <label>Full Name:</label><br>
        <input type="text" name="full_name" required>
        <br><br>

        <label>Email Address:</label><br>
        <input type="email" name="email" required>
        <br><br>

        <label>Phone Number:</label><br>
        <input type="tel" name="phone" required>
        <br><br>

        <label>Password:</label><br>
        <input type="password" name="password" minlength="8" required>
        <br><br>

        <button type="submit">Register</button>

    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>

</body>
</html>
