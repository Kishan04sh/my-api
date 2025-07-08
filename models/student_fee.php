<?php
require_once __DIR__ . '/../config/database.php';

class Student_fee {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

// *******************************************************************

public function saveFeeStructure($data) {
    $stmt = $this->db->prepare("
        INSERT INTO fee_structure (branch_id, duration, amount, discount, created_by_admin)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([
        $data['branch_id'],
        $data['duration'],
        $data['amount'],
        $data['discount'],
        $data['admin_id']
    ]);
}

public function updateFeeStructure($data) {
    $stmt = $this->db->prepare("
        UPDATE fee_structure SET duration = ?, amount = ?, discount = ?
        WHERE id = ? AND branch_id = ?
    ");
    return $stmt->execute([
        $data['duration'],
        $data['amount'],
        $data['discount'],
        $data['id'],
        $data['branch_id']
    ]);
}

public function deleteFeeStructure($id,) {
    $stmt = $this->db->prepare("DELETE FROM fee_structure WHERE id = ?");
    return $stmt->execute([$id]);
}

public function getFeeStructuresByBranch($branch_id) {
    $stmt = $this->db->prepare("SELECT * FROM fee_structure WHERE branch_id = ?");
    $stmt->execute([$branch_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getFeeStructuresByAdmin($adminId) {
    $stmt = $this->db->prepare("
        SELECT 
            fs.*, 
            b.branch_name AS branch_name
        FROM 
            fee_structure fs
        JOIN 
            branches b ON fs.branch_id = b.id
        WHERE 
            fs.created_by_admin = ?
        ORDER BY 
            fs.branch_id ASC, fs.duration ASC
    ");
    $stmt->execute([$adminId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function feeStructureExists($branch_id, $duration) {
    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM fee_structure WHERE branch_id = ? AND duration = ?");
    $stmt->execute([$branch_id, $duration]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] > 0;
}

///*********************************************************************** */

public function getFeeStructureById($id) {
    $stmt = $this->db->prepare("SELECT * FROM fee_structure WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getLastFeeId() {
    $stmt = $this->db->query("SELECT MAX(id) as lastId FROM student_fee");
    $row = $stmt->fetch();
    return $row['lastId'] ?? 0;
}

public function saveStudentFee($data) {
    $stmt = $this->db->prepare("
        INSERT INTO student_fee 
        (payment_id, student_id, branch_id, admin_id, fee_structure_id, months_paid, amount_paid, discount, payment_mode, payment_date, start_date, end_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    return $stmt->execute([
        $data['payment_id'],
        $data['student_id'],
        $data['branch_id'],
        $data['admin_id'],
        $data['fee_structure_id'],
        $data['months_paid'],
        $data['amount_paid'],
        $data['discount'],
        $data['payment_mode'],
        $data['payment_date'],
        $data['start_date'],
        $data['end_date']
    ]);
}

/// ************************************************** get student id to fee data

public function getFeeHistoryByStudent($student_id) {
    $stmt = $this->db->prepare("
        SELECT * FROM student_fee 
        WHERE student_id = ? 
        ORDER BY payment_date DESC
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getFeeHistoryByAdminId($admin_id) {
    $stmt = $this->db->prepare("
        SELECT * FROM student_fee 
        WHERE admin_id = ? 
        ORDER BY payment_date DESC
    ");
    $stmt->execute([$admin_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/// **************************************************************************** get pending list for admin
public function getPendingStudentsByBranch($branch_id) {
    $stmt = $this->db->prepare("
        SELECT s.id AS student_id, s.name, s.contact_no, MAX(f.end_date) AS last_paid_till
        FROM students s
        LEFT JOIN student_fee f ON s.id = f.student_id
        WHERE s.branch_id = ?
        GROUP BY s.id
        HAVING last_paid_till IS NULL OR last_paid_till < CURDATE()
    ");
    $stmt->execute([$branch_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/// *****************************************************************************


public function getFeeHistoryByAdminIdWithDetails($admin_id) {
    $stmt = $this->db->prepare("
        SELECT 
            sf.*, 
            s.name,
            s.enroll_no,
            s.surname,
            s.father_name,
            s.contact_no,
            s.whatsapp,
            s.email,
            s.occupation AS qualification,
            s.student_photo AS image,
            s.branch_id,
            b.branch_name,
            s.belt_id,
            blt.belt_name
        FROM student_fee sf
        LEFT JOIN students s ON sf.student_id = s.id
        LEFT JOIN branches b ON s.branch_id = b.id
        LEFT JOIN exam_fees blt ON s.belt_id = blt.id
        WHERE sf.admin_id = ?
        ORDER BY sf.payment_date DESC
    ");
    $stmt->execute([$admin_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/// *****************************************************




}
