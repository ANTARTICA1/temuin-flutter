<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->id)) {
        $check_query = "SELECT id FROM items WHERE id = :id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':id', $data->id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() == 0) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Unauthorized or Item not found."]);
            exit;
        }

        $query = "UPDATE items SET ";
        $updates = [];
        
        // Removed category_id
        if (isset($data->name)) {
            $updates[] = "name = :name";
        }
        if (isset($data->description)) {
            $updates[] = "description = :description";
        }
        if (isset($data->image_url)) {
            $updates[] = "image_url = :image_url";
        }
        if (isset($data->status)) {
            $updates[] = "status = :status";
        }

        if (count($updates) == 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "No data to update."]);
            exit;
        }

        $query .= implode(", ", $updates) . " WHERE id = :id";
        $stmt = $db->prepare($query);

        // Removed bindParam category_id
        if (isset($data->name)) {
            $name = htmlspecialchars(strip_tags($data->name));
            $stmt->bindParam(':name', $name);
        }
        if (isset($data->description)) {
            $desc = htmlspecialchars(strip_tags($data->description));
            $stmt->bindParam(':description', $desc);
        }
        if (isset($data->image_url)) {
            $image_url = htmlspecialchars(strip_tags($data->image_url));
            $stmt->bindParam(':image_url', $image_url);
        }
        if (isset($data->status)) {
            $stmt->bindParam(':status', $data->status);
        }
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Item updated successfully."]);
        } else {
            http_response_code(503);
            echo json_encode(["status" => "error", "message" => "Unable to update item."]);
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
