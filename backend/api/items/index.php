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

    // Slot information
    $slot_query = "SELECT COALESCE(extra_slots, 0) as extra_slots FROM users WHERE id = :user_id";
    $slot_stmt = $db->prepare($slot_query);
    $slot_stmt->bindParam(':user_id', $user_id);
    $slot_stmt->execute();
    $extra_slots = (int)($slot_stmt->fetch(PDO::FETCH_ASSOC)['extra_slots'] ?? 0);
    $free_slots = 5;
    $max_slots = $free_slots + $extra_slots;
    $used_slots = count($items);

    http_response_code(200);
    echo json_encode([
        "status" => "success", 
        "data" => $items,
        "slots" => [
            "free_slots" => $free_slots,
            "extra_slots" => $extra_slots,
            "max_slots" => $max_slots,
            "used_slots" => $used_slots,
            "remaining_slots" => max(0, $max_slots - $used_slots),
            "price_per_slot" => 9000
        ]
    ]);

} elseif ($method === 'POST') {
    // Check slot limit
    $slot_query = "SELECT COALESCE(extra_slots, 0) as extra_slots FROM users WHERE id = :user_id";
    $slot_stmt = $db->prepare($slot_query);
    $slot_stmt->bindParam(':user_id', $user_id);
    $slot_stmt->execute();
    $extra_slots = (int)($slot_stmt->fetch(PDO::FETCH_ASSOC)['extra_slots'] ?? 0);
    $free_slots = 5;
    $max_slots = $free_slots + $extra_slots;

    $count_query = "SELECT COUNT(*) as total FROM items WHERE user_id = :user_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':user_id', $user_id);
    $count_stmt->execute();
    $used_slots = (int)($count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    if ($used_slots >= $max_slots) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "code" => "SLOT_LIMIT_REACHED",
            "message" => "Batas slot barang telah tercapai ($used_slots/$max_slots slot). Silakan beli slot tambahan seharga Rp 9.000 per slot untuk mendaftarkan barang lagi.",
            "slots" => [
                "free_slots" => $free_slots,
                "extra_slots" => $extra_slots,
                "max_slots" => $max_slots,
                "used_slots" => $used_slots,
                "price_per_slot" => 9000
            ]
        ]);
        exit;
    }

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
