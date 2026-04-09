<?php
session_start();
header("Content-Type: application/json");

$conn = new mysqli("92.113.22.70", "u546156068_testUser", "e1K8@gs0Z~", "u546156068_mailing_list");

$data = json_decode(file_get_contents("php://input"), true);

$address = strtolower($data['address']);
$signature = $data['signature'];
$message = $data['message'];

// Call Node verifier
$payload = json_encode([
  "address" => $address,
  "signature" => $signature,
  "message" => $message
]);

$response = file_get_contents("http://localhost:3000/verify", false, stream_context_create([
  "http" => [
    "method" => "POST",
    "header" => "Content-Type: application/json",
    "content" => $payload
  ]
]));

$result = json_decode($response, true);

if (!$result['success']) {
  echo json_encode(["success" => false, "message" => "Invalid wallet"]);
  exit;
}

// Login or create user
$stmt = $conn->prepare("SELECT id FROM wallet_users WHERE address = ?");
$stmt->bind_param("s", $address);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  $stmt = $conn->prepare("INSERT INTO wallet_users (address) VALUES (?)");
  $stmt->bind_param("s", $address);
  $stmt->execute();
  $user_id = $stmt->insert_id;
} else {
  $user = $res->fetch_assoc();
  $user_id = $user['id'];
}

// Session
$_SESSION['user_id'] = $user_id;
$_SESSION['wallet'] = $address;

echo json_encode(["success" => true]);