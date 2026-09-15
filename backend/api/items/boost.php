<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->item_id)) {
        $check_query = "SELECT id FROM items WHERE id = :item_id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':item_id', $data->item_id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() == 0) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Item tidak ditemukan atau Anda tidak berhak."]);
            exit;
        }


        $payment_success = true;
        
        if ($payment_success) {
            $update_query = "UPDATE items SET is_premium = 1 WHERE id = :item_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':item_id', $data->item_id);
            
            if ($update_stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    "status" => "success", 
                    "message" => "Pembayaran Rp 15.000 Berhasil! Postingan Anda sekarang Premium."
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "message" => "Gagal mengupdate status premium."]);
            }
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Item ID tidak boleh kosong."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
