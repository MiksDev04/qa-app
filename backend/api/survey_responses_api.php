<?php
/**
 * Survey Response API - Handle survey submissions and response viewing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

class SurveyResponseAPI {
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
            default:
                jsonResponse(false, 'Method not allowed', [], 405);
        }
    }
    
    private function handleGet() {
        $action = $_GET['action'] ?? '';
        $surveyId = $_GET['survey_id'] ?? null;
        
        if ($action === 'get_responses' && $surveyId) {
            $this->getSurveyResponses($surveyId);
        } else {
            jsonResponse(false, 'Invalid action', [], 400);
        }
    }
    
    private function getSurveyResponses($surveyId) {
        // Get all respondents for this survey
        $respondents = dbFetchAll("SELECT * FROM qa_survey_respondents WHERE survey_id = ? ORDER BY submitted_at DESC", 'i', [$surveyId]);
        
        if (empty($respondents)) {
            jsonResponse(true, 'No responses found', ['data' => []]);
            return;
        }
        
        // Get all questions for this survey
        $questions = dbFetchAll("SELECT question_id, question_text, question_type FROM qa_survey_questions WHERE survey_id = ? ORDER BY sort_order", 'i', [$surveyId]);
        $questionsMap = [];
        foreach ($questions as $q) {
            $questionsMap[$q['question_id']] = $q;
        }
        
        // Get answers for each respondent
        foreach ($respondents as &$respondent) {
            $answers = dbFetchAll("SELECT * FROM qa_survey_answers WHERE respondent_id = ?", 'i', [$respondent['respondent_id']]);
            
            foreach ($answers as &$answer) {
                $answer['question_text'] = $questionsMap[$answer['question_id']]['question_text'] ?? 'Unknown';
                
                // Get option text if option_id exists
                if ($answer['option_id']) {
                    $option = dbFetchOne("SELECT option_text FROM qa_question_options WHERE option_id = ?", 'i', [$answer['option_id']]);
                    if ($option) {
                        $answer['option_text'] = $option['option_text'];
                    }
                }
            }
            
            $respondent['answers'] = $answers;
        }
        
        jsonResponse(true, 'Responses loaded successfully', ['data' => $respondents]);
    }
    
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            jsonResponse(false, 'Invalid input data', [], 400);
        }
        
        $this->submitResponse($input);
    }
    
    private function submitResponse($data) {
        $surveyId = $data['survey_id'] ?? null;
        $respondentRole = $data['respondent_role'] ?? null;
        $respondentId = $data['respondent_id'] ?? null;
        $answers = $data['answers'] ?? [];

        if (!is_array($answers)) {
            jsonResponse(false, 'Invalid answers payload', [], 400);
        }
        
        // Validate required fields
        if (!$surveyId || !$respondentRole || empty($answers)) {
            jsonResponse(false, 'Missing required fields', [], 400);
        }
        
        // Check if survey exists and is active
        $survey = dbFetchOne("SELECT * FROM qa_surveys WHERE survey_id = ? AND status = 'Active'", 'i', [$surveyId]);
        if (!$survey) {
            jsonResponse(false, 'Survey not found or is no longer active', [], 404);
        }
        
        // No duplicate-submission check here (schema lacks IP/session columns)
        
        dbBegin();
        
        try {
            // Insert respondent (match actual schema: no IP/session columns)
            $sql = "INSERT INTO qa_survey_respondents (survey_id, respondent_role, student_id, employee_id)
                    VALUES (?, ?, NULLIF(?,0), NULLIF(?,0))";

            $studentId = ($respondentRole === 'Student') ? (int)$respondentId : 0;
            $employeeId = (in_array($respondentRole, ['Faculty', 'Staff'])) ? (int)$respondentId : 0;

            $result = dbExecute($sql, 'isii', [
                $surveyId,
                $respondentRole,
                $studentId,
                $employeeId
            ]);

            if (!$result) {
                $connErr = $this->conn->error;
                $connErrno = $this->conn->errno;
                error_log("Respondent insert failed: connErr={$connErr} | errno={$connErrno} | surveyId={$surveyId} | role={$respondentRole}");
                throw new Exception('Failed to record respondent: ' . ($connErr ?: 'unknown DB error'));
            }

            $respondentDbId = $result;
            
            // Insert answers
            foreach ($answers as $answer) {
                if (!is_array($answer) || empty($answer['question_id'])) {
                    continue;
                }

                $questionId = (int) $answer['question_id'];
                $answerValue = $answer['answer'] ?? null;
                $answerType = $answer['answer_type'] ?? 'text';
                $optionId = $answer['option_id'] ?? null;
                
                // Get question to determine type
                $question = dbFetchOne("SELECT question_type, is_required FROM qa_survey_questions WHERE question_id = ?", 'i', [$questionId]);
                if (!$question) continue;
                
                if ($answerType === 'rating' || str_starts_with((string) $answerType, 'rating')) {
                    dbExecute("INSERT INTO qa_survey_answers (respondent_id, question_id, rating_value) VALUES (?, ?, ?)", 
                             'iii', [$respondentDbId, $questionId, $answerValue]);
                } elseif ($optionId !== null && in_array($answerType, ['multiple_choice', 'checkbox', 'option'], true)) {
                    dbExecute("INSERT INTO qa_survey_answers (respondent_id, question_id, option_id) VALUES (?, ?, ?)", 
                             'iii', [$respondentDbId, $questionId, (int) $optionId]);
                } else {
                    dbExecute("INSERT INTO qa_survey_answers (respondent_id, question_id, text_answer) VALUES (?, ?, ?)", 
                             'iis', [$respondentDbId, $questionId, $answerValue]);
                }
            }
            
            dbCommit();
            
            // Log successful submission
            error_log("Survey submission successful: Survey ID $surveyId, Respondent ID $respondentDbId");
            
            jsonResponse(true, 'Survey submitted successfully! Thank you for your feedback.');
            
        } catch (Exception $e) {
            dbRollback();
            error_log("Survey submission failed: " . $e->getMessage());
            jsonResponse(false, 'Failed to submit survey: ' . $e->getMessage(), [], 500);
        }
    }
}

// Start session for tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$api = new SurveyResponseAPI();
$api->handleRequest();
?>