<?php

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/token.php';
require_once __DIR__ . '/../helpers/Request.php';

class AdminController
{
    private $admin;

    public function __construct() {
        $this->admin = new Admin();
    }

//////////////////////////////////////////////////////////////
    private function authorizeSuperAdmin() {
        $token = Request::header('Authorization');
        if (!$token) Response::error("Missing token", 401);

        $token = str_replace("Bearer ", "", $token);
        $payload = Token::verify($token);

        if (!$payload || $payload->role !== 'Admin') {
            Response::error("Unauthorized ! Token Expried", 403);
        }

        // Optional: get superadmin from db
        $admin = $this->admin->getAdminById($payload->id);
        if (!$admin || $admin['is_superadmin'] !== 'Yes') {
            Response::error("Access Denied. Only Super Admin allowed", 403);
        }

        return $payload;
    }

///////////////////////////////////////////////////////////
    public function createAdmin() {
        $this->authorizeSuperAdmin();
        $data = Request::all();

        $errors = Validator::validate($data, [
            'admin_name' => 'required',
            'email'      => 'required|email',
            'password'   => 'required|min:4',
            'mobile_no'  => 'required',
            'gender'     => 'required|in:Male,Female,Other'
        ]);

        // if ($errors) Response::error("Validation failed", 422);
         if (!empty($errors)) {
            Response::error($errors, 422);
         }


           // ✅ Check if email already exists
        $existing = $this->admin->getAdminByEmail($data['email']);
        if ($existing) {
            Response::error("Email already registered", 409); // 409 = Conflict
        }

        $result = $this->admin->createAdmin($data);
        if ($result) {
            Response::success("Admin created successfully", $result);
        } else {
            Response::error("Failed to create admin");
        }
    }


////////////////////////////////////////////////////////////////

    public function listAdmins() {
        $this->authorizeSuperAdmin();
        $admins = $this->admin->getAllAdminsWithBranches();
        Response::success("Admin list", $admins);
    }

/////////////////////////////////////////////////////////////////
    public function updateAdminStatus() {
        $this->authorizeSuperAdmin();
        $data = Request::all();

        $errors = Validator::validate($data, [
            'admin_id' => 'required',
            'status'   => 'required|in:Active,Inactive'
        ]);

         if (!empty($errors)) {
            Response::error($errors, 422);
         }

        $updated = $this->admin->updateAdminStatus($data['admin_id'], $data['status']);
        if ($updated) {
            Response::success("Admin status updated successfully");
        } else {
            Response::error("Admin not found or update failed");
        }
    }

/////////////////////////////////////////////////////////////////
    public function deleteAdmin() {
        $this->authorizeSuperAdmin();
        $data = Request::all();

        $errors = Validator::validate($data, [
            'admin_id' => 'required'
        ]);

       if (!empty($errors)) {
            Response::error($errors, 422);
         }

        $deleted = $this->admin->deleteAdmin($data['admin_id']);
        if ($deleted) {
            Response::success("Admin deleted successfully");
        } else {
            Response::error("Delete failed or admin not found");
        }
    }

///////////////////////////////////////////////////////////////////////////////////

public function changePassword() {
    $data = Request::all();
    $errors = Validator::validate($data, [
        'old_password'     => 'required',
        'new_password'     => 'required|min:4',
        'confirm_password' => 'required'
    ]);

    if (!empty($errors)) {
        Response::error($errors, 422);
    }

    if ($data['new_password'] !== $data['confirm_password']) {
        Response::error("New and confirm password do not match", 422);
    }

    $token = Request::header('Authorization');
    if (!$token) Response::error("Missing token", 401);

    $token = str_replace("Bearer ", "", $token);
    $payload = Token::verify($token);

    if (!$payload || $payload->role !== 'Admin') {
        Response::error("Unauthorized Token Expried", 403);
    }

    $loginId = $payload->login_credential_id;
    $admin = $this->admin->getLoginCredentialById($loginId);

    if (!$admin) {
        Response::error("Admin not found", 404);
    }

    if (!password_verify(trim($data['old_password']), $admin['password'])) {
        Response::error("Old password is incorrect", 400);
    }

    $newHashedPassword = password_hash(trim($data['new_password']), PASSWORD_BCRYPT);
    $updated = $this->admin->changePassword($loginId, $newHashedPassword);
    if ($updated) {
        Response::success("Password changed successfully");
    } else {
        Response::error("Failed to change password");
    }
}

////////////////////////////////////////////////////////////////////////// branch apis
// ****************************************************************************************





}
