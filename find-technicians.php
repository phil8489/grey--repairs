
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

$search = trim($_GET["search"] ?? "");
$location = trim($_GET["location"] ?? "");

$sql = "SELECT
            t.id AS technician_id,
            u.full_name,
            t.specialization,
            t.location,
            t.experience,
            t.description,
            t.availability
        FROM technicians t
        INNER JOIN users u ON t.user_id = u.id
        WHERE u.role = 'technician'
        AND t.availability = 'available'
        AND (
            u.full_name LIKE ?
            OR t.specialization LIKE ?
        )
        AND t.location LIKE ?
        ORDER BY t.experience DESC";

$stmt = $conn->prepare($sql);

$search_term = "%" . $search . "%";
$location_term = "%" . $location . "%";

$stmt->bind_param(
    "sss",
    $search_term,
    $search_term,
    $location_term
);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Find Technicians</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f4f6f9;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .technician-card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        input, button {
            padding: 10px;
            margin: 5px 0;
        }

        .book-link {
            display: inline-block;
            background: #1769aa;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
<link rel="stylesheet" href="../style.css">
</head>

<body>

<div class="container">

    <h1>Find a Technician - Grey Repairs</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!
    </p>

    <form method="GET" action="find-technicians.php">

        <label>Search by name or specialization:</label><br>
        <input
            type="text"
            name="search"
            placeholder="e.g. laptop, phone, David"
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <br>

        <label>Filter by location:</label><br>
        <input
            type="text"
            name="location"
            placeholder="e.g. Abuja"
            value="<?php echo htmlspecialchars($location); ?>"
        >

        <br>

        <button type="submit">Search Technicians</button>

        <a href="find-technicians.php">Clear Search</a>

    </form>

    <hr>

    <h2>Available Technicians</h2>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($technician = $result->fetch_assoc()): ?>

            <div class="technician-card">

                <h3>
                    <?php
                    echo htmlspecialchars($technician["full_name"]);
                    ?>
                </h3>

                <p>
                    <strong>Specialization:</strong>
                    <?php
                    echo htmlspecialchars(
                        $technician["specialization"]
                    );
                    ?>
                </p>

                <p>
                    <strong>Location:</strong>
                    <?php
                    echo htmlspecialchars($technician["location"]);
                    ?>
                </p>

                <p>
                    <strong>Experience:</strong>
                    <?php
                    echo (int)$technician["experience"];
                    ?> years
                </p>

                <p>
                    <strong>About:</strong>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $technician["description"] ?? ""
                        )
                    );
                    ?>
                </p>

                <p>
                    <strong>Availability:</strong>
                    <?php
                    echo htmlspecialchars($technician["availability"]);
                    ?>
                </p>

                <a
                    class="book-link"
                    href="book-technician.php?technician_id=<?php
                        echo (int)$technician["technician_id"];
                    ?>"
                >
                    Book This Technician
                </a>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No available technicians found. Try another search or location.</p>

    <?php endif; ?>

    <p>
        <a href="dashboard.php">Back to Dashboard</a>
    </p>

</div>

</body>
</html>

<?php
$stmt->close();
?>
