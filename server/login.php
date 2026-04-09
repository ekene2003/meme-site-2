<?php
session_start();
header("Content-Type: application/json");

 
$conn = new mysqli("92.113.22.70", "u546156068_testUser", "e1K8@gs0Z~", "u546156068_mailing_list");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB error"]);
    exit;
}
 
$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
 
if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

 
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}

$user = $result->fetch_assoc();
 
if (!password_verify($password, $user['password'])) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}

 
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['username'];

echo json_encode(["success" => true]);

$stmt->close();
$conn->close();