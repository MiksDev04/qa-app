<?php
/**
 * Survey API - CRUD operations for surveys
 * Handles survey creation, reading, updating, deletion
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once '../config/database.php';

session_start();

// Auth guard
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}


class SurveyAPI {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->handleGet();
                break;
            case 'POST':
                $this->handlePost();
                break;
            case 'DELETE':
                $this->handleDelete();
                break;
            default:
                jsonResponse(false, 'Method not allowed', [], 405);
        }
    }
    
    private function handleGet() {
        $action = $_GET['action'] ?? '';
        $surveyId = $_GET['id'] ?? null;
        $token = $_GET['token'] ?? null;
        
        if ($action === 'get_public' && $token) {
            $this->getPublicSurvey($token);
        } elseif ($action === 'get' && $surveyId) {
            $this->getSurvey($surveyId);
        } elseif ($action === 'list') {
            $search = $_GET['search'] ?? '';
            $this->listSurveys($search);
        } else {
            jsonResponse(false, 'Invalid action', [], 400);
        }
    }
    
    private function getPublicSurvey($token) {
        // Check if token is survey_id or actual token
        $surveyId = is_numeric($token) ? (int)$token : null;
        
        if ($surveyId) {
            $sql = "SELECT s.*, u.full_name as creator_name 
                    FROM qa_surveys s 
                    LEFT JOIN qa_users u ON s.created_by = u.user_id 
                    WHERE s.survey_id = ? AND s.status = 'Active'";
            $survey = dbFetchOne($sql, 'i', [$surveyId]);
        } else {
            $sql = "SELECT s.*, u.full_name as creator_name 
                    FROM qa_surveys s 
                    LEFT JOIN qa_users u ON s.created_by = u.user_id 
                    WHERE s.qr_token = ? AND s.status = 'Active'";
            $survey = dbFetchOne($sql, 's', [$token]);
        }
        
        if (!$survey) {
            jsonResponse(false, 'Survey not found or inactive', [], 404);
        }
        
        // Get questions
        $questions = dbFetchAll("SELECT * FROM qa_survey_questions WHERE survey_id = ? ORDER BY sort_order", 'i', [$survey['survey_id']]);
        
        // Get options for each question
        foreach ($questions as &$question) {
            $options = dbFetchAll("SELECT * FROM qa_question_options WHERE question_id = ? ORDER BY sort_order", 'i', [$question['question_id']]);
            $question['options'] = $options;
        }
        
        $survey['questions'] = $questions;
        
        jsonResponse(true, 'Survey loaded successfully', ['data' => $survey]);
    }
    
    private function getSurvey($surveyId) {
        $sql = "SELECT * FROM qa_surveys WHERE survey_id = ?";
        $survey = dbFetchOne($sql, 'i', [$surveyId]);
        
        if (!$survey) {
            jsonResponse(false, 'Survey not found', [], 404);
        }
        
        // Get questions
        $questions = dbFetchAll("SELECT * FROM qa_survey_questions WHERE survey_id = ? ORDER BY sort_order", 'i', [$surveyId]);
        
        // Get options for each question
        foreach ($questions as &$question) {
            $options = dbFetchAll("SELECT * FROM qa_question_options WHERE question_id = ? ORDER BY sort_order", 'i', [$question['question_id']]);
            $question['options'] = $options;
        }
        
        $survey['questions'] = $questions;
        
        // Get response count
        $result = dbFetchOne("SELECT COUNT(DISTINCT respondent_id) as count FROM qa_survey_respondents WHERE survey_id = ?", 'i', [$surveyId]);
        $survey['responses_count'] = $result['count'] ?? 0;
        
        jsonResponse(true, 'Survey loaded successfully', ['data' => $survey]);
    }
    
    private function listSurveys($search = '') {
        $sql = "SELECT s.*, 
                (SELECT COUNT(*) FROM qa_survey_questions WHERE survey_id = s.survey_id) as questions_count,
                (SELECT COUNT(DISTINCT respondent_id) FROM qa_survey_respondents WHERE survey_id = s.survey_id) as responses_count
                FROM qa_surveys s";
        
        if ($search) {
            $sql .= " WHERE s.title LIKE '%" . $this->conn->real_escape_string($search) . "%'";
        }
        
        $sql .= " ORDER BY s.created_by DESC";
        
        $surveys = dbFetchAll($sql);
        
        jsonResponse(true, 'Surveys loaded successfully', ['data' => $surveys]);
    }
    
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            jsonResponse(false, 'Invalid input data', [], 400);
        }
        
        $surveyId = $input['survey_id'] ?? null;
        
        if ($surveyId) {
            $this->updateSurvey($input);
        } else {
            $this->createSurvey($input);
        }
    }
    
    private function createSurvey($data) {
        // Validate required fields
        $errors = validateRequired(['title', 'target_group'], $data);
        if (!empty($errors)) {
            jsonResponse(false, 'Validation failed', ['errors' => $errors], 400);
        }
        
        // Generate unique token
        $token = bin2hex(random_bytes(16));
        
        // Start transaction
        dbBegin();
        
        try {
            $sql = "INSERT INTO qa_surveys (title, description, target_group, start_date, end_date, status, created_by, qr_token) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $createdBy = $_SESSION['user_id'] ?? 1; // Default to admin if not set
            $result = dbExecute($sql, 'ssssssis', [
                $data['title'],
                $data['description'] ?? null,
                $data['target_group'],
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['status'] ?? 'Draft',
                $createdBy,
                $token
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create survey');
            }
            
            $surveyId = $this->conn->insert_id;
            
            // Save questions
            if (isset($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $index => $question) {
                    $this->saveQuestion($surveyId, $question, $index);
                }
            }
            
            dbCommit();
            jsonResponse(true, 'Survey created successfully', ['survey_id' => $surveyId]);
            
        } catch (Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to create survey: ' . $e->getMessage(), [], 500);
        }
    }
    
    private function updateSurvey($data) {
        $surveyId = $data['survey_id'];
        
        // Check if survey exists
        $existing = dbFetchOne("SELECT * FROM qa_surveys WHERE survey_id = ?", 'i', [$surveyId]);
        if (!$existing) {
            jsonResponse(false, 'Survey not found', [], 404);
        }
        
        dbBegin();
        
        try {
            $sql = "UPDATE qa_surveys SET 
                    title = ?, description = ?, target_group = ?, 
                    start_date = ?, end_date = ?, status = ? 
                    WHERE survey_id = ?";
            
            $result = dbExecute($sql, 'ssssssi', [
                $data['title'],
                $data['description'] ?? null,
                $data['target_group'],
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['status'] ?? 'Draft',
                $surveyId
            ]);
            
            if ($result === false) {
                throw new Exception('Failed to update survey');
            }
            
            // Delete existing questions if we're replacing them
            if (isset($data['questions'])) {
                // Get existing question IDs
                $existingQuestions = dbFetchAll("SELECT question_id FROM qa_survey_questions WHERE survey_id = ?", 'i', [$surveyId]);
                $existingIds = array_column($existingQuestions, 'question_id');
                
                $newIds = [];
                foreach ($data['questions'] as $index => $question) {
                    if (isset($question['question_id']) && $question['question_id']) {
                        $this->updateQuestion($surveyId, $question, $index);
                        $newIds[] = $question['question_id'];
                    } else {
                        $newId = $this->saveQuestion($surveyId, $question, $index);
                        if ($newId) $newIds[] = $newId;
                    }
                }
                
                // Delete removed questions
                $toDelete = array_diff($existingIds, $newIds);
                foreach ($toDelete as $qid) {
                    // Delete options first
                    dbExecute("DELETE FROM qa_question_options WHERE question_id = ?", 'i', [$qid]);
                    dbExecute("DELETE FROM qa_survey_questions WHERE question_id = ?", 'i', [$qid]);
                }
            }
            
            dbCommit();
            jsonResponse(true, 'Survey updated successfully');
            
        } catch (Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to update survey: ' . $e->getMessage(), [], 500);
        }
    }
    
    private function saveQuestion($surveyId, $question, $index) {
        $sql = "INSERT INTO qa_survey_questions (survey_id, question_text, question_type, is_required, sort_order) 
                VALUES (?, ?, ?, ?, ?)";
        
        $isRequired = isset($question['is_required']) ? (int)$question['is_required'] : 1;
        $result = dbExecute($sql, 'issii', [
            $surveyId,
            $question['question_text'],
            $question['question_type'],
            $isRequired,
            $index
        ]);
        
        if (!$result) return false;
        
        $questionId = $this->conn->insert_id;
        
        // Save options
        if (isset($question['options']) && is_array($question['options']) && 
            ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'checkbox')) {
            foreach ($question['options'] as $optIndex => $optionText) {
                if (trim($optionText)) {
                    dbExecute("INSERT INTO qa_question_options (question_id, option_text, sort_order) VALUES (?, ?, ?)", 
                             'isi', [$questionId, trim($optionText), $optIndex]);
                }
            }
        }
        
        return $questionId;
    }
    
    private function updateQuestion($surveyId, $question, $index) {
        $questionId = $question['question_id'];
        
        $sql = "UPDATE qa_survey_questions SET 
                question_text = ?, question_type = ?, is_required = ?, sort_order = ? 
                WHERE question_id = ? AND survey_id = ?";
        
        $isRequired = isset($question['is_required']) ? (int)$question['is_required'] : 1;
        dbExecute($sql, 'ssiiii', [
            $question['question_text'],
            $question['question_type'],
            $isRequired,
            $index,
            $questionId,
            $surveyId
        ]);
        
        // Update options
        if (isset($question['options']) && is_array($question['options']) && 
            ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'checkbox')) {
            
            // Delete existing options
            dbExecute("DELETE FROM qa_question_options WHERE question_id = ?", 'i', [$questionId]);
            
            // Insert new options
            foreach ($question['options'] as $optIndex => $optionText) {
                if (trim($optionText)) {
                    dbExecute("INSERT INTO qa_question_options (question_id, option_text, sort_order) VALUES (?, ?, ?)", 
                             'isi', [$questionId, trim($optionText), $optIndex]);
                }
            }
        }
    }
    
    private function handleDelete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $surveyId = $input['survey_id'] ?? null;
        
        if (!$surveyId) {
            jsonResponse(false, 'Survey ID is required', [], 400);
        }
        
        // Check if survey has responses
        $responses = dbFetchOne("SELECT COUNT(*) as count FROM qa_survey_respondents WHERE survey_id = ?", 'i', [$surveyId]);
        if ($responses['count'] > 0) {
            jsonResponse(false, 'Cannot delete survey with existing responses', [], 400);
        }
        
        dbBegin();
        
        try {
            // Delete questions and options
            $questions = dbFetchAll("SELECT question_id FROM qa_survey_questions WHERE survey_id = ?", 'i', [$surveyId]);
            foreach ($questions as $q) {
                dbExecute("DELETE FROM qa_question_options WHERE question_id = ?", 'i', [$q['question_id']]);
            }
            dbExecute("DELETE FROM qa_survey_questions WHERE survey_id = ?", 'i', [$surveyId]);
            
            // Delete survey
            $result = dbExecute("DELETE FROM qa_surveys WHERE survey_id = ?", 'i', [$surveyId]);
            
            if ($result === false) {
                throw new Exception('Failed to delete survey');
            }
            
            dbCommit();
            jsonResponse(true, 'Survey deleted successfully');
            
        } catch (Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to delete survey: ' . $e->getMessage(), [], 500);
        }
    }
}

$api = new SurveyAPI();
$api->handleRequest();
?>