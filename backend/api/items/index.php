<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $query = "SELECT i.* 
              FROM items i 
              WHERE i.user_id = :user_id 
              ORDER BY i.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $items = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($items, $row);
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $items]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->name)) {
        $qr_id = "ITEM_" . time() . rand(100, 999);
        
        $query = "INSERT INTO items 
                  SET id=:id, user_id=:user_id, name=:name, description=:description, image_url=:image_url";
        
        $stmt = $db->prepare($query);

        $name = htmlspecialchars(strip_tags($data->name));
        $desc = isset($data->description) ? htmlspecialchars(strip_tags($data->description)) : null;
        $image_url = isset($data->image_url) ? htmlspecialchars(strip_tags($data->image_url)) : null;

        $stmt->bindParam(":id", $qr_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":description", $desc);
        $stmt->bindParam(":image_url", $image_url);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "status" => "success", 
                "message" => "Item created successfully.",
                "item_id" => $qr_id
            ]);
        } else {
            http_response_code(503);
            echo json_encode(["status" => "error", "message" => "Unable to create item."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Incomplete data. name is required."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
