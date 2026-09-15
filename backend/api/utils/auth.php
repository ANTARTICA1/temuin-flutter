<?php

class Auth {
    private static $secret_key = "TEMUIN_SECRET_KEY_12345";

    public static function generateToken($user_id) {
        $payload = base64_encode($user_id);
        $signature = hash_hmac('sha256', $payload, self::$secret_key);
        return $payload . '.' . $signature;
    }

    public static function validateToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $headers = array();
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        }
        if (empty($headers) && function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        
        $authHeader = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
        
        if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Access denied. Missing Authorization header."]);
            exit;
        }

        $arr = explode(" ", $authHeader);
        
        if (count($arr) !== 2 || $arr[0] !== 'Bearer') {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Access denied. Invalid token format."]);
            exit;
        }

        $token = $arr[1];
        $tokenParts = explode('.', $token);

        if (count($tokenParts) !== 2) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Access denied. Invalid token."]);
            exit;
        }

        $payload = $tokenParts[0];
        $signature = $tokenParts[1];

        $valid_signature = hash_hmac('sha256', $payload, self::$secret_key);

        if (hash_equals($valid_signature, $signature)) {
            $user_id = base64_decode($payload);
            return (int) $user_id;
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Access denied. Token verification failed."]);
            exit;
        }
    }
}

if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst(strtolower($ak_val));
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }
}
?>
