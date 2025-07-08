<?php 

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Belt.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/token.php';
require_once __DIR__ . '/../helpers/Request.php';

class BeltController {
    private $belt;
    private $admin;

    public function __construct() {
        $this->belt = new Belt();
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

    //******************************************************8 */ ✅ Add Belt and edit
   
public function saveBelt() {
    try {
         $this->authorizeSuperAdmin();
        $data = Request::all();
        $id = $data['id'] ?? null;

        $errors = Validator::validate($data, [
            'belt_name' => 'required',
            'belt_level' => 'required|numeric',
            'fee' => 'required|numeric',
            'color' => 'required', // ✅ New Color Validation
        ]);

        if (!empty($errors)) return Response::error($errors, 422);

        $result = $this->belt->saveBelt($data, $id);

        if (isset($result['error'])) {
            return Response::error($result['message'], 409);
        }

        if ($id) {
            Response::success("Belt updated successfully", ['id' => $id]);
        } else {
            Response::success("Belt added successfully", ['id' => $result['id']]);
        }
    } catch (Exception $e) {
        Response::error("Server error: " . $e->getMessage(), 500);
    }
}



    // ✅ Delete Belt ***************************************************************

    public function deleteBelt() {
        try {
            $this->authorizeSuperAdmin();
            $data = Request::all();

            $errors = Validator::validate($data, [
                'belt_id' => 'required|numeric'
            ]);
            if (!empty($errors)) {
                return Response::error($errors, 422);
            }
            $result = $this->belt->deleteBelt($data['belt_id']);
            if (isset($result['error'])) {
                return Response::error($result['message'], 404); // Belt not found or error
            }
            return Response::success("Belt deleted successfully", ['id' => $data['belt_id']]);
        } catch (Exception $e) {
            return Response::error("Server error: " . $e->getMessage(), 500);
        }
    }


// ✅ Add Topic ***********************************************************************

    public function saveTopic() {
    try {
        $this->authorizeSuperAdmin();
        $data = Request::all();
        $id = $data['id'] ?? null;

        $errors = Validator::validate($data, [
            'topic_name' => 'required',
            'is_common' => 'numeric'
        ]);
        if (!empty($errors)) return Response::error($errors, 422);

        $result = $this->belt->saveTopic($data, $id);

        if (isset($result['error'])) {
            return Response::error($result['message'], 409);
        }

        if ($id) {
            Response::success("Topic updated successfully", ['id' => $id]);
        } else {
            Response::success("Topic added successfully", ['id' => $result['id']]);
        }

    } catch (Exception $e) {
        Response::error("Server error: " . $e->getMessage(), 500);
    }
}


// ✅ Delete Topic *************************************************************
    public function deleteTopic() {
        try {
            $this->authorizeSuperAdmin();
            $data = Request::all();
            $errors = Validator::validate($data, [
                'TopicId' => 'required|numeric'
            ]);
            if (!empty($errors)) {
                return Response::error($errors, 422);
            }
            $result = $this->belt->deleteTopic($data['TopicId']);
            if (isset($result['error'])) {
                return Response::error($result['message'], 404); // Not found or error
            }
            // return Response::success("Topic deleted successfully", ['TopicId' => $data['id']]);
            return Response::success("Topic deleted successfully", ['TopicId' => $data['TopicId']]);

        } catch (Exception $e) {
            return Response::error("Server error: " . $e->getMessage(), 500);
        }
    }


 // ✅ Map Topics to Belt  //////////////////////////////////////////////////////////
        public function mapTopicsToBelt() {
            try {
                $this->authorizeSuperAdmin();
                $data = Request::all();

                // ✅ Basic structural validation
                if (empty($data['belt_id']) || empty($data['topic_ids']) || !is_array($data['topic_ids'])) {
                    return Response::error("belt_id and topic_ids[] (as array) are required", 422);
                }

                // ✅ Validate belt_id as numeric
                $errors = Validator::validate(['belt_id' => $data['belt_id']], [
                    'belt_id' => 'required|numeric'
                ]);
                if (!empty($errors)) {
                    return Response::error($errors, 422);
                }

                // ✅ Validate each topic_id is numeric
                foreach ($data['topic_ids'] as $tid) {
                    if (!is_numeric($tid)) {
                        return Response::error("All topic_ids must be numeric", 422);
                    }
                }

                // ✅ Call the model function
                $this->belt->mapTopics($data['belt_id'], $data['topic_ids']);
                return Response::success("Topics successfully mapped to belt");
        } catch (Exception $e) {
            return Response::error("Server error: " . $e->getMessage(), 500);
        }
    }


// ✅ Get Belt + Syllabus (Public) /////////////////////////////////////////////////////////////
    public function getBeltSyllabus() {
        try {
            $data = Request::all();
            $errors = Validator::validate($data, [
                'beltId' => 'required|numeric'
            ]);
            if (!empty($errors)) {
                return Response::error($errors, 422);
            }
            $result = $this->belt->getBeltSyllabus($data['beltId']);
            Response::success("Syllabus fetched successfully", $result);
        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage(), 500);
        }
    }

// ✅ Get All Belts (Public) ****************************************************88
    public function getAllBelts() {
        try {
            $belts = $this->belt->getAllBelts();
            Response::success("All belts fetched", $belts);
        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage(), 500);
        }
    }


     public function getAllTopic() {
        try {
            $this->authorizeSuperAdmin();
            $belts = $this->belt->getAllTopics();
            Response::success("All Syllbus Topic fetched", $belts);
        } catch (Exception $e) {
            Response::error("Server error: " . $e->getMessage(), 500);
        }
    }

///////////////////////////////////////////////////////////////////////////
  public function getUnassignedPrivateTopicsByBelt() {
    try {
        $this->authorizeSuperAdmin();
        $data = Request::all();

        if (empty($data['belt_id'])) {
            return Response::error("belt_id is required", 422);
        }

        $beltId = $data['belt_id'];

        // ✅ Step 1: Get all Private Topics (is_common = 0)
        $privateTopics = $this->belt->getPrivateTopics();  // Example: Topic IDs = [1,2,3,4,5]

        // ✅ Step 2: Get Already Assigned Topic IDs from belt_syllabus_map
        $assignedTopicIds = $this->belt->getBeltSyllabusTopicIds($beltId);  // Example: [2,3]

        // ✅ Step 3: Filter -> Only those topics which are private AND not assigned to this belt
        $unassignedTopics = array_filter($privateTopics, function ($topic) use ($assignedTopicIds) {
            return !in_array($topic['id'], $assignedTopicIds);
        });

        // ✅ Step 4: Return JSON
        return Response::success("Unassigned private topics fetched", array_values($unassignedTopics));

    } catch (Exception $e) {
        return Response::error("Server error: " . $e->getMessage(), 500);
    }
}



///////////////////////////////////////////////////////////////////////////////////////


}
