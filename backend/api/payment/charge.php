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

    if (!empty($data->item_id)) {
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

        $user_query = "SELECT name, email, phone FROM users WHERE id = :user_id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

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

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 201 || $http_code == 200) {
            $midtrans_response = json_decode($response);
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Berhasil membuat transaksi.",
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
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Item ID tidak boleh kosong."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
