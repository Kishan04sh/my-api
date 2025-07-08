<?php

class Request {
    public static function input($key, $default = null) {
        $body = $_POST;

        if (empty($body)) {
            $raw = file_get_contents("php://input");
            $json = json_decode($raw, true);
            $body = is_array($json) ? $json : [];
        }

        return $body[$key] ?? $default;
    }

    public static function all() {
        $body = $_POST;
        if (empty($body)) {
            $body = json_decode(file_get_contents("php://input"), true);
        }
        return $body ?? [];
    }

    public static function query($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    public static function header($key) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? null;
    }

    public static function method() {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }


    ///////////////////////////////////////////////////
    public static function uri() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Remove the project folder name like /my_Api if it exists
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    if (strpos($uri, $scriptName) === 0) {
        $uri = substr($uri, strlen($scriptName));
    }

    return rtrim($uri, '/') ?: '/';
}



}


