<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/BranchController.php';
require_once __DIR__ . '/controllers/BeltController.php';
require_once __DIR__ . '/controllers/StudentController.php';
require_once __DIR__ . '/controllers/StudentFeeController.php';
require_once __DIR__ . '/helpers/Request.php';

$routes = [
     //✅ Admin Auth + Management
    'POST /api/admin/login'   => [AuthController::class, 'login'],
    'POST /api/admin/create'  => [AdminController::class, 'createAdmin'],
    'GET /api/admin/list'     => [AdminController::class, 'listAdmins'],
    'POST /api/admin/status'  => [AdminController::class, 'updateAdminStatus'],
    'POST /api/admin/delete'  => [AdminController::class, 'deleteAdmin'],
    'POST /api/admin/change_password' => [AdminController::class, 'changePassword'],

    // ✅ Branch Management
    'POST /api/branch/add' => [BranchController::class, 'addBranch'],
    'GET /api/branch/list' => [BranchController::class, 'getAllBranches'],
    'GET /api/branch/getAvailableBranches' => [BranchController::class, 'getAvailableBranches'],
    'POST /api/branch/delete' => [BranchController::class, 'deleteBranch'],
    'POST /api/branch/by_admin' => [BranchController::class, 'getBranchesByAdmin'],
    'POST /api/branch/addBranchToadmin' => [BranchController::class, 'assignBranchesToAdmin'],

        // ✅ Belt Management
    'POST /api/belt/saveBelt' => [BeltController::class, 'saveBelt'],
    'POST /api/belt/delete'  => [BeltController::class, 'deleteBelt'], 
    'POST /api/belt/syllabus' => [BeltController::class, 'getBeltSyllabus'], 
    'GET /api/belt/all'   => [BeltController::class, 'getAllBelts'],

    // ✅ Syllabus Topic Management
    'POST /api/belt/mapTopics'  => [BeltController::class, 'mapTopicsToBelt'],
    'POST /api/belt/unassignedTopic' => [BeltController::class, 'getUnassignedPrivateTopicsByBelt'],
    'GET /api/belt/allTopic'   => [BeltController::class, 'getAllTopic'],
    'POST /api/topic/save'   => [BeltController::class, 'saveTopic'],
    'POST /api/topic/delete' => [BeltController::class, 'deleteTopic'], 

    // ✅ student Management API
    'POST /api/student/save' => [StudentController::class,'saveStudent'],
    'POST /api/student/login' => [StudentController::class, 'login'],
    'POST /api/student/Status' => [StudentController::class, 'toggleStatus'],
    'POST /api/student/changePassword' => [StudentController:: class, 'changePassword'],
    'POST /api/student/GetByBranchId' => [StudentController::class, 'getByBranchId'],
    'POST /api/student/GetStudentById' => [StudentController::class, 'getStudent'],
    'POST /api/student/deleteStudentById' => [StudentController::class, 'deleteStudent'],

    // sturent fee payment 
    'POST /api/fee_structure/save'    => [StudentFeeController::class, 'saveOrUpdateFeeStructure'],
    'POST /api/fee_structure/delete'  => [StudentFeeController::class, 'deleteFeeStructure'],
    'POST /api/fee_structure/list'    => [StudentFeeController::class, 'getFeeStructuresByBranch'],
    'POST /api/student_fee/pay'       => [StudentFeeController::class, 'payFee'],
    'POST /api/student_fee/FeeHistory' => [StudentFeeController::class, 'getStudentFeeHistory'],
    'POST /api/student_fee/FeeAdminHistory' => [StudentFeeController::class, 'getAdminIdFeeHistory'],
    'POST /api/student_fee/PendingList' => [StudentFeeController::class, 'getPendingStudentsByBranch'],
    'POST /api/student_fee/allFeeByAdmin' => [StudentFeeController::class, 'getAllfeeStructureByAdmin'],

];

$method = Request::method();
$uri = Request::uri();
$routeKey = "$method $uri";

if (isset($routes[$routeKey])) {
    [$controller, $action] = $routes[$routeKey];
    (new $controller())->$action();
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Route not found: $routeKey"], JSON_PRETTY_PRINT);
}





