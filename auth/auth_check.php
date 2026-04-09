<?php
/**
 * auth_check.php — Reusable guard: include at top of any protected page
 *
 * Usage:
 *   require_once __DIR__ . '/auth/auth_check.php';
 *   // $walletAddress is now available
 */

session_start();

if (empty($_SESSION['logged_in']) || empty($_SESSION['wallet'])) {
    // For API endpoints — return JSON 401
    if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    // For HTML pages — redirect to login
    header('Location: /index.php');
    exit;
}

$walletAddress = $_SESSION['wallet'];
