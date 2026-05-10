<?php
/**
 * Survey Response API - Handle survey submissions and response viewing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $this->handleGet();    break;
            case 'POST':   $this->handlePost();   break;
            case 'DELETE': $this->handleDelete(); break;
            default:       jsonResponse(false, 'Method not allowed', [], 405);
        }
    }

    /* ─── GET router ────────────────────────────────── */
    private function handleGet() {
        $action       = $_GET['action']        ?? '';
        $surveyId     = $_GET['survey_id']     ?? null;
        $respondentId = $_GET['respondent_id'] ?? null;

        if ($action === 'get_responses' && $surveyId) {
            $this->getSurveyResponses($surveyId);
        } elseif ($action === 'get_all_responses') {
            $this->getAllSurveyResponses();
        } elseif ($action === 'get_respondent_answers' && $respondentId) {
            $this->getRespondentAnswers((int) $respondentId);
        } else {
            jsonResponse(false, 'Invalid action', [], 400);
        }
    }

    /* ─── DELETE router ─────────────────────────────── */
    private function handleDelete() {
        $input        = json_decode(file_get_contents('php://input'), true);
        $respondentId = isset($input['respondent_id']) ? (int) $input['respondent_id'] : 0;

        if (!$respondentId) {
            jsonResponse(false, 'respondent_id is required', [], 400);
        }

        $respondent = dbFetchOne(
            "SELECT respondent_id FROM qa_survey_respondents WHERE respondent_id = ?",
            'i',
            [$respondentId]
        );

        if (!$respondent) {
            jsonResponse(false, 'Response not found', [], 404);
        }

        dbBegin();
        try {
            // Delete answers first (FK constraint)
            dbExecute(
                "DELETE FROM qa_survey_answers WHERE respondent_id = ?",
                'i',
                [$respondentId]
            );

            $result = dbExecute(
                "DELETE FROM qa_survey_respondents WHERE respondent_id = ?",
                'i',
                [$respondentId]
            );

            if ($result === false) {
                throw new Exception('Failed to delete response record');
            }

            dbCommit();
            jsonResponse(true, 'Response deleted successfully');
        } catch (Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to delete response: ' . $e->getMessage(), [], 500);
        }
    }

    /* ─── Fetch one respondent's answers ────────────── */
    private function getRespondentAnswers(int $respondentId) {
        $respondent = dbFetchOne(
            "SELECT respondent_id, survey_id, respondent_role, student_id, employee_id, submitted_at
             FROM qa_survey_respondents
             WHERE respondent_id = ?",
            'i',
            [$respondentId]
        );

        if (!$respondent) {
            jsonResponse(false, 'Respondent not found', [], 404);
            return;
        }

        $answers = dbFetchAll(
            "SELECT a.question_id,
                    a.option_id,
                    a.rating_value,
                    a.text_answer,
                    q.question_type
             FROM qa_survey_answers a
             LEFT JOIN qa_survey_questions q ON a.question_id = q.question_id
             WHERE a.respondent_id = ?
             ORDER BY q.sort_order, a.answer_id",
            'i',
            [$respondentId]
        );

        jsonResponse(true, 'Respondent answers loaded', [
            'data' => [
                'respondent' => $respondent,
                'answers'    => $answers,
            ]
        ]);
    }

    /* ─── Build full survey + all respondents payload ─ */
    private function buildSurveyResponsePayload($surveyId) {
        $survey = dbFetchOne(
            "SELECT survey_id, title, target_group, status, start_date, end_date
             FROM qa_surveys
             WHERE survey_id = ?",
            'i',
            [$surveyId]
        );

        if (!$survey) return null;

        $questions = dbFetchAll(
            "SELECT question_id, question_text, question_type, is_required, sort_order
             FROM qa_survey_questions
             WHERE survey_id = ?
             ORDER BY sort_order",
            'i',
            [$surveyId]
        );

        $questionMap = [];
        foreach ($questions as $q) {
            $questionMap[$q['question_id']] = $q;
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
                 LEFT JOIN qa_question_options  o ON a.option_id  = o.option_id
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

        $survey['questions']       = $questions;
        $survey['responses_count'] = count($respondents);
        $survey['respondents']     = $respondents;

        return $survey;
    }

    private function formatAnswerValue($answer) {
        if ($answer['rating_value'] !== null && $answer['rating_value'] !== '') {
            return (string) $answer['rating_value'];
        }
        if (!empty($answer['option_text'])) return $answer['option_text'];
        if (!empty($answer['text_answer'])) return $answer['text_answer'];
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
            $data = $this->buildSurveyResponsePayload($survey['survey_id']);
            if ($data) $payload[] = $data;
        }

        jsonResponse(true, 'All survey responses loaded successfully', ['data' => $payload]);
    }

    /* ─── POST router ───────────────────────────────── */
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonResponse(false, 'Invalid input data', [], 400);
        }
        $this->submitResponse($input);
    }

    private function submitResponse($data) {
        $surveyId       = $data['survey_id']       ?? null;
        $respondentRole = $data['respondent_role']  ?? null;
        $respondentId   = $data['respondent_id']    ?? null;
        $answers        = $data['answers']           ?? [];

        if (!is_array($answers)) {
            jsonResponse(false, 'Invalid answers payload', [], 400);
        }

        if (!$surveyId || !$respondentRole || empty($answers)) {
            jsonResponse(false, 'Missing required fields', [], 400);
        }

        $survey = dbFetchOne(
            "SELECT * FROM qa_surveys WHERE survey_id = ? AND status = 'Active'",
            'i',
            [$surveyId]
        );
        if (!$survey) {
            jsonResponse(false, 'Survey not found or is no longer active', [], 404);
        }

        dbBegin();

        try {
            $studentId  = ($respondentRole === 'Student')                         ? (int) $respondentId : 0;
            $employeeId = (in_array($respondentRole, ['Faculty', 'Staff'], true)) ? (int) $respondentId : 0;

            $result = dbExecute(
                "INSERT INTO qa_survey_respondents (survey_id, respondent_role, student_id, employee_id)
                 VALUES (?, ?, NULLIF(?,0), NULLIF(?,0))",
                'isii',
                [$surveyId, $respondentRole, $studentId, $employeeId]
            );

            if (!$result) {
                $connErr = $this->conn->error;
                error_log("Respondent insert failed: {$connErr}");
                throw new Exception('Failed to record respondent: ' . ($connErr ?: 'unknown DB error'));
            }

            $respondentDbId = $result;

            foreach ($answers as $answer) {
                if (!is_array($answer) || empty($answer['question_id'])) continue;

                $questionId  = (int) $answer['question_id'];
                $answerValue = $answer['answer']      ?? null;
                $answerType  = $answer['answer_type'] ?? 'text';
                $optionId    = $answer['option_id']   ?? null;

                $question = dbFetchOne(
                    "SELECT question_type, is_required FROM qa_survey_questions WHERE question_id = ?",
                    'i',
                    [$questionId]
                );
                if (!$question) continue;

                if ($answerType === 'rating' || str_starts_with((string) $answerType, 'rating')) {
                    dbExecute(
                        "INSERT INTO qa_survey_answers (respondent_id, question_id, rating_value) VALUES (?, ?, ?)",
                        'iii',
                        [$respondentDbId, $questionId, $answerValue]
                    );
                } elseif ($optionId !== null && in_array($answerType, ['multiple_choice','checkbox','option'], true)) {
                    dbExecute(
                        "INSERT INTO qa_survey_answers (respondent_id, question_id, option_id) VALUES (?, ?, ?)",
                        'iii',
                        [$respondentDbId, $questionId, (int) $optionId]
                    );
                } else {
                    dbExecute(
                        "INSERT INTO qa_survey_answers (respondent_id, question_id, text_answer) VALUES (?, ?, ?)",
                        'iis',
                        [$respondentDbId, $questionId, $answerValue]
                    );
                }
            }

            dbCommit();
            error_log("Survey submission OK: Survey #{$surveyId}, Respondent #{$respondentDbId}");

            jsonResponse(true, 'Survey submitted successfully! Thank you for your feedback.', [
                'data' => ['respondent_id' => $respondentDbId]
            ]);

        } catch (Exception $e) {
            dbRollback();
            error_log("Survey submission failed: " . $e->getMessage());
            jsonResponse(false, 'Failed to submit survey: ' . $e->getMessage(), [], 500);
        }
    }
}

if (session_status() === PHP_SESSION_NONE) session_start();

$api = new SurveyResponseAPI();
$api->handleRequest();
?>