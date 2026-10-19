<?php

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $priority_only = isset($_GET['priority_only']) && ($_GET['priority_only'] == '1' || $_GET['priority_only'] === 'true');
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $where_clauses = ["r.type = 'lost'", "r.status = 'pending'"];
    $params = [];

    if ($priority_only) {
        $where_clauses[] = "i.is_premium = 1";
    }

    if (!empty($search)) {
        $where_clauses[] = "(i.name LIKE :search OR i.description LIKE :search OR i.last_seen_location LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $where_sql = implode(' AND ', $where_clauses);

    $query = "
        SELECT 
            i.id, i.name, i.description, i.image_url, 
            u.name as owner_name, u.photo_url as owner_photo, u.phone as owner_phone,
            COALESCE(i.reward_amount, 0) as reward_amount,
            i.reward_description,
            i.last_seen_location,
            i.contact_phone,
            r.notes as report_notes, r.created_at as reported_at,
            IFNULL(i.is_premium, 0) as is_premium
        FROM reports r
        JOIN items i ON r.item_id = i.id
        JOIN users u ON i.user_id = u.id
        WHERE $where_sql
        ORDER BY i.is_premium DESC, (COALESCE(i.reward_amount, 0) > 0) DESC, r.created_at DESC
    ";

    $stmt = $db->prepare($query);
    foreach ($params as $param_key => $param_val) {
        $stmt->bindValue($param_key, $param_val);
    }
    $stmt->execute();

    $feed = array();
    $priority_count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['is_premium'] = (bool)$row['is_premium'];
        $row['reward_amount'] = (int)$row['reward_amount'];
        if ($row['is_premium']) {
            $priority_count++;
        }
        array_push($feed, $row);
    }

    // Hitung total priority items jika mode normal
    if (!$priority_only) {
        $count_p = $db->query("SELECT COUNT(*) as total FROM reports r JOIN items i ON r.item_id = i.id WHERE r.type = 'lost' AND r.status = 'pending' AND i.is_premium = 1");
        $priority_count = (int)$count_p->fetch(PDO::FETCH_ASSOC)['total'];
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success", 
        "data" => $feed,
        "priority_count" => $priority_count
    ]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
