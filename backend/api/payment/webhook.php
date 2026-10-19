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

$order_id = $notification->order_id ?? '';
$status_code = $notification->status_code ?? '';
$gross_amount = $notification->gross_amount ?? '';
$signature_key = $notification->signature_key ?? '';

$my_signature = hash('sha512', $order_id . $status_code . $gross_amount . $serverKey);

if ($my_signature !== $signature_key) {
    http_response_code(403);
    exit("Invalid Signature");
}

$transaction_status = $notification->transaction_status ?? '';
$fraud_status = $notification->fraud_status ?? '';

$is_success = ($transaction_status == 'capture' || $transaction_status == 'settlement') && ($fraud_status != 'challenge');
$is_failure = in_array($transaction_status, ['cancel', 'deny', 'expire']);

// Cek status transaksi saat ini di database agar tidak dieksekusi ganda
$tx_check = $db->prepare("SELECT status FROM transactions WHERE order_id = :order_id");
$tx_check->bindParam(':order_id', $order_id);
$tx_check->execute();
$current_tx = $tx_check->fetch(PDO::FETCH_ASSOC);
$already_processed = ($current_tx && $current_tx['status'] === 'success');

$parts = explode('-', $order_id);

if ($is_success && !$already_processed) {
    // 1. Order BOOST
    if (count($parts) >= 2 && $parts[0] === 'BOOST') {
        $item_id = $parts[1];
        $update_query = "UPDATE items SET is_premium = 1 WHERE id = :item_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':item_id', $item_id);
        $update_stmt->execute();
    }
    // 2. Order SLOT
    elseif (count($parts) >= 3 && $parts[0] === 'SLOT') {
        $target_user_id = (int)$parts[1];
        $slots_to_add = (int)$parts[2];

        $update_query = "UPDATE users SET extra_slots = COALESCE(extra_slots, 0) + :slots WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':slots', $slots_to_add, PDO::PARAM_INT);
        $update_stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
        $update_stmt->execute();
    }

    // Update status transaksi jadi 'success'
    $upd_tx = $db->prepare("UPDATE transactions SET status = 'success' WHERE order_id = :order_id");
    $upd_tx->bindParam(':order_id', $order_id);
    $upd_tx->execute();

} elseif ($is_failure) {
    $upd_tx = $db->prepare("UPDATE transactions SET status = :status WHERE order_id = :order_id");
    $upd_tx->bindParam(':status', $transaction_status);
    $upd_tx->bindParam(':order_id', $order_id);
    $upd_tx->execute();
}

http_response_code(200);
echo "OK";
?>
