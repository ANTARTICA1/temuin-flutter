<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT id, name, email, phone, photo_url, role, COALESCE(extra_slots, 0) as extra_slots FROM users WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $cnt_stmt = $db->prepare("SELECT COUNT(*) as total FROM items WHERE user_id = :id");
        $cnt_stmt->bindParam(':id', $user_id);
        $cnt_stmt->execute();
        $used_slots = (int)($cnt_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $extra_slots = (int)$row['extra_slots'];
        $max_slots = 5 + $extra_slots;
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data" => [
                "id" => $row['id'],
                "name" => $row['name'],
                "email" => $row['email'],
                "phone" => $row['phone'],
                "photo_url" => $row['photo_url'],
                "role" => $row['role'],
                "slots" => [
                    "free_slots" => 5,
                    "extra_slots" => $extra_slots,
                    "max_slots" => $max_slots,
                    "used_slots" => $used_slots,
                    "remaining_slots" => max(0, $max_slots - $used_slots),
                    "price_per_slot" => 9000
                ]
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
