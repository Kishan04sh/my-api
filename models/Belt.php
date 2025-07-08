<?php

require_once __DIR__ . '/../config/database.php';

class Belt
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }


    public function saveBelt($data, $id = null)
        {
            try {
                $belt_name = trim($data['belt_name']);
                $belt_level = (int)$data['belt_level'];
                $fee = (float)$data['fee'];
                $color = trim($data['color']);  // ✅ New Color Field

                // Check duplicate (you can include color if needed)
                $query = "SELECT COUNT(*) FROM exam_fees WHERE belt_name = ? AND fee = ?";
                $params = [$belt_name, $fee];
                if ($id) {
                    $query .= " AND id != ?";
                    $params[] = $id;
                }

                $check = $this->db->prepare($query);
                $check->execute($params);
                if ($check->fetchColumn() > 0) {
                    return ['error' => true, 'message' => 'Duplicate belt exists.'];
                }

                if ($id) {
                    // ✅ Update belt with color
                    $stmt = $this->db->prepare("UPDATE exam_fees SET belt_name = ?, belt_level = ?, fee = ?, color = ? WHERE id = ?");
                    $stmt->execute([$belt_name, $belt_level, $fee, $color, $id]);
                    return ['success' => true, 'id' => $id];
                } else {
                    // ✅ Insert new belt with color
                    $stmt = $this->db->prepare("INSERT INTO exam_fees (belt_name, belt_level, fee, color) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$belt_name, $belt_level, $fee, $color]);
                    return ['success' => true, 'id' => $this->db->lastInsertId()];
                }
            } catch (PDOException $e) {
                return ['error' => true, 'message' => $e->getMessage()];
            }
        }


    // ✅ Delete Belt
    public function deleteBelt($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM exam_fees WHERE id = ?");
            $stmt->execute([(int)$id]);
            return $stmt->rowCount() ? true : ['error' => true, 'message' => 'Belt not found'];
        } catch (PDOException $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    // ✅ Save Topic
    public function saveTopic($data, $id = null)
    {
        try {
            $topic_name = trim($data['topic_name']);
            $is_common = (int)$data['is_common'];

            $query = "SELECT COUNT(*) FROM syllabus_topics WHERE topic_name = ?";
            $params = [$topic_name];
            if ($id) {
                $query .= " AND id != ?";
                $params[] = $id;
            }

            $check = $this->db->prepare($query);
            $check->execute($params);
            if ($check->fetchColumn() > 0) {
                return ['error' => true, 'message' => 'Duplicate topic exists.'];
            }

            if ($id) {
                $stmt = $this->db->prepare("UPDATE syllabus_topics SET topic_name = ?, is_common = ? WHERE id = ?");
                $stmt->execute([$topic_name, $is_common, $id]);
                return true;
            } else {
                $stmt = $this->db->prepare("INSERT INTO syllabus_topics (topic_name, is_common) VALUES (?, ?)");
                $stmt->execute([$topic_name, $is_common]);
                return ['id' => $this->db->lastInsertId()];
            }
        } catch (PDOException $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    // ✅ Delete Topic
    public function deleteTopic($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM syllabus_topics WHERE id = ?");
            $stmt->execute([(int)$id]);
            return $stmt->rowCount() ? true : ['error' => true, 'message' => 'Topic not found'];
        } catch (PDOException $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    // ✅ Map Topics to Belt
    public function mapTopics($beltId, $topicIds)
    {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("DELETE FROM belt_syllabus_map WHERE exam_fee_id = ?")->execute([(int)$beltId]);

            $stmt = $this->db->prepare("INSERT INTO belt_syllabus_map (exam_fee_id, syllabus_topic_id) VALUES (?, ?)");
            foreach ($topicIds as $tid) {
                $stmt->execute([(int)$beltId, (int)$tid]);
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    ////////////////////////////////////////////////// ✅ Get Syllabus for Belt
    public function getBeltSyllabus($beltId)
    {
        try {
            $stmt = $this->db->prepare("SELECT t.id, t.topic_name, t.is_common FROM syllabus_topics t LEFT JOIN belt_syllabus_map m ON t.id = m.syllabus_topic_id WHERE m.exam_fee_id = ? OR t.is_common = 1 GROUP BY t.id");
            $stmt->execute([(int)$beltId]);
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $belt = $this->db->prepare("SELECT belt_name, fee, belt_level FROM exam_fees WHERE id = ?");
            $belt->execute([(int)$beltId]);
            $beltData = $belt->fetch(PDO::FETCH_ASSOC);

            if (!$beltData) return ['error' => true, 'message' => 'Belt not found'];

            return [
                'belt_id' => (int)$beltId,
                'belt_name' => $beltData['belt_name'],
                'belt_level' => $beltData['belt_level'],
                'fee' => (float)$beltData['fee'],
                'syllabus' => $topics
            ];
        } catch (PDOException $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    // ✅ Get All Belts /////////////////////////////////////////////////////////

    public function getAllBelts()
    {
        try {
            $stmt = $this->db->query("SELECT id, belt_name, belt_level, fee, color, created_on FROM exam_fees ORDER BY id ASC, created_on ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function getAllTopics()
    {
        try {
            $stmt = $this->db->query("SELECT id, topic_name, is_common FROM syllabus_topics ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }


// ✅ Get All Private Topics (is_common = 0)
public function getPrivateTopics() {
    try {
        $stmt = $this->db->query("SELECT id, topic_name, is_common FROM syllabus_topics WHERE is_common = 0 ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// ✅ Get Already Assigned Topic IDs for a Belt
public function getBeltSyllabusTopicIds($beltId) {
    try {
        $stmt = $this->db->prepare("SELECT syllabus_topic_id FROM belt_syllabus_map WHERE exam_fee_id = ?");
        $stmt->execute([(int)$beltId]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $result ?: [];
    } catch (PDOException $e) {
        return [];
    }
}


}
