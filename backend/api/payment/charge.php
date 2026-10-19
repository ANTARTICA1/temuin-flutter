<?php
include_once '../config/database.php';
include_once '../utils/auth.php';

if (file_exists(__DIR__ . '/../config/midtrans.php')) {
    include_once __DIR__ . '/../config/midtrans.php';
} else {
    $serverKey = 'YOUR_MIDTRANS_SERVER_KEY_HERE';
    $isProduction = false;
}

$apiUrl = $isProduction ? 
    'https://app.midtrans.com/snap/v1/transactions' : 
    'https://app.sandbox.midtrans.com/snap/v1/transactions';

$user_id = Auth::validateToken();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    $user_query = "SELECT name, email, phone FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Pengguna tidak ditemukan."]);
        exit;
    }

    $payment_type = $data->type ?? '';
    
    // 1. Pembelian Slot Tambahan (Rp 9.000 / slot)
    if ($payment_type === 'slot' || isset($data->slots) || isset($data->slot_count)) {
        $slots = isset($data->slots) ? (int)$data->slots : (isset($data->slot_count) ? (int)$data->slot_count : 1);
        if ($slots < 1) {
            $slots = 1;
        }

        $price_per_slot = 9000;
        $gross_amount = $slots * $price_per_slot;
        $order_id = "SLOT-" . $user_id . "-" . $slots . "-" . time();

        $transaction_data = array(
            'transaction_details' => array(
                'order_id' => $order_id,
                'gross_amount' => $gross_amount,
            ),
            'item_details' => array(
                array(
                    'id' => 'ITEM_SLOT',
                    'price' => $price_per_slot,
                    'quantity' => $slots,
                    'name' => 'Slot Tambahan Barang (' . $slots . ' Slot)'
                )
            ),
            'customer_details' => array(
                'first_name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? ''
            )
        );

        // Simpan ke tabel transactions
        $tx_query = "INSERT INTO transactions (order_id, user_id, type, amount, status, metadata) 
                     VALUES (:order_id, :user_id, 'slot', :amount, 'pending', :metadata)";
        $tx_stmt = $db->prepare($tx_query);
        $metadata = json_encode(['slots' => $slots, 'price_per_slot' => $price_per_slot]);
        $tx_stmt->bindParam(':order_id', $order_id);
        $tx_stmt->bindParam(':user_id', $user_id);
        $tx_stmt->bindParam(':amount', $gross_amount);
        $tx_stmt->bindParam(':metadata', $metadata);
        $tx_stmt->execute();

    } 
    // 2. Boost Postingan (Rp 15.000)
    elseif (!empty($data->item_id)) {
        $check_query = "SELECT id, name FROM items WHERE id = :item_id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':item_id', $data->item_id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() == 0) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Item tidak ditemukan atau Anda tidak berhak."]);
            exit;
        }

        $item = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $order_id = "BOOST-" . $item['id'] . "-" . time();
        $gross_amount = 15000;

        $transaction_data = array(
            'transaction_details' => array(
                'order_id' => $order_id,
                'gross_amount' => $gross_amount,
            ),
            'item_details' => array(
                array(
                    'id' => 'PREMIUM_BOOST',
                    'price' => $gross_amount,
                    'quantity' => 1,
                    'name' => 'Boost Postingan: ' . substr($item['name'], 0, 30)
                )
            ),
            'customer_details' => array(
                'first_name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? ''
            )
        );

        // Simpan ke tabel transactions
        $tx_query = "INSERT INTO transactions (order_id, user_id, type, amount, status, metadata) 
                     VALUES (:order_id, :user_id, 'boost', :amount, 'pending', :metadata)";
        $tx_stmt = $db->prepare($tx_query);
        $metadata = json_encode(['item_id' => $item['id']]);
        $tx_stmt->bindParam(':order_id', $order_id);
        $tx_stmt->bindParam(':user_id', $user_id);
        $tx_stmt->bindParam(':amount', $gross_amount);
        $tx_stmt->bindParam(':metadata', $metadata);
        $tx_stmt->execute();

    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Parameter tidak valid. Sertakan type 'slot' dan 'slots', atau 'item_id'."]);
        exit;
    }

    $payload = json_encode($transaction_data);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
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

    if ($http_code == 201 || $http_code == 200) {
        $midtrans_response = json_decode($response);
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Berhasil membuat transaksi.",
            "order_id" => $order_id,
            "redirect_url" => $midtrans_response->redirect_url,
            "token" => $midtrans_response->token
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Gagal terhubung ke Midtrans.",
            "midtrans_error" => json_decode($response)
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
