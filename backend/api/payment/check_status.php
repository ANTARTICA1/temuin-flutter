<?php
include_once '../config/database.php';
include_once '../utils/auth.php';

if (file_exists(__DIR__ . '/../config/midtrans.php')) {
    include_once __DIR__ . '/../config/midtrans.php';
} else {
    $serverKey = 'YOUR_MIDTRANS_SERVER_KEY_HERE';
    $isProduction = false;
}

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $order_id = $data->order_id ?? '';

    if (empty($order_id)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "order_id is required"]);
        exit;
    }

    // Cek apakah order ini milik user ini
    $parts = explode('-', $order_id);
    $order_type = $parts[0] ?? '';

    // Cek di tabel transactions
    $tx_stmt = $db->prepare("SELECT * FROM transactions WHERE order_id = :order_id");
    $tx_stmt->bindParam(':order_id', $order_id);
    $tx_stmt->execute();
    $tx = $tx_stmt->fetch(PDO::FETCH_ASSOC);

    if ($tx && (int)$tx['user_id'] !== (int)$user_id) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Akses tidak diizinkan."]);
        exit;
    }

    // Jika sudah tercatat sukses di DB, langsung kembalikan sukses
    if ($tx && $tx['status'] === 'success') {
        // Ambil info slot terbaru
        $u_stmt = $db->prepare("SELECT COALESCE(extra_slots, 0) as extra_slots FROM users WHERE id = :id");
        $u_stmt->bindParam(':id', $user_id);
        $u_stmt->execute();
        $u_data = $u_stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "is_paid" => true,
            "transaction_status" => "settlement",
            "extra_slots" => (int)($u_data['extra_slots'] ?? 0),
            "message" => "Pembayaran berhasil dan telah diproses."
        ]);
        exit;
    }

    // Query status ke Midtrans API secara langsung
    $statusUrl = $isProduction ?
        "https://api.midtrans.com/v2/$order_id/status" :
        "https://api.sandbox.midtrans.com/v2/$order_id/status";

    $ch = curl_init($statusUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $midtrans_data = json_decode($response, true);
    $transaction_status = $midtrans_data['transaction_status'] ?? '';
    $fraud_status = $midtrans_data['fraud_status'] ?? '';

    $is_success = ($transaction_status === 'capture' || $transaction_status === 'settlement') && ($fraud_status !== 'challenge');

    if ($is_success) {
        // Eksekusi pemberian kuota atau boost jika belum pernah
        if ($order_type === 'SLOT' && count($parts) >= 3) {
            $slots_to_add = (int)$parts[2];
            $update_query = "UPDATE users SET extra_slots = COALESCE(extra_slots, 0) + :slots WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':slots', $slots_to_add, PDO::PARAM_INT);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } elseif ($order_type === 'BOOST' && count($parts) >= 2) {
            $item_id = $parts[1];
            $update_query = "UPDATE items SET is_premium = 1 WHERE id = :item_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':item_id', $item_id);
            $update_stmt->execute();
        }

        // Update tabel transaksi
        if ($tx) {
            $upd = $db->prepare("UPDATE transactions SET status = 'success' WHERE order_id = :order_id");
            $upd->bindParam(':order_id', $order_id);
            $upd->execute();
        }

        // Ambil info slot terbaru
        $u_stmt = $db->prepare("SELECT COALESCE(extra_slots, 0) as extra_slots FROM users WHERE id = :id");
        $u_stmt->bindParam(':id', $user_id);
        $u_stmt->execute();
        $u_data = $u_stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "is_paid" => true,
            "transaction_status" => $transaction_status,
            "extra_slots" => (int)($u_data['extra_slots'] ?? 0),
            "message" => "Pembayaran berhasil dikonfirmasi!"
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "status" => "pending",
            "is_paid" => false,
            "transaction_status" => $transaction_status ?: 'pending',
            "message" => "Pembayaran belum diselesaikan atau sedang diproses."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
