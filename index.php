<?php
session_start();
$dashboard = null;
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') $dashboard = 'admin/dashboard.php';
    elseif ($_SESSION['role'] === 'technician') $dashboard = 'technician/dashboard.php';
    elseif ($_SESSION['role'] === 'customer') $dashboard = 'customer/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Find trusted electronics repair technicians and book a service with Grey Repairs.">
<title>Grey Repairs | Electronics Repair Made Easier</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="home-page">
<header class="site-header"><div class="nav-wrap">
<a class="brand" href="index.php"><span class="brand-mark">G</span><span>Grey <b>Repairs</b><small>Electronics care, made simple</small></span></a>
<nav class="main-nav" aria-label="Main navigation"><a href="#services">Services</a><a href="#how-it-works">How it works</a><a href="#why-us">Why Grey Repairs</a></nav>
<div class="nav-actions"><?php if ($dashboard): ?><a class="btn btn-outline" href="<?php echo htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8'); ?>">My dashboard</a><?php else: ?><a class="nav-login" href="login.php">Log in</a><a class="btn btn-primary" href="register.php">Get started</a><?php endif; ?></div>
</div></header>
<main>
<section class="hero"><div class="hero-copy"><span class="eyebrow"><span class="eyebrow-dot"></span> A smarter way to repair</span><h1>Get your devices back to <span>working perfectly.</span></h1><p class="hero-lede">Find electronics repair technicians, compare their specializations, and book a service that fits your schedule — all in one place.</p><div class="hero-actions"><a class="btn btn-primary btn-large" href="<?php echo $dashboard ? htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8') : 'register.php'; ?>">Find a technician <span aria-hidden="true">→</span></a><a class="btn btn-quiet btn-large" href="#how-it-works">See how it works</a></div><div class="trust-row"><div class="avatar-stack"><span>GR</span><span>✓</span><span>+</span></div><p><strong>Simple. Convenient. Reliable.</strong><br><small>Repair support for the devices you depend on.</small></p></div></div>
<div class="hero-art" aria-label="Illustration of electronics repair tools"><div class="art-glow"></div><div class="device-card laptop-card"><div class="device-top"><span></span><span></span><span></span></div><div class="screen-content"><div class="screen-symbol">⌁</div><div class="screen-line"></div><div class="screen-line short"></div><div class="screen-check">✓</div></div><div class="laptop-base"></div></div><div class="floating-card repair-badge"><span class="badge-icon">✓</span><div><strong>Repair made simple</strong><small>One step at a time</small></div></div><div class="floating-card tool-badge"><span class="tool-icon">⚙</span><div><strong>Skilled technicians</strong><small>Find your specialist</small></div></div><div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div></div>
</section>
<section class="benefit-strip" id="why-us"><div><span class="benefit-icon">⌕</span><p><strong>Find the right expert</strong><small>Search by service and location.</small></p></div><div><span class="benefit-icon">▦</span><p><strong>Book in a few steps</strong><small>Tell us what needs fixing.</small></p></div><div><span class="benefit-icon">✓</span><p><strong>Track your booking</strong><small>Follow its status from your account.</small></p></div></section>
<section class="section services-section" id="services"><div class="section-heading"><span class="eyebrow">WHAT WE HELP WITH</span><h2>Repair support for everyday tech.</h2><p>Find a technician whose skills match the device that needs attention.</p></div><div class="service-grid"><article class="service-card"><div class="service-icon phone-icon">▯</div><h3>Mobile phones</h3><p>Get help with common phone faults and device issues.</p></article><article class="service-card"><div class="service-icon laptop-icon">▱</div><h3>Laptops & computers</h3><p>Find support for personal and work computers.</p></article><article class="service-card"><div class="service-icon tv-icon">▣</div><h3>Televisions</h3><p>Connect with a specialist for TV repair needs.</p></article><article class="service-card"><div class="service-icon home-icon">⌂</div><h3>Home electronics</h3><p>Explore technicians for other household electronics.</p></article></div></section>
<section class="section how-section" id="how-it-works"><div class="section-heading"><span class="eyebrow">HOW IT WORKS</span><h2>From problem to booking, made simple.</h2><p>Three straightforward steps to get your repair request started.</p></div><div class="steps-grid"><article class="step-card"><span class="step-number">01</span><div class="step-icon">⌕</div><h3>Find a technician</h3><p>Search available technicians by specialization and location.</p></article><article class="step-card"><span class="step-number">02</span><div class="step-icon">▤</div><h3>Describe the problem</h3><p>Choose a date, provide the device details, and send a booking request.</p></article><article class="step-card"><span class="step-number">03</span><div class="step-icon">✓</div><h3>Track your request</h3><p>Sign in to view your booking and its latest status.</p></article></div></section>
<section class="cta-section"><div><span class="eyebrow eyebrow-light">READY WHEN YOU ARE</span><h2>Let's get your device working again.</h2><p>Create your account and start looking for the right technician.</p></div><a class="btn btn-light btn-large" href="<?php echo $dashboard ? htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8') : 'register.php'; ?>">Start your repair request <span aria-hidden="true">→</span></a></section>
</main>
<footer class="site-footer"><a class="brand brand-footer" href="index.php"><span class="brand-mark">G</span><span>Grey <b>Repairs</b><small>Electronics care, made simple</small></span></a><p>© <?php echo date('Y'); ?> Grey Repairs. Built to make repair booking easier.</p><div><a href="login.php">Log in</a><a href="technician/register.php">Join as a technician</a></div></footer>
</body></html>
