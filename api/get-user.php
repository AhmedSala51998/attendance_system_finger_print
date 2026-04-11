<?php
include "../config.php";

$token = $_GET['token'] ?? '';

$stmt = $conn->prepare("SELECT id FROM employees WHERE api_token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    http_response_code(401);
    exit("Unauthorized");
}

echo json_encode([
    "employee_id" => $user['id']
]);