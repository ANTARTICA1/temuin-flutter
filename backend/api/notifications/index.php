<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT id, item_id, title, message, is_read, created_at 
              FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $notifications = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['is_read'] = (bool) $row['is_read'];
        array_push($notifications, $row);
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $notifications]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
