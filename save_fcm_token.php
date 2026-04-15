<?php
session_start();
include "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$token = $data['token'];
$user_id = $_SESSION['employee_id'];

$stmt = $conn->prepare("
    UPDATE employees 
    SET fcm_token=? 
    WHERE id=?
");

$stmt->bind_param("si", $token, $user_id);
$stmt->execute();