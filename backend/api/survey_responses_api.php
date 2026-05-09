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
        } elseif ($action === 'get_all_responses') {
            $this->getAllSurveyResponses();
        } else {
            jsonResponse(false, 'Invalid action', [], 400);
        }
    }

    private function buildSurveyResponsePayload($surveyId) {
        $survey = dbFetchOne(
            "SELECT survey_id, title, target_group, status, start_date, end_date
             FROM qa_surveys
             WHERE survey_id = ?",
            'i',
            [$surveyId]
        );

        if (!$survey) {
            return null;
        }

        $questions = dbFetchAll(
            "SELECT question_id, question_text, question_type, is_required, sort_order
             FROM qa_survey_questions
             WHERE survey_id = ?
             ORDER BY sort_order",
            'i',
            [$surveyId]
        );

        $questionMap = [];
        foreach ($questions as $question) {
            $questionMap[$question['question_id']] = $question;
        }

        $respondents = dbFetchAll(
            "SELECT respondent_id, respondent_role, student_id, employee_id, submitted_at
             FROM qa_survey_respondents
             WHERE survey_id = ?
             ORDER BY submitted_at ASC",
            'i',
            [$surveyId]
        );

        foreach ($respondents as &$respondent) {
            $answers = dbFetchAll(
                "SELECT a.question_id,
                        a.option_id,
                        a.rating_value,
                        a.text_answer,
                        q.question_text,
                        q.question_type,
                        o.option_text
                 FROM qa_survey_answers a
                 LEFT JOIN qa_survey_questions q ON a.question_id = q.question_id
                 LEFT JOIN qa_question_options o ON a.option_id = o.option_id
                 WHERE a.respondent_id = ?
                 ORDER BY q.sort_order, a.answer_id",
                'i',
                [$respondent['respondent_id']]
            );

            foreach ($answers as &$answer) {
                if (!$answer['question_text'] && isset($questionMap[$answer['question_id']])) {
                    $answer['question_text'] = $questionMap[$answer['question_id']]['question_text'];
                    $answer['question_type'] = $questionMap[$answer['question_id']]['question_type'];
                }

                $answer['display_value'] = $this->formatAnswerValue($answer);
            }

            $respondent['answers'] = $answers;
        }

        $survey['questions'] = $questions;
        $survey['responses_count'] = count($respondents);
        $survey['respondents'] = $respondents;

        return $survey;
    }

    private function formatAnswerValue($answer) {
        if ($answer['rating_value'] !== null && $answer['rating_value'] !== '') {
            return (string) $answer['rating_value'];
        }

        if (!empty($answer['option_text'])) {
            return $answer['option_text'];
        }

        if (!empty($answer['text_answer'])) {
            return $answer['text_answer'];
        }

        return '-';
    }
    
    private function getSurveyResponses($surveyId) {
        $survey = $this->buildSurveyResponsePayload($surveyId);

        if (!$survey) {
            jsonResponse(false, 'Survey not found', [], 404);
        }

        jsonResponse(true, 'Responses loaded successfully', ['data' => $survey]);
    }

    private function getAllSurveyResponses() {
        $surveys = dbFetchAll(
            "SELECT survey_id, title, target_group, status, start_date, end_date
             FROM qa_surveys
             ORDER BY survey_id DESC"
        );

        $payload = [];
        foreach ($surveys as $survey) {
            $surveyData = $this->buildSurveyResponsePayload($survey['survey_id']);
            if ($surveyData) {
                $payload[] = $surveyData;
            }
        }

        jsonResponse(true, 'All survey responses loaded successfully', ['data' => $payload]);
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