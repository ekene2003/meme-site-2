<?php
session_start();
header('Content-Type: application/json');

$address = strtolower($_GET['address'] ?? '');

$nonce = bin2hex(random_bytes(16));
$expires = time() + 300;

$message = "Sign in to MyApp\n\nNonce: $nonce\nExpires: " . date('c', $expires);

// Store in session
$_SESSION['nonces'][$address] = [
    'value' => $nonce,
    'expires' => $expires
];

echo json_encode([
    'nonce' => $nonce,
    'message' => $message
]);