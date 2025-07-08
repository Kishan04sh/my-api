<?php 
// File: controllers/AuthController.php
require_once __DIR__ . '\..\models\Admin.php';
require_once __DIR__ . '\..\helpers\Response.php';
require_once __DIR__ . '\..\helpers\Validator.php';
require_once __DIR__ . '\..\helpers\token.php';
require_once __DIR__ . '\..\helpers\Request.php'; 

class AuthController {

 public function login() {
        $data = Request::all();

        $errors = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:3'
        ]);

        if (!empty($errors)) {
            Response::error($errors, 422);
         }

        $adminModel = new Admin();
        $user = $adminModel->login($data['email'], $data['password']);

        // ✅ Check if model returned an error
        if (isset($user['error']) && $user['error'] === true) {
            Response::error($user['message'], 401);
        }

        // ✅ Successful login
        Response::success("Login successful", $user);

    }

} 