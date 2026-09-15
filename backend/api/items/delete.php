<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->id)) {
        try {
            $db->beginTransaction();

            $del_notif = $db->prepare("DELETE FROM notifications WHERE item_id = :item_id");
            $del_notif->bindParam(':item_id', $data->id);
            $del_notif->execute();

            $del_reports = $db->prepare("DELETE FROM reports WHERE item_id = :item_id");
            $del_reports->bindParam(':item_id', $data->id);
            $del_reports->execute();

            $query = "DELETE FROM items WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data->id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $db->commit();
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Item deleted successfully."]);
            } else {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Item not found or unauthorized."]);
            }
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(503);
            echo json_encode(["status" => "error", "message" => "Unable to delete item: " . $e->getMessage()]);
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
