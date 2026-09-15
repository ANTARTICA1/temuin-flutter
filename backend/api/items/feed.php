<?php

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    $query = "
        SELECT 
            i.id, i.name, i.description, i.image_url, 
            u.name as owner_name, u.photo_url as owner_photo,
            r.notes as report_notes, r.created_at as reported_at,
            IFNULL(i.is_premium, 0) as is_premium
        FROM reports r
        JOIN items i ON r.item_id = i.id
        JOIN users u ON i.user_id = u.id
        WHERE r.type = 'lost' AND r.status = 'pending'
        ORDER BY i.is_premium DESC, r.created_at DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $feed = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['is_premium'] = (bool)$row['is_premium'];
        array_push($feed, $row);
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $feed]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
