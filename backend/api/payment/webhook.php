<?php
include_once '../config/database.php';

if (file_exists(__DIR__ . '/../config/midtrans.php')) {
    include_once __DIR__ . '/../config/midtrans.php';
} else {
    $serverKey = 'YOUR_MIDTRANS_SERVER_KEY_HERE';
}

$database = new Database();
$db = $database->getConnection();

$payload = file_get_contents('php://input');
$notification = json_decode($payload);

if (!$notification) {
    http_response_code(400);
    exit("Invalid JSON");
}

$order_id = $notification->order_id;
$status_code = $notification->status_code;
$gross_amount = $notification->gross_amount;
$signature_key = $notification->signature_key;

$my_signature = hash('sha512', $order_id . $status_code . $gross_amount . $serverKey);

if ($my_signature !== $signature_key) {
    http_response_code(403);
    exit("Invalid Signature");
}

$transaction_status = $notification->transaction_status;
$fraud_status = $notification->fraud_status ?? '';

$parts = explode('-', $order_id);
if (count($parts) >= 2 && $parts[0] === 'BOOST') {
    $item_id = $parts[1];

    if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
        if ($fraud_status == 'challenge') {
        } else {
            $update_query = "UPDATE items SET is_premium = 1 WHERE id = :item_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':item_id', $item_id);
            $update_stmt->execute();
        }
    } else if ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
    }
}

http_response_code(200);
echo "OK";
?>
