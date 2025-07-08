<?php

require_once __DIR__ . '/../config/database.php';

class Student {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // 🔁 Add or Update Student
    public function save($data, $id = null) {
        try {
        
            if (empty($data['enroll_no']) && !empty($data['branch_id'])) {
                $data['enroll_no'] = $this->generateEnrollNo($data['branch_id']);
            }

            // 🔐 Default password (only for insert)
            if (!$id && empty($data['password'])) {
                $data['password'] = password_hash('12345', PASSWORD_DEFAULT);
            }

            // 🕒 Default admission date
            $data['admission_date'] = $data['admission_date'] ?? date('Y-m-d');

            if ($id) {
                // 🔄 Update
                $sql = "UPDATE students SET
                    admission_date=?, surname=?, name=?, father_name=?, mother_name=?, occupation=?,
                    branch_id=?, belt_id=?, gender=?, dob=?, aadhar_no=?, contact_no=?, tel_no=?, email=?,
                    height=?, weight=?, blood_group=?, address=?, disability=?, reference_by=?, experience=?,
                    whatsapp=?, school=?, student_photo=?, pd_form=?, experience_file=?, status=?, updated_at=NOW()
                    WHERE id=?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $data['admission_date'],
                    $data['surname'],
                    $data['name'],
                    $data['father_name'],
                    $data['mother_name'],
                    $data['occupation'] ?? null,
                    $data['branch_id'],
                    $data['belt_id'],
                    $data['gender'],
                    $data['dob'],
                    $data['aadhar_no'] ?? null,
                    $data['contact_no'],
                    $data['tel_no'] ?? null,
                    $data['email'],
                    $data['height'] ?? null,
                    $data['weight'] ?? null,
                    $data['blood_group'] ?? null,
                    $data['address'],
                    $data['disability'] ?? null,
                    $data['reference_by'] ?? null,
                    $data['experience'] ?? null,
                    $data['whatsapp'],
                    $data['school'] ?? null,
                    $data['student_photo'],
                    $data['pd_form'],
                    $data['experience_file'],
                    $data['status'] ?? 1,
                    $id
                ]);
                return ['id' => $id];
            } else {
                // ➕ Insert
                $sql = "INSERT INTO students (
                    enroll_no, admission_date, surname, name, father_name, mother_name, occupation,
                    branch_id, belt_id, gender, dob, aadhar_no, contact_no, tel_no, email,
                    height, weight, blood_group, address, disability, reference_by, experience,
                    whatsapp, school, student_photo, pd_form, experience_file, status, password
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $data['enroll_no'],
                    $data['admission_date'],
                    $data['surname'],
                    $data['name'],
                    $data['father_name'],
                    $data['mother_name'],
                    $data['occupation'] ?? null,
                    $data['branch_id'],
                    $data['belt_id'],
                    $data['gender'],
                    $data['dob'],
                    $data['aadhar_no'] ?? null,
                    $data['contact_no'],
                    $data['tel_no'] ?? null,
                    $data['email'],
                    $data['height'] ?? null,
                    $data['weight'] ?? null,
                    $data['blood_group'] ?? null,
                    $data['address'],
                    $data['disability'] ?? null,
                    $data['reference_by'] ?? null,
                    $data['experience'] ?? null,
                    $data['whatsapp'],
                    $data['school'] ?? null,
                    $data['student_photo'],
                    $data['pd_form'],
                    $data['experience_file'],
                    $data['status'] ?? 1,
                    $data['password']
                ]);
                return ['id' => $this->db->lastInsertId()];
            }

        } catch (PDOException $e) {
            return ['error' => true, 'message' => 'DB Error: ' . $e->getMessage()];
        }
    }

//**************************************************************** */ get eron number by uniq

private function generateEnrollNo($branchId) {
    // ✅ Get Branch Short Code
    $stmt = $this->db->prepare("SELECT short_code FROM branches WHERE id = ?");
    $stmt->execute([$branchId]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$branch) return null;

    $shortCode = strtoupper($branch['short_code']);
    $year = date('y');  // Example: 2025 → 25

    $likePattern = $shortCode . $year . '-%';

    // ✅ Get latest roll no for this branch & year
    $stmt = $this->db->prepare("SELECT enroll_no FROM students WHERE enroll_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$likePattern]);
    $lastEnroll = $stmt->fetchColumn();

    $nextRoll = 1;
    if ($lastEnroll) {
        // Extract roll number from last enroll_no
        $parts = explode('-', $lastEnroll);
        if (isset($parts[1]) && is_numeric($parts[1])) {
            $nextRoll = intval($parts[1]) + 1;
        }
    }

    // ✅ Keep looping till getting a truly unique (just in case)
    do {
        $rollLength = 3;  // Minimum 3 digit
        $roll = str_pad($nextRoll, $rollLength, '0', STR_PAD_LEFT);
        $enrollNo = $shortCode . $year . '-' . $roll;
        $nextRoll++;
    } while ($this->enrollExists($enrollNo));

    return $enrollNo;
}




// ***************************************************🔍 Get student by ID
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => true, 'message' => 'Fetch failed: ' . $e->getMessage()];
        }
    }


   public function getStudentDetailsById($studentId) {
    $stmt = $this->db->prepare("
        SELECT 
            s.id AS student_id,
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
        FROM students s
        LEFT JOIN branches b ON s.branch_id = b.id
        LEFT JOIN exam_fees blt ON s.belt_id = blt.id
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}




// *********************************************************** 🔍 Get all by branch


   public function getByBranchId($branchId) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                students.*,
                branches.branch_name AS branch_name,
                exam_fees.belt_name AS belt_name,
                exam_fees.fee AS belt_fee,
                exam_fees.color AS belt_color
            FROM students
            LEFT JOIN branches ON students.branch_id = branches.id
            LEFT JOIN exam_fees ON students.belt_id = exam_fees.id
            WHERE students.branch_id = ?
            ORDER BY students.id DESC
        ");
        $stmt->execute([$branchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [
            'error' => true,
            'message' => 'Fetch failed: ' . $e->getMessage()
        ];
    }
}




// **************************************************************❌ Delete
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM students WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return ['error' => true, 'message' => 'Delete failed: ' . $e->getMessage()];
        }
    }

//*************************************************888 */ 🔄 Toggle active/inactive
    public function toggleStatus($id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE students SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            return ['error' => true, 'message' => 'Status update failed: ' . $e->getMessage()];
        }
    }

// *****************************************************🔐 Login (by enroll)
    public function getByEnrollNo($enroll_no) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM students WHERE enroll_no = ?");
            $stmt->execute([$enroll_no]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

// **************************************************************8🔑 Change password
    public function changePassword($enroll_no, $newPassword) {
        try {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE students SET password = ? WHERE enroll_no = ?");
            return $stmt->execute([$hashed, $enroll_no]);
        } catch (PDOException $e) {
            return false;
        }
    }

// *************************************************** 🔁 Check existing enroll
    private function enrollExists($enroll_no, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM students WHERE enroll_no = ?";
        $params = [$enroll_no];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }



// ***************************************************************
}
