<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$address   = strtolower(trim($data['address'] ?? ''));
$signature = $data['signature'] ?? '';
$message   = $data['message'] ?? '';

if (!$address || !$signature || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

// Send to Node verifier
$payload = json_encode([
    'address' => $address,
    'signature' => $signature,
    'message' => $message
]);

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $payload
    ]
];

$response = file_get_contents("http://localhost:3000/verify", false, stream_context_create($options));

if (!$response) {
    echo json_encode(['error' => 'Verifier service unavailable']);
    exit;
}

$result = json_decode($response, true);

if (!$result['success']) {
    echo json_encode(['error' => $result['error'] ?? 'Invalid signature']);
    exit;
}

// ✅ SUCCESS → create session
session_regenerate_id(true);
$_SESSION['wallet'] = $address;
$_SESSION['logged_in'] = true;
require('config.php');
mysqli_real_escape_string($conn,$address);
         
$qry = "SELECT * FROM users WHERE wallet = ?";

$run = mysqli_prepare($conn,$qry);
mysqli_stmt_bind_param($run,"s",$address);
mysqli_stmt_execute($run);

$result = mysqli_stmt_get_result($run);
$num_email = mysqli_num_rows($result) ;

if($num_email < 1){
    $rand = rand(400,4000);
    $username = "Trader $rand";
    $qry = "INSERT INTO users(username,wallet) VALUES(?,?)";
    $run = mysqli_prepare($conn,$qry);
    mysqli_stmt_bind_param($run,"ss",$username,$address);
    mysqli_stmt_execute($run);
}

echo json_encode(['success' => true]);