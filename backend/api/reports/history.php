<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT r.*, i.name as item_name, u.name as reporter_name 
              FROM reports r 
              JOIN items i ON r.item_id = i.id 
              JOIN users u ON r.reporter_id = u.id
              WHERE r.reporter_id = :user_id OR i.user_id = :owner_id 
              ORDER BY r.created_at DESC";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':owner_id', $user_id);
    $stmt->execute();

    $reports = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($reports, $row);
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $reports]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
