<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $item_id = htmlspecialchars(strip_tags($_GET['id']));
        
        $query = "SELECT i.*, u.name as owner_name,
                  (SELECT u2.phone FROM users u2 JOIN reports r ON r.reporter_id = u2.id WHERE r.item_id = i.id AND r.type = 'found' ORDER BY r.created_at DESC LIMIT 1) as finder_phone,
                  (SELECT u2.name FROM users u2 JOIN reports r ON r.reporter_id = u2.id WHERE r.item_id = i.id AND r.type = 'found' ORDER BY r.created_at DESC LIMIT 1) as finder_name,
                  (SELECT r.latitude FROM reports r WHERE r.item_id = i.id AND r.type = 'found' ORDER BY r.created_at DESC LIMIT 1) as latitude,
                  (SELECT r.longitude FROM reports r WHERE r.item_id = i.id AND r.type = 'found' ORDER BY r.created_at DESC LIMIT 1) as longitude,
                  (SELECT r.notes FROM reports r WHERE r.item_id = i.id AND r.type = 'found' ORDER BY r.created_at DESC LIMIT 1) as notes
                  FROM items i 
                  LEFT JOIN users u ON i.user_id = u.id 
                  WHERE i.id = :id LIMIT 1";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $row]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Item not found."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Item ID is required."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
