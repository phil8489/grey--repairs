<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php'); exit();
}
$full_name = $_SESSION['full_name'] ?? 'Customer';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Customer Dashboard | Grey Repairs</title><link rel="stylesheet" href="../style.css"></head><body>
<div class="container"><div class="header"><h1>Grey Repairs</h1><h2>Welcome, <?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>!</h2><p>Your repair requests and technician search, all in one place.</p></div>
<div class="statistics"><div class="stat-card"><h3>Find help</h3><p>Browse technicians who are currently available.</p><a class="book-link" href="find-technicians.php">Find a technician →</a></div><div class="stat-card"><h3>Book a repair</h3><p>Choose a technician and tell them what needs fixing.</p><a class="book-link" href="book-technician.php">Start a booking →</a></div><div class="stat-card"><h3>My bookings</h3><p>Check the status of your repair requests.</p><a class="book-link" href="my-bookings.php">View bookings →</a></div></div>
<div class="panel"><h2>Quick links</h2><div class="navigation"><a href="find-technicians.php">Find Technicians</a><a href="book-technician.php">Book a Technician</a><a href="my-bookings.php">My Bookings</a><a href="../index.php">Visit Homepage</a></div></div><p><a href="../logout.php">Log out</a></p></div></body></html>