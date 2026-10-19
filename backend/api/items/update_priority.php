<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->item_id)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "item_id is required."]);
        exit;
    }

    $item_id = $data->item_id;

    // Pastikan item milik user
    $check_query = "SELECT id, is_premium FROM items WHERE id = :item_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':item_id', $item_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Item tidak ditemukan atau Anda tidak berwenang."]);
        exit;
    }

    $reward_amount = isset($data->reward_amount) ? (int)$data->reward_amount : 0;
    $reward_description = isset($data->reward_description) ? htmlspecialchars(strip_tags($data->reward_description)) : null;
    $last_seen_location = isset($data->last_seen_location) ? htmlspecialchars(strip_tags($data->last_seen_location)) : null;
    $contact_phone = isset($data->contact_phone) ? htmlspecialchars(strip_tags($data->contact_phone)) : null;

    $update_query = "UPDATE items SET 
                        reward_amount = :reward_amount,
                        reward_description = :reward_description,
                        last_seen_location = :last_seen_location,
                        contact_phone = :contact_phone
                     WHERE id = :item_id AND user_id = :user_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':reward_amount', $reward_amount, PDO::PARAM_INT);
    $update_stmt->bindParam(':reward_description', $reward_description);
    $update_stmt->bindParam(':last_seen_location', $last_seen_location);
    $update_stmt->bindParam(':contact_phone', $contact_phone);
    $update_stmt->bindParam(':item_id', $item_id);
    $update_stmt->bindParam(':user_id', $user_id);

    if ($update_stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Pengaturan imbalan dan info lokasi berhasil diperbarui.",
            "data" => [
                "item_id" => $item_id,
                "reward_amount" => $reward_amount,
                "reward_description" => $reward_description,
                "last_seen_location" => $last_seen_location,
                "contact_phone" => $contact_phone
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal memperbarui data."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
