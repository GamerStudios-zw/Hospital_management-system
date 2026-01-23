<?php
class Response {
    public static function send($code, $message, $data = null) {
        http_response_code($code);
        echo json_encode([
            "status" => $code < 300 ? "success" : "error",
            "message" => $message,
            "data" => $data
        ]);
        exit();
    }
}
?>