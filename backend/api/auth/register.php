<?php

include_once '../config/database.php';
include_once '../utils/auth.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    
    $check_query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $data->email);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email already registered."]);
        exit;
    }

    $query = "INSERT INTO users SET name=:name, email=:email, password=:password, phone=:phone";
    $stmt = $db->prepare($query);

    $name = htmlspecialchars(strip_tags($data->name));
    $email = htmlspecialchars(strip_tags($data->email));
    $phone = !empty($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : null;
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password_hash);
    $stmt->bindParam(":phone", $phone);

    if($stmt->execute()) {
        $user_id = $db->lastInsertId();
        $token = Auth::generateToken($user_id);
        
        http_response_code(201);
        echo json_encode([
            "status" => "success", 
            "message" => "User registered successfully.",
            "token" => $token,
            "user" => [
                "id" => $user_id,
                "name" => $name,
                "email" => $email,
                "phone" => $phone,
                "role" => 'user'
            ]
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["status" => "error", "message" => "Unable to register user."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Incomplete data."]);
}
?>
