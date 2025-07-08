<?php


class Response {
    public static function json($message = '', $data = null, $status = 200, $success = true) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ], JSON_PRETTY_PRINT); // 👈 adds formatting
        exit;
    }

    public static function success($message, $data = null) {
        self::json($message, $data, 200, true);
    }

    public static function error($message, $status = 400) {
        self::json($message, null, $status, false);
    }
}








