
<?php
session_start();

require_once "../config/database.php";
require_once __DIR__ . "/../config/security.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($email == "" || $password == "") {

        $message = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password, role
             FROM users
             WHERE email = ? AND role = 'technician'"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit();

            } else {
                $message = "Incorrect email or password.";
            }

        } else {
            $message = "Incorrect email or password.";
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
    <title>Technician Login | Grey Repairs</title>
<link rel="stylesheet" href="../style.css">
</head>

<body>

    <h1>Grey Repairs</h1>

    <h2>Technician Login</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST" action="login.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

        <label>Email Address:</label><br>
        <input type="email" name="email" required>
        <br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Login</button>

    </form>

    <p>
        Not registered?
        <a href="register.php">Register as a technician</a>
    </p>

</body>
</html>
