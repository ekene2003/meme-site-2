<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "your_db");

$data = json_decode(file_get_contents("php://input"), true);
$address = strtolower(trim($data['address']));

// Generate nonce
$nonce = bin2hex(random_bytes(16));

// Store/update nonce
$stmt = $conn->prepare("INSERT INTO wallet_users (address, nonce)
VALUES (?, ?)
ON DUPLICATE KEY UPDATE nonce=?");

$stmt->bind_param("sss", $address, $nonce, $nonce);
$stmt->execute();

echo json_encode(["nonce" => $nonce]);