<?php

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/Token.php';
require_once __DIR__ . '/../helpers/Request.php';
require_once __DIR__ . '/../helpers/FileUploadHelper.php';

class StudentController {
    private $student;
    private $admin;

    public function __construct() {
        $this->student = new Student();
        $this->admin = new Admin();
    }

    // ✅ Super Admin Authorization
    private function authorizeSuperAdmin() {
        $token = Request::header('Authorization');
        if (!$token) Response::error("Missing token", 401);

        $token = str_replace("Bearer ", "", $token);
        $payload = Token::verify($token);

        if (!$payload || $payload->role !== 'Admin') {
            Response::error("Unauthorized or expired token", 403);
        }

        $admin = $this->admin->getAdminById($payload->id);
        if (!$admin || $admin['is_superadmin'] !== 'Yes') {
            Response::error("Access Denied. Only Super Admin allowed", 403);
        }

        return $payload;
    }

//************************************************ */ ✅ Add or Update Student
    public function saveStudent() {
        try {
            $data = Request::all();
            $files = $_FILES;
            $id = $data['id'] ?? null;

            // ✅ Validate required fields
            $required = [
                'surname', 'name', 'father_name', 'mother_name', 'branch_id',
                'belt_id', 'gender', 'dob', 'address', 'contact_no',
            ];
            $errors = Validator::validate($data, array_fill_keys($required, 'required'));
            if ($errors) return Response::error($errors, 422);

            // ✅ File Uploads (optional)
            // $data['student_photo'] = isset($files['student_photo']) ? FileUploadHelper::upload($files['student_photo'], 'uploads/student/') : ($data['student_photo'] ?? null);
           // ✅ Handle student_photo
            if (isset($files['student_photo'])) {
                // 🔄 If updating, delete old photo
                if (!empty($data['id']) && !empty($data['student_photo']) && file_exists($data['student_photo'])) {
                    unlink($data['student_photo']);
                }
                $data['student_photo'] = FileUploadHelper::upload($files['student_photo'], 'uploads/student/');
            } else {
                // 🛑 No new file, keep existing if any
                $data['student_photo'] = $data['student_photo'] ?? null;
            }

           
            $data['pd_form'] = isset($files['pd_form']) ? FileUploadHelper::upload($files['pd_form'], 'uploads/student/') : ($data['pd_form'] ?? null);
            $data['experience_file'] = isset($files['experience_file']) ? FileUploadHelper::upload($files['experience_file'], 'uploads/student/') : ($data['experience_file'] ?? null);

            // ✅ Default status if not set
            $data['status'] = $data['status'] ?? 1;

            // ✅ Default password (on create only)
            if (!$id) {
                $data['password'] = password_hash('12345', PASSWORD_DEFAULT);
            }

            // ✅ Save via model
            $result = $this->student->save($data, $id);

            // ✅ Success response
            Response::success($id ? 'Student updated successfully' : 'Student added successfully', $result);

        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage(), 500);
        }
    }

 //********************************************** */ ✅ Student Login
    public function login() {
        try {
            $data = Request::all();
            // ✅ Input validation
            $errors = Validator::validate($data, [
                'enroll_no' => 'required',
                'password' => 'required'
            ]);
            if (!empty($errors)) {
                return Response::error($errors, 422);
            }

            // ✅ Fetch student
            $student = $this->student->getByEnrollNo($data['enroll_no']);

            if (!$student || isset($student['error'])) {
                return Response::error($student['message'] ?? "Invalid credentials", 401);
            }
            // ✅ Password match check
            if (!password_verify($data['password'], $student['password'])) {
                return Response::error("Invalid credentials", 401);
            }
            // ✅ Check if inactive
            if ($student['status'] == 0) {
                return Response::error("Your account is inactive. Please contact admin.", 403);
            }
            // ✅ Generate secure token
            $token = Token::create([
                'id' => $student['id'],
                'role' => 'Student',
                'name' => $student['name']
            ]);
            // ✅ Response
            return Response::success("Login successful", [
                'id' => $student['id'],
                'name' => $student['name'],
                'enroll_no' => $student['enroll_no'],
                'contact_no' => $student['contact_no'],
                'whatsapp' => $student['whatsapp'],
                'status' => $student['status'],
                'token' => $token,
            ]);
        } catch (Exception $e) {
            return Response::error("Login failed: " . $e->getMessage(), 500);
        }
    }


 // *******************************************✅ Toggle student status
    public function toggleStatus() {
        // $this->authorizeSuperAdmin();
        $data = Request::all();
        $errors = Validator::validate($data, [
            'id' => 'required',
            'status' => 'required'
        ]);
        if ($errors) return Response::error($errors, 422);

        $updated = $this->student->toggleStatus($data['id'], $data['status']);
        if ($updated) {
            Response::success("Status updated successfully");
        } else {
            Response::error("Status update failed");
        }
    }



// ✅ Change password ***********************************************
    public function changePassword() {
        $data = Request::all();
        $errors = Validator::validate($data, [
            'enroll_no' => 'required',
            'new_password' => 'required|min:5'
        ]);

        if ($errors) return Response::error($errors, 422);

        $changed = $this->student->changePassword($data['enroll_no'], $data['new_password']);
        if ($changed) {
            Response::success("Password updated successfully");
        } else {
            Response::error("Password update failed");
        }
    }

//********************************************** */ ✅ Get all students by branch
    public function getByBranchId() {
        try {
        $data = Request::all();
        $errors = Validator::validate($data, [
            'branchId' => 'required',
        ]);
        if ($errors) return Response::error($errors, 422);
            $students = $this->student->getByBranchId($data['branchId']);
            Response::success("Student list fetched", $students);
        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage());
        }
    }

//********************************************* */ ✅ Get single student by ID
    public function getStudent() {
        try {
            $data = Request::all();
            $errors = Validator::validate($data, [
                'studentId' => 'required',
            ]);
            if ($errors) return Response::error($errors, 422);
            $student = $this->student->getById($data['studentId']);
            if (!$student) {
                Response::error("Student not found", 404);
            }
            Response::success("Student fetched", $student);
        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage());
        }
    }

// ********************************************✅ Delete Student
    public function deleteStudent() {
        try {
            $data = Request::all();
            $errors = Validator::validate($data, [
                'studentId' => 'required',
            ]);
            if ($errors) return Response::error($errors, 422);

            $deleted = $this->student->delete($data['studentId']);
            if ($deleted) {
                Response::success("Student deleted successfully");
            } else {
                Response::error("Delete failed", 500);
            }
        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage());
        }
    }



// *****************************************************************************


}
