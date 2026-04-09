<?php
session_start();
header('Content-Type: application/json');

$address = $_SESSION['wallet'];
if (isset($_SESSION['logged_in'])) {
    require('config.php');
    $address = mysqli_real_escape_string($conn, $address);
    if ($_SESSION['wallet']) {
        $qry = "SELECT * FROM users WHERE wallet = ?";

        $run = mysqli_prepare($conn, $qry);
        mysqli_stmt_bind_param($run, "s", $address);
        mysqli_stmt_execute($run);

        $result = mysqli_stmt_get_result($run);
        $num_email = mysqli_num_rows($result);

        if ($num_email > 0) {
            $user_details = mysqli_fetch_assoc($result);
            echo json_encode($user_details);
        } else {
            echo json_encode(["error" => "User not found"]);
        }
    } else if ($_SESSION['email']) {
        $qry = "SELECT * FROM users WHERE wallet = ?";
        $email = mysqli_real_escape_string($conn,$email);

        $run = mysqli_prepare($conn, $qry);
        mysqli_stmt_bind_param($run, "s", $email);
        mysqli_stmt_execute($run);

        $result = mysqli_stmt_get_result($run);
        $num_email = mysqli_num_rows($result);

        if ($num_email > 0) {
            $user_details = mysqli_fetch_assoc($result);
            echo json_encode($user_details);
        } else {
            echo json_encode(["error" => "User not found"]);
        }
    } else {
        echo json_encode(["error" => "Not authenticated"]);
    }
}
