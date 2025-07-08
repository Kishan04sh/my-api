<?php 

require_once __DIR__ . '\..\config\database.php';

class Admin {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

   
    public function login($email, $password) {
    $query = "SELECT uc.*, ad.* FROM users_credential uc 
              INNER JOIN admin_detail ad ON uc.unique_id = ad.login_credential_id 
              WHERE uc.email_id = :email LIMIT 1";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['status'] !== 'Active') {
            return [
                'error' => true,
                'message' => 'Your account is inactive. Please contact the administrator.'
            ];
        }

        if (password_verify(trim($password), $user['password'])) {
            require_once __DIR__ . '/../helpers/token.php';

            $payload = [
                'id' => $user['admin_id'],
                'email' => $user['email_id'],
                'login_credential_id' => $user['login_credential_id'],
                'role' => $user['user_type']
            ];

            $token = Token::create($payload);
            unset($user['password']); // security

            return [
                'admin_id'      => $user['admin_id'],
                'status'        => $user['status'],
                'admin_name'    => $user['admin_name'],
                'email'         => $user['admin_email'],
                'mobile'        => $user['mobile_no'],
                'user_type'     => $user['user_type'],
                'is_superadmin' => $user['is_superadmin'],
                'profile_image' => $user['profile_img_name'],
                'token'         => $token
            ];
        }
    }

    return [
        'error' => true,
        'message' => 'Invalid email or password.'
    ];
}





//////////////////////////////////////////////////////////////////////////////

    public function getAdminById($admin_id) {
        $stmt = $this->db->prepare("SELECT * FROM admin_detail WHERE admin_id = :id LIMIT 1");
        $stmt->bindParam(':id', $admin_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


//////////////////////////////////////////////////////////////////////////



    public function getAllAdminsWithBranches() {
    // Step 1: Fetch all admins
    $stmt = $this->db->prepare("SELECT * FROM admin_detail WHERE is_superadmin = 'No'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Step 2: For each admin, get their branches
    foreach ($admins as &$admin) {
        $admin_id = $admin['admin_id'];

        $branchQuery = "SELECT b.id, b.branch_name, b.location, b.created_at
                        FROM admin_branch_map abm
                        INNER JOIN branches b ON abm.branch_id = b.id
                        WHERE abm.admin_id = :admin_id";

        $branchStmt = $this->db->prepare($branchQuery);
        $branchStmt->bindParam(':admin_id', $admin_id);
        $branchStmt->execute();
        $branches = $branchStmt->fetchAll(PDO::FETCH_ASSOC);

        $admin['branches'] = $branches;  // ✅ Attach branches under 'branches' key
    }
    return $admins;
}




//////////////////////////////////////////////////

public function createAdmin($data) {
    $uid = 'ADMIN_' . strtoupper(bin2hex(random_bytes(3)));
    $login_id = 'ADMIN_LOGIN_' . strtoupper(bin2hex(random_bytes(3)));
    $createdOn = date("Y-m-d H:i:s");

    // Insert into users_credential
    $stmt1 = $this->db->prepare("INSERT INTO users_credential 
        (unique_id, email_id, password, status, username, user_type, created_by, created_on) 
        VALUES (:uid, :email, :password, 'Active', :username, 'Admin', 'SuperAdmin', :created_on)");
    
    $stmt1->execute([
        ':uid' => $login_id,
        ':email' => $data['email'],
        ':password' => password_hash($data['password'], PASSWORD_BCRYPT),  //sha1($data['password']), 
        ':username' => strtoupper(bin2hex(random_bytes(4))),
        ':created_on' => $createdOn
    ]);

    // Insert into admin_detail
    $stmt2 = $this->db->prepare("INSERT INTO admin_detail 
        (admin_id, admin_name, admin_email, gender, login_credential_id, mobile_no, is_superadmin, created_by, created_on, status) 
        VALUES (:aid, :name, :email, :gender, :login_id, :mobile, 'No', 'SuperAdmin', :created_on, 'Active')");

    $stmt2->execute([
        ':aid' => $uid,
        ':name' => $data['admin_name'],
        ':email' => $data['email'],
        ':gender' => $data['gender'],
        ':login_id' => $login_id,
        ':mobile' => $data['mobile_no'],
        ':created_on' => $createdOn
    ]);

    return ['admin_id' => $uid];
}

////////////////////////////////////////////////////////////////////
public function getAdminByEmail($email) {
    $stmt = $this->db->prepare("SELECT * FROM admin_detail WHERE admin_email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

///////////////////////////////////////////////

public function updateAdminStatus($admin_id, $status) {
    $stmt = $this->db->prepare("UPDATE admin_detail SET status = :status WHERE admin_id = :id");
    return $stmt->execute([':status' => $status, ':id' => $admin_id]);
}


////////////////////////////////////////////////////////////
public function deleteAdmin($admin_id) {
    // First, get login_credential_id
    $stmt = $this->db->prepare("SELECT login_credential_id FROM admin_detail WHERE admin_id = :id");
    $stmt->execute([':id' => $admin_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) return false;

    $login_id = $result['login_credential_id'];

    $this->db->beginTransaction();

    try {
        $this->db->prepare("DELETE FROM admin_detail WHERE admin_id = :id")->execute([':id' => $admin_id]);
        $this->db->prepare("DELETE FROM users_credential WHERE unique_id = :id")->execute([':id' => $login_id]);
        $this->db->commit();
        return true;
    } catch (Exception $e) {
        $this->db->rollBack();
        return false;
    }
}

//////////////////////////////////////////////////////////////////////////////



// Update password
public function changePassword($loginId, $hashedPassword) {
    $query = "UPDATE users_credential SET password = :pass WHERE unique_id = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':pass', $hashedPassword);
    $stmt->bindParam(':id', $loginId);
    return $stmt->execute();
}

   // Fetch login credential by ID
public function getLoginCredentialById($loginId) {
    $query = "SELECT * FROM users_credential WHERE unique_id = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $loginId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



////////////////////////////////////////////////////////////////////////// Branches add oprestions
// ***************************************************************************************8


    // Add updateAdmin, addAdmin, etc. as needed
}