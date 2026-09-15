<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$user_id = Auth::validateToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    $database = new Database();
    $db = $database->getConnection();
    
    $name = isset($data->name) ? htmlspecialchars(strip_tags($data->name)) : null;
    $email = isset($data->email) ? htmlspecialchars(strip_tags($data->email)) : null;
    $phone = isset($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : null;
    $photo_base64 = isset($data->photo_base64) ? $data->photo_base64 : null;
    
    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Name and Email are required."]);
        exit;
    }

    $check_query = "SELECT id FROM users WHERE email = :email AND id != :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->bindParam(':id', $user_id);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email already used by another account."]);
        exit;
    }
    
    $photo_url = null;
    
    if ($photo_base64) {
        $image_parts = explode(";base64,", $photo_base64);
        if (count($image_parts) == 2) {
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = isset($image_type_aux[1]) ? $image_type_aux[1] : 'jpeg';
            $image_base64 = base64_decode($image_parts[1]);
            
            $file_name = 'avatar_' . $user_id . '_' . time() . '.' . $image_type;
            $file_path = '../uploads/avatars/' . $file_name;
            
            if (file_put_contents($file_path, $image_base64)) {
                $photo_url = 'uploads/avatars/' . $file_name;
            }
        }
    }
    
    if ($photo_url) {
        $query = "UPDATE users SET name = :name, email = :email, phone = :phone, photo_url = :photo_url WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':photo_url', $photo_url);
    } else {
        $query = "UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id";
        $stmt = $db->prepare($query);
    }
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':id', $user_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        $response = ["status" => "success", "message" => "Profile updated successfully."];
        if ($photo_url) {
            $response['photo_url'] = $photo_url;
        }
        echo json_encode($response);
    } else {
        http_response_code(503);
        echo json_encode(["status" => "error", "message" => "Unable to update profile."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
