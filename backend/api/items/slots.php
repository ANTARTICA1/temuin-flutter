<?php
include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $slot_query = "SELECT COALESCE(extra_slots, 0) as extra_slots FROM users WHERE id = :user_id";
    $slot_stmt = $db->prepare($slot_query);
    $slot_stmt->bindParam(':user_id', $user_id);
    $slot_stmt->execute();
    $user_data = $slot_stmt->fetch(PDO::FETCH_ASSOC);
    
    $extra_slots = (int)($user_data['extra_slots'] ?? 0);
    $free_slots = 5;
    $max_slots = $free_slots + $extra_slots;

    $count_query = "SELECT COUNT(*) as total FROM items WHERE user_id = :user_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':user_id', $user_id);
    $count_stmt->execute();
    $used_slots = (int)($count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => [
            "free_slots" => $free_slots,
            "extra_slots" => $extra_slots,
            "max_slots" => $max_slots,
            "used_slots" => $used_slots,
            "remaining_slots" => max(0, $max_slots - $used_slots),
            "price_per_slot" => 9000,
            "can_add_item" => ($used_slots < $max_slots)
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
