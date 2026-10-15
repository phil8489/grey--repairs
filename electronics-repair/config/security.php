<?php
// Shared CSRF helpers. Include this file after session_start() or let it start the session.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(): void
{
    $submitted = $_POST['csrf_token'] ?? null;
    $stored = $_SESSION['csrf_token'] ?? null;
    if (!is_string($submitted) || !is_string($stored) || $stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('Your session security token is missing or invalid. Go back, refresh the page, and try again.');
    }
}
