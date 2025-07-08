<?php 
// File: controllers/AuthController.php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Branch.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/token.php';
require_once __DIR__ . '/../helpers/Request.php';

class BranchController {
 private $branch;
  private $admin;

    public function __construct() {
        $this->branch = new Branch();
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

////////////////////////////////////////////////////////////////////


public function addBranch() {
    $this->authorizeSuperAdmin(); 
    $data = Request::all();

    $errors = Validator::validate($data, [
        'branch_name' => 'required',
    ]);

    if (!empty($errors)) {
        Response::error($errors, 422);
    }

    $data['location'] = $data['location'] ?? null;

    $exists = $this->branch->isBranchExists($data['branch_name']);
    if ($exists) {
        Response::error("Branch already exists with same name", 409);
    }

    $branchId = $this->branch->createBranch($data);
    if ($branchId) {
        Response::success("Branch created", ['branch_id' => $branchId]);
    } else {
        Response::error("Failed to create branch");
    }
}


/////////////////////////////////////////////////////////////


public function assignBranchesToAdmin() {
    $this->authorizeSuperAdmin();
    $data = Request::all();

    $errors = Validator::validate($data, [
        'admin_id'   => 'required',
        'branch_ids' => 'required'
    ]);

    if (!empty($errors)) {
        Response::error($errors, 422);
    }

    try {
        $updated = $this->branch->assignBranches($data['admin_id'], $data['branch_ids']);
        Response::success("Branches assigned to admin", [
            'admin_id' => $data['admin_id'],
            'assigned_branch_ids' => $data['branch_ids']
        ]);
    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }
}


///////////////////////////////////////////////////////////////////

public function deleteBranch() {
    $this->authorizeSuperAdmin();
    $data = Request::all();

    $errors = Validator::validate($data, [
        'branch_id' => 'required'
    ]);

    if (!empty($errors)) {
        Response::error($errors, 422);
    }

    $deleted = $this->branch->deleteBranch($data['branch_id']);
    if ($deleted) {
        Response::success("Branch deleted successfully");
    } else {
        Response::error("Failed to delete branch");
    }
}


//////////////////////////////////////////////////////////

public function getBranchesByAdmin() {
    //$this->authorizeSuperAdmin(); // Optional: you can allow admin too
    $data = Request::all();

    $errors = Validator::validate($data, [
        'admin_id' => 'required'
    ]);

    if (!empty($errors)) {
        Response::error($errors, 422);
    }

    $branches = $this->branch->getBranchesByAdminId($data['admin_id']);
    Response::success("Assigned branches", $branches);
}


///////////////////////////////////////////////////////////////////////////
public function getAllBranches() {
    $branches = $this->branch->getBranches();

    if ($branches && count($branches) > 0) {
        Response::success("Branch lists", $branches);
    } else {
        Response::success("No branches found", []);
    }
}


public function getAvailableBranches() {
     $this->authorizeSuperAdmin(); // Optional: you can allow admin too
    $branches = $this->branch->getUnassignedBranches();
    Response::success("Available Branches", $branches);
}

////////////////////////////////////////////////////////////////////////////
// ********************************************************************************

} 