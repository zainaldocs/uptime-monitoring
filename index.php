<?php
/**
 * Root Router / Entry Point
 */
require_once __DIR__ . '/includes/auth_guard.php';

startSessionIfNeeded();

$baseUrl = getBaseUrl();

if (isLoggedIn()) {
    header("Location: " . $baseUrl . "views/dashboard.php");
} else {
    header("Location: " . $baseUrl . "views/login.php");
}
exit();
