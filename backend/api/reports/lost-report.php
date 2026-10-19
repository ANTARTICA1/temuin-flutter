<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->item_id)) {
        $check_query = "SELECT id, name FROM items WHERE id = :item_id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':item_id', $data->item_id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() == 0) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Unauthorized or Item not found."]);
            exit;
        }

        $item = $check_stmt->fetch(PDO::FETCH_ASSOC);

        try {
            $db->beginTransaction();

            $reward_amount = isset($data->reward_amount) ? (int)$data->reward_amount : 0;
            $reward_description = isset($data->reward_description) ? htmlspecialchars(strip_tags($data->reward_description)) : null;
            $last_seen_location = isset($data->last_seen_location) ? htmlspecialchars(strip_tags($data->last_seen_location)) : null;
            $contact_phone = isset($data->contact_phone) ? htmlspecialchars(strip_tags($data->contact_phone)) : null;

            $update_query = "UPDATE items SET 
                                status = 'lost',
                                reward_amount = :reward_amount,
                                reward_description = :reward_description,
                                last_seen_location = :last_seen_location,
                                contact_phone = :contact_phone
                             WHERE id = :item_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':reward_amount', $reward_amount, PDO::PARAM_INT);
            $update_stmt->bindParam(':reward_description', $reward_description);
            $update_stmt->bindParam(':last_seen_location', $last_seen_location);
            $update_stmt->bindParam(':contact_phone', $contact_phone);
            $update_stmt->bindParam(':item_id', $data->item_id);
            $update_stmt->execute();

            $report_query = "INSERT INTO reports SET item_id = :item_id, reporter_id = :reporter_id, type = 'lost', notes = :notes";
            $report_stmt = $db->prepare($report_query);
            
            $notes = isset($data->notes) ? htmlspecialchars(strip_tags($data->notes)) : null;
            
            $report_stmt->bindParam(':item_id', $data->item_id);
            $report_stmt->bindParam(':reporter_id', $user_id);
            $report_stmt->bindParam(':notes', $notes);
            $report_stmt->execute();

            $db->commit();

            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Item marked as lost."]);

        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(503);
            echo json_encode(["status" => "error", "message" => "Failed to report item as lost: " . $e->getMessage()]);
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
