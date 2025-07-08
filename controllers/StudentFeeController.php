<?php
require_once __DIR__ . '/../models/student_fee.php';
require_once __DIR__ . '/../models/student.php';
require_once __DIR__ . '/../helpers/Request.php';
require_once __DIR__ . '/../helpers/Response.php';

class StudentFeeController {
    private $fee;
    private $student;

    public function __construct() {
        $this->fee = new Student_fee();
        $this->student = new Student();
    }


///**************************************************************** */

// ✅ Create Fee Structure (Admin Only) 
    public function saveOrUpdateFeeStructure() {
        try {
            $data = Request::all();

            // ✅ Common validation
            $required = ['branch_id', 'duration', 'amount', 'discount', 'admin_id', 'id'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    return Response::error("$field is required", 422);
                }
            }

            if ($data['id'] === 'NA') {
                // ✅ Check duplicate before insert
                if ($this->fee->feeStructureExists($data['branch_id'], $data['duration'])) {
                    return Response::error("Fee structure for this branch and duration already exists.", 409);
                }
                // ✅ Create new
                $save = $this->fee->saveFeeStructure($data);
                if ($save) {
                    return Response::success("Fee structure created successfully.");
                }
                return Response::error("Failed to save structure.");
            } else {
                // ✅ Update existing
                $update = $this->fee->updateFeeStructure($data);
                if ($update) {
                    return Response::success("Fee structure updated successfully.");
                }
                return Response::error("Failed to update structure.");
            }
        } catch (Exception $e) {
            return Response::error("Something went wrong: " . $e->getMessage(), 500);
        }
    }


// ✅ Delete Fee Structure **********************************************************
public function deleteFeeStructure() {
    try {
        $data = Request::all();
        if (empty($data['id'])) {
            return Response::error("idrequired", 422);
        }

        $deleted = $this->fee->deleteFeeStructure($data['id']);
        if ($deleted) {
            return Response::success("Fee structure deleted.");
        }
        return Response::error("Delete failed.");
    } catch (Exception $e) {
        return Response::error($e->getMessage(), 500);
    }
}

// ✅ List Fee Structures by Branch
public function getFeeStructuresByBranch() {
    try {
         $data = Request::all();
        if (empty($data['branch_id'])) {
            return Response::error("branch_id required", 422);
        }

        $structures = $this->fee->getFeeStructuresByBranch($data['branch_id']);
        return Response::success("Data found", $structures);
    } catch (Exception $e) {
        return Response::error($e->getMessage(), 500);
    }
}


    public function getAllfeeStructureByAdmin() {
        try {
             $data = Request::all();
            if (empty($data['admin_id'])) {
                return Response::error("admin_id required", 422);
            }
            $structures = $this->fee->getFeeStructuresByAdmin($data['admin_id']);
            Response::success("All Fee Structures fetched", $structures);
        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage(), 500);
        }
    }


/// *********************************************************************** pay fee

 public function payFee() {
        try {
            $data = Request::all();

            $required = ['student_id', 'branch_id', 'admin_id', 'fee_structure_id', 'payment_mode'];
            foreach ($required as $field) {
                if (empty($data[$field])) return Response::error("$field is required", 422);
            }

            $structure = $this->fee->getFeeStructureById($data['fee_structure_id']);
            if (!$structure) return Response::error("Fee structure not found", 404);

            preg_match('/(\d+)/', strtolower($structure['duration']), $match);
            $monthsPaid = intval($match[1]);
            if ($monthsPaid <= 0) return Response::error("Invalid duration format", 422);

            $lastId = $this->fee->getLastFeeId();
            $receiptId = "FSKA-" . date("Y") . "-" . str_pad($lastId + 1, 4, "0", STR_PAD_LEFT);

            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+$monthsPaid months", strtotime($startDate)));
            $paymentDate = date('Y-m-d H:i:s');
            $amount     = floatval($structure['amount']);
            $discount   = floatval($structure['discount']);
            $totalPayable = $amount - $discount;
            $paymentMode = $data['payment_mode'];

            $result = $this->fee->saveStudentFee([
                'payment_id'       => $receiptId,
                'student_id'       => $data['student_id'],
                'branch_id'        => $data['branch_id'],
                'admin_id'         => $data['admin_id'],
                'fee_structure_id' => $data['fee_structure_id'],
                'months_paid'      => $monthsPaid,
                'amount_paid'      => $totalPayable,
                'discount'         => $structure['discount'],
                'payment_mode'     => $paymentMode,
                'payment_date'     => $paymentDate,
                'start_date'       => $startDate,
                'end_date'         => $endDate
            ]);

            if (!$result) return Response::error("Fee payment failed", 500);

            $student = $this->student->getStudentDetailsById($data['student_id']);

            return Response::success("Fee paid successfully", [
                'receipt_id'   => $receiptId,
                'payment_mode'   => $paymentMode,
                'months_paid'  => $monthsPaid,
                'original_fee' => $amount,
                'discount'     => $discount,
                'paid_amount'  => $totalPayable,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
                'payment_date' => $paymentDate,
                'student'      => $student,
            ]);

        } catch (Exception $e) {
            return Response::error("Something went wrong: " . $e->getMessage(), 500);
        }
    }


/// ****************************************************************** get fee history by student id 

    public function getStudentFeeHistory() {
        try {
            $data = Request::all();
            if (empty($data['student_id'])) {
                return Response::error("student_id required", 422);
            }
            $records = $this->fee->getFeeHistoryByStudent($data['student_id']);
            return Response::success("Fee history fetched successfully", $records);
        } catch (Exception $e) {
            return Response::error("Something went wrong: " . $e->getMessage(), 500);
        }
    }




/// *********************************************************************** admin id 

    //public function getAdminIdFeeHistory() {
    //     try {
    //         $data = Request::all();
    //         if (empty($data['admin_id'])) {
    //             return Response::error("admin_id required", 422);
    //         }
    //         $records = $this->fee->getFeeHistoryByAdminId($data['admin_id']);
    //         return Response::success("Fee history fetched successfully", $records);
    //     } catch (Exception $e) {
    //         return Response::error("Something went wrong: " . $e->getMessage(), 500);
    //     }
    // }

    public function getAdminIdFeeHistory() {
    try {
        $data = Request::all();
        if (empty($data['admin_id'])) {
            return Response::error("admin_id required", 422);
        }

        $records = $this->fee->getFeeHistoryByAdminIdWithDetails($data['admin_id']);

        $result = array_map(function ($row) {
            $original_fee = floatval($row['amount_paid']) + floatval($row['discount']);
            return [
                'receipt_id'   => $row['payment_id'],
                'payment_mode' => $row['payment_mode'],
                'months_paid'  => $row['months_paid'],
                'original_fee' => $original_fee,
                'discount'     => floatval($row['discount']),
                'paid_amount'  => floatval($row['amount_paid']),
                'start_date'   => $row['start_date'],
                'end_date'     => $row['end_date'],
                'payment_date' => $row['payment_date'],
                'student'      => [
                    'student_id'   => $row['student_id'],
                    'name'         => $row['name'],
                    'enroll_no'    => $row['enroll_no'],
                    'surname'      => $row['surname'],
                    'father_name'  => $row['father_name'],
                    'contact_no'   => $row['contact_no'],
                    'whatsapp'     => $row['whatsapp'],
                    'email'        => $row['email'],
                    'qualification'=> $row['qualification'],
                    'image'        => $row['image'],
                    'branch_id'    => $row['branch_id'],
                    'branch_name'  => $row['branch_name'],
                    'belt_id'      => $row['belt_id'],
                    'belt_name'    => $row['belt_name']
                ]
            ];
        }, $records);

        return Response::success("Admin Fee History fetched successfully", $result);

    } catch (Exception $e) {
        return Response::error("Something went wrong: " . $e->getMessage(), 500);
    }
}


 /// ************************************************************************ get all pending list

    public function getPendingStudentsByBranch() {
        try {
            $data = Request::all();
            if (empty($data['branch_id'])) {
                return Response::error("branch_id required", 422);
            }
            $records = $this->fee->getPendingStudentsByBranch($data['branch_id']);
            return Response::success("Pending students fetched successfully", $records);
        } catch (Exception $e) {
            return Response::error("Something went wrong: " . $e->getMessage(), 500);
        }
    }


/// **********************************************************


}
