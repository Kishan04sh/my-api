<?php

require_once __DIR__ . '\..\config\database.php';

class Branch {

    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

///////////////////////////////////////////////////////

// public function createBranch($data) {
//     $query = "INSERT INTO branches (branch_name, location) VALUES (:branch_name, :location)";
//     $stmt = $this->db->prepare($query);
//     $stmt->bindParam(':branch_name', $data['branch_name']);
//     $stmt->bindParam(':location', $data['location']);
//     $stmt->execute();
//     return $this->db->lastInsertId();
// }

public function createBranch($data) {
    try {
        // ✅ Auto-generate short_code from branch_name
        $shortCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $data['branch_name']), 0, 3));

        $sql = "INSERT INTO branches (branch_name, location, short_code) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['branch_name'],
            $data['location'],
            $shortCode
        ]);

        return $this->db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/////////////////////////////////////////////////////////
public function getBranches() {
    $query = "SELECT * FROM branches ORDER BY id DESC";
    $stmt = $this->db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/////////////////////////////////////////////////////////////////

public function deleteBranch($id) {
    // Optional: Delete from map first
    $this->db->prepare("DELETE FROM admin_branch_map WHERE branch_id = :id")
             ->execute([':id' => $id]);

    $stmt = $this->db->prepare("DELETE FROM branches WHERE id = :id");
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

/////////////////////////////////////////////////////////////////////////

public function getBranchesByAdminId($admin_id) {
    $query = "SELECT b.id, b.branch_name, b.location 
              FROM admin_branch_map abm 
              INNER JOIN branches b ON abm.branch_id = b.id 
              WHERE abm.admin_id = :admin_id";
    
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':admin_id', $admin_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


 // Get only unassigned branches //////////////////////////////////////////
    public function getUnassignedBranches() {
        $query = "
            SELECT * FROM branches 
            WHERE id NOT IN (SELECT branch_id FROM admin_branch_map)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

////////////////////////////////////////////////////////



public function assignBranches($admin_id, $branch_ids) {
    if (is_string($branch_ids)) {
        $branch_ids = explode(',', $branch_ids);
    }

    // Check if any branch is already assigned to a different admin
    foreach ($branch_ids as $branch_id) {
        $stmt = $this->db->prepare("SELECT admin_id FROM admin_branch_map WHERE branch_id = :branch_id");
        $stmt->execute([':branch_id' => $branch_id]);
        $existing = $stmt->fetch();

        if ($existing && $existing['admin_id'] != $admin_id) {
            throw new Exception("Branch ID $branch_id is already assigned to another admin.");
        }
    }

    // Remove all previous assignments for this admin
    $this->db->prepare("DELETE FROM admin_branch_map WHERE admin_id = :admin_id")
        ->execute([':admin_id' => $admin_id]);

    // Assign new branches
    foreach ($branch_ids as $branch_id) {
        $stmt = $this->db->prepare("INSERT INTO admin_branch_map (admin_id, branch_id) VALUES (:admin_id, :branch_id)");
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':branch_id', $branch_id);
        $stmt->execute();
    }

    return true;
}


//////////////////////////////////////////////////////////////////////////////////

public function isBranchExists($branch_name) {
    $stmt = $this->db->prepare("SELECT id FROM branches WHERE branch_name = :branch_name LIMIT 1");
    $stmt->bindParam(':branch_name', $branch_name);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}