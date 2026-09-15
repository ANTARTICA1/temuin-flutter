<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken(); 
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->item_id) && isset($data->latitude) && isset($data->longitude)) {
        
        $item_query = "SELECT user_id, name FROM items WHERE id = :item_id LIMIT 1";
        $item_stmt = $db->prepare($item_query);
        $item_stmt->bindParam(':item_id', $data->item_id);
        $item_stmt->execute();

        if ($item_stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Item not found."]);
            exit;
        }

        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);
        $owner_id = $item['user_id'];

        if ($owner_id == $user_id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "You cannot report your own item as found by you. Use update status instead."]);
            exit;
        }

        try {
            $db->beginTransaction();

            $update_query = "UPDATE items SET status = 'found' WHERE id = :item_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':item_id', $data->item_id);
            $update_stmt->execute();

            $report_query = "INSERT INTO reports 
                             SET item_id = :item_id, reporter_id = :reporter_id, type = 'found', 
                             latitude = :latitude, longitude = :longitude, image_proof_url = :image_proof_url, notes = :notes";
            
            $report_stmt = $db->prepare($report_query);
            
            $notes = isset($data->notes) ? htmlspecialchars(strip_tags($data->notes)) : null;
            $image_proof = isset($data->image_proof_url) ? htmlspecialchars(strip_tags($data->image_proof_url)) : null;
            
            $report_stmt->bindParam(':item_id', $data->item_id);
            $report_stmt->bindParam(':reporter_id', $user_id); 
            $report_stmt->bindParam(':latitude', $data->latitude);
            $report_stmt->bindParam(':longitude', $data->longitude);
            $report_stmt->bindParam(':image_proof_url', $image_proof);
            $report_stmt->bindParam(':notes', $notes);
            $report_stmt->execute();

            $notif_title = "Barang Anda Ditemukan!";
            $notif_message = "Seseorang telah menemukan barang Anda: " . $item['name'] . ". Segera cek detail laporan dan lokasi penemuannya.";
            
            $notif_query = "INSERT INTO notifications SET user_id = :user_id, item_id = :item_id, title = :title, message = :message";
            $notif_stmt = $db->prepare($notif_query);
            $notif_stmt->bindParam(':user_id', $owner_id);
            $notif_stmt->bindParam(':item_id', $data->item_id);
            $notif_stmt->bindParam(':title', $notif_title);
            $notif_stmt->bindParam(':message', $notif_message);
            $notif_stmt->execute();

            $db->commit();

            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Item reported as found. The owner has been notified."]);

        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(503);
            echo json_encode(["status" => "error", "message" => "Failed to submit found report: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Incomplete data. item_id, latitude, and longitude are required."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
