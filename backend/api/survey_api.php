<?php

/**
 * Survey API – CRUD operations for surveys
 * backend/api/survey_api.php
 *
 * Status lifecycle rules enforced here:
 *  • Users may only choose Draft or Active manually.
 *  • Closed is set automatically when end_date < today.
 *  • Active is blocked if today is outside [start_date, end_date].
 *  • end_date must not be earlier than start_date.
 *  • created_at / updated_at are maintained automatically.
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

if (empty($_SESSION['logged_in'])) {
    jsonResponse(false, 'Unauthorized', [], 401);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────

class SurveyAPI
{
    private $conn;

    public function __construct()
    {
        $this->conn = getDBConnection();
    }

    // ── Router ────────────────────────────────────────────────────────────────

    public function handleRequest(): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
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

    private function handleGet(): void
    {
        $action   = $_GET['action'] ?? '';
        $surveyId = $_GET['id']     ?? null;
        $token    = $_GET['token']  ?? null;

        if ($action === 'get_public' && $token) {
            $this->getPublicSurvey($token);
        } elseif ($action === 'get' && $surveyId) {
            $this->getSurvey((int)$surveyId);
        } elseif ($action === 'list') {
            $this->listSurveys($_GET['search'] ?? '');
        } else {
            jsonResponse(false, 'Invalid action', [], 400);
        }
    }

    private function handlePost(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            jsonResponse(false, 'Invalid JSON input', [], 400);
        }

        if (($data['action'] ?? '') === 'update') {
            $this->updateSurvey($data);
        } else {
            $this->createSurvey($data);
        }
    }

    private function handleDelete(): void
    {
        $input        = json_decode(file_get_contents('php://input'), true);
        $surveyId     = $input['survey_id']     ?? null;
        $respondentId = $input['respondent_id'] ?? null;

        if ($respondentId) {
            $this->deleteRespondent((int)$respondentId);
        } elseif ($surveyId) {
            $this->deleteSurvey((int)$surveyId);
        } else {
            jsonResponse(false, 'survey_id or respondent_id is required', [], 400);
        }
    }

    // ── Auto-close helper ─────────────────────────────────────────────────────

    /**
     * Belt-and-suspenders: closes any Active survey whose end_date < today.
     * The MySQL event does this hourly; this call covers the gap.
     */
    private function autoCloseExpiredSurveys(): void
    {
        dbExecute(
            "UPDATE qa_surveys
                SET status     = 'Closed',
                    updated_at = NOW()
              WHERE status     = 'Active'
                AND end_date  IS NOT NULL
                AND end_date   < CURDATE()"
        );
    }

    // ── Validation helpers ────────────────────────────────────────────────────

    /**
     * Returns an error string if the status value is invalid, null otherwise.
     * 'Closed' cannot be set manually; the system owns that transition.
     */
    private function validateStatus(string $status, ?string $existingStatus = null): ?string
    {
        // Allow keeping Closed on an already-Closed survey (e.g. editing title/questions).
        // Closed cannot be chosen on a fresh create or forced onto an open survey.
        if ($status === 'Closed' && $existingStatus === 'Closed') {
            return null;
        }

        if (!in_array($status, ['Draft', 'Active'], true)) {
            return '"Closed" status is managed automatically by the system. '
                . 'Please choose Draft or Active.';
        }
        return null;
    }

    /**
     * Returns an error string if end_date precedes start_date, null otherwise.
     */
    private function validateDateOrder(?string $startDate, ?string $endDate): ?string
    {
        if ($startDate && $endDate && $endDate < $startDate) {
            return 'End date cannot be earlier than start date.';
        }
        return null;
    }

    /**
     * Returns an error string if the date window is incompatible with Active,
     * null otherwise.
     *
     * Rules:
     *  – Cannot activate if today > end_date (survey has already expired).
     *  – Cannot activate if today < start_date (survey has not started yet).
     */
    private function validateActivationWindow(
        string  $status,
        ?string $startDate,
        ?string $endDate
    ): ?string {
        if ($status !== 'Active') {
            return null;
        }

        $today = date('Y-m-d');

        if ($endDate && $today > $endDate) {
            return 'Cannot activate this survey: its end date ('
                . $endDate
                . ') has already passed. The system will mark it as Closed.';
        }

        if ($startDate && $today < $startDate) {
            return 'Cannot activate this survey before its start date ('
                . $startDate
                . '). Save it as Draft and activate it on or after that date.';
        }

        return null;
    }

    /**
     * Runs all three validations and calls jsonResponse on the first error.
     * Returns false if validation failed (caller should stop), true if clean.
     */
    private function runSurveyValidation(
        string  $status,
        ?string $startDate,
        ?string $endDate,
        ?string $existingStatus = null   // ← add this
    ): bool {
        $checks = [
            $this->validateStatus($status, $existingStatus),   // ← pass it here
            $this->validateDateOrder($startDate, $endDate),
            $this->validateActivationWindow($status, $startDate, $endDate),
        ];

        foreach ($checks as $error) {
            if ($error !== null) {
                jsonResponse(false, $error, [], 422);
                return false;
            }
        }
        return true;
    }

    // ── Read operations ───────────────────────────────────────────────────────

    private function getPublicSurvey(string $token): void
    {
        // Token is either a numeric survey_id (from the admin link/QR shortcut)
        // or the hex qr_token stored in the database.
        if (is_numeric($token)) {
            $survey = dbFetchOne(
                "SELECT s.*, u.full_name AS creator_name
                   FROM qa_surveys s
                   LEFT JOIN qa_users u ON s.created_by = u.user_id
                  WHERE s.survey_id = ?",   // ← no status filter
                'i',
                [(int)$token]
            );
        } else {
            $survey = dbFetchOne(
                "SELECT s.*, u.full_name AS creator_name
                   FROM qa_surveys s
                   LEFT JOIN qa_users u ON s.created_by = u.user_id
                  WHERE s.qr_token = ?",    // ← no status filter
                's',
                [$token]
            );
        }
 
        if (!$survey) {
            jsonResponse(false, 'Survey not found.', [], 404);
            return;
        }
 
        // Always attach questions so the frontend has them if it needs them.
        $survey['questions'] = $this->fetchQuestionsWithOptions($survey['survey_id']);
 
        jsonResponse(true, 'Survey loaded successfully', ['data' => $survey]);
    }

    private function getSurvey(int $surveyId): void
    {
        $this->autoCloseExpiredSurveys();

        $survey = dbFetchOne(
            "SELECT * FROM qa_surveys WHERE survey_id = ?",
            'i',
            [$surveyId]
        );

        if (!$survey) {
            jsonResponse(false, 'Survey not found', [], 404);
            return;
        }

        $survey['questions']      = $this->fetchQuestionsWithOptions($surveyId);
        $survey['responses_count'] = $this->getResponseCount($surveyId);

        jsonResponse(true, 'Survey loaded successfully', ['data' => $survey]);
    }

    private function listSurveys(string $search = ''): void
    {
        $this->autoCloseExpiredSurveys();

        $sql = "SELECT s.*,
                       (SELECT COUNT(*)
                          FROM qa_survey_questions
                         WHERE survey_id = s.survey_id) AS questions_count,
                       (SELECT COUNT(DISTINCT respondent_id)
                          FROM qa_survey_respondents
                         WHERE survey_id = s.survey_id) AS responses_count
                  FROM qa_surveys s";

        if ($search !== '') {
            $safe  = $this->conn->real_escape_string($search);
            $sql  .= " WHERE s.title LIKE '%{$safe}%'";
        }

        $sql .= " ORDER BY s.survey_id DESC";

        jsonResponse(true, 'Surveys loaded successfully', ['data' => dbFetchAll($sql)]);
    }

    // ── Write operations ──────────────────────────────────────────────────────

    private function createSurvey(array $data): void
    {
        $errors = validateRequired(['title', 'target_group'], $data);
        if (!empty($errors)) {
            jsonResponse(false, 'Validation failed', ['errors' => $errors], 400);
            return;
        }

        $status    = $data['status']     ?? 'Draft';
        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $endDate   = !empty($data['end_date'])   ? $data['end_date']   : null;

        if (!$this->runSurveyValidation($status, $startDate, $endDate)) {
            return;
        }

        $token = bin2hex(random_bytes(16));

        dbBegin();
        try {
            $createdBy = $_SESSION['user_id'] ?? 1;

            $result = dbExecute(
                "INSERT INTO qa_surveys
                        (title, description, target_group,
                         start_date, end_date, status,
                         created_by, qr_token,
                         created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                'ssssssis',
                [
                    $data['title'],
                    $data['description']  ?? null,
                    $data['target_group'],
                    $startDate,
                    $endDate,
                    $status,
                    $createdBy,
                    $token,
                ]
            );

            if (!$result) {
                throw new \Exception('Insert failed');
            }

            $surveyId = $this->conn->insert_id;

            if (!empty($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $idx => $q) {
                    $this->saveQuestion($surveyId, $q, $idx);
                }
            }

            dbCommit();
            jsonResponse(true, 'Survey created successfully', ['survey_id' => $surveyId]);
        } catch (\Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to create survey: ' . $e->getMessage(), [], 500);
        }
    }

    private function updateSurvey(array $data): void
    {
        $surveyId = (int)($data['survey_id'] ?? 0);

        if (!$surveyId || !dbFetchOne("SELECT survey_id FROM qa_surveys WHERE survey_id = ?", 'i', [$surveyId])) {
            jsonResponse(false, 'Survey not found', [], 404);
            return;
        }

        $status    = $data['status']     ?? 'Draft';
        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $endDate   = !empty($data['end_date'])   ? $data['end_date']   : null;

        // AFTER – fetch existing status first, then validate
        $existing   = dbFetchOne("SELECT status FROM qa_surveys WHERE survey_id = ?", 'i', [$surveyId]);
        $existingStatus = $existing['status'] ?? null;

        if (!$this->runSurveyValidation($status, $startDate, $endDate, $existingStatus)) {
            return;
        }
        dbBegin();
        try {
            $result = dbExecute(
                "UPDATE qa_surveys
                    SET title        = ?,
                        description  = ?,
                        target_group = ?,
                        start_date   = ?,
                        end_date     = ?,
                        status       = ?,
                        updated_at   = NOW()
                  WHERE survey_id    = ?",
                'ssssssi',
                [
                    $data['title'],
                    $data['description'] ?? null,
                    $data['target_group'],
                    $startDate,
                    $endDate,
                    $status,
                    $surveyId,
                ]
            );

            if ($result === false) {
                throw new \Exception('Update failed');
            }

            if (isset($data['questions']) && is_array($data['questions'])) {
                $existingIds = array_column(
                    dbFetchAll("SELECT question_id FROM qa_survey_questions WHERE survey_id = ?", 'i', [$surveyId]),
                    'question_id'
                );

                $keptIds = [];
                foreach ($data['questions'] as $idx => $q) {
                    if (!empty($q['question_id'])) {
                        $this->updateQuestion($surveyId, $q, $idx);
                        $keptIds[] = (int)$q['question_id'];
                    } else {
                        $newId = $this->saveQuestion($surveyId, $q, $idx);
                        if ($newId) {
                            $keptIds[] = $newId;
                        }
                    }
                }

                // Remove questions that were deleted in the UI
                foreach (array_diff($existingIds, $keptIds) as $qid) {
                    dbExecute("DELETE FROM qa_question_options  WHERE question_id = ?", 'i', [$qid]);
                    dbExecute("DELETE FROM qa_survey_questions  WHERE question_id = ?", 'i', [$qid]);
                }
            }

            dbCommit();
            jsonResponse(true, 'Survey updated successfully');
        } catch (\Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to update survey: ' . $e->getMessage(), [], 500);
        }
    }

    // ── Question helpers ──────────────────────────────────────────────────────

    private function fetchQuestionsWithOptions(int $surveyId): array
    {
        $questions = dbFetchAll(
            "SELECT * FROM qa_survey_questions WHERE survey_id = ? ORDER BY sort_order",
            'i',
            [$surveyId]
        );

        foreach ($questions as &$q) {
            $options = dbFetchAll(
                "SELECT * FROM qa_question_options WHERE question_id = ? ORDER BY sort_order",
                'i',
                [$q['question_id']]
            );
            $q['options'] = $this->uniqueOptions($options);
        }

        return $questions;
    }

    private function saveQuestion(int $surveyId, array $question, int $index): int|false
    {
        $isRequired = isset($question['is_required']) ? (int)$question['is_required'] : 1;

        $result = dbExecute(
            "INSERT INTO qa_survey_questions
                    (survey_id, question_text, question_type, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?)",
            'issii',
            [$surveyId, $question['question_text'], $question['question_type'], $isRequired, $index]
        );

        if (!$result) {
            return false;
        }

        $questionId = $this->conn->insert_id;
        $this->saveOptions($questionId, $question);

        return $questionId;
    }

    private function updateQuestion(int $surveyId, array $question, int $index): void
    {
        $questionId = (int)$question['question_id'];
        $isRequired = isset($question['is_required']) ? (int)$question['is_required'] : 1;

        dbExecute(
            "UPDATE qa_survey_questions
                SET question_text = ?,
                    question_type = ?,
                    is_required   = ?,
                    sort_order    = ?
              WHERE question_id   = ?
                AND survey_id     = ?",
            'ssiiii',
            [$question['question_text'], $question['question_type'], $isRequired, $index, $questionId, $surveyId]
        );

        $this->syncOptions($questionId, $question);
    }

    private function saveOptions(int $questionId, array $question): void
    {
        if (!in_array($question['question_type'], ['multiple_choice', 'checkbox'], true)) {
            return;
        }

        foreach ($this->normalizeOptionTexts($question['options'] ?? []) as $idx => $text) {
            dbExecute(
                "INSERT INTO qa_question_options (question_id, option_text, sort_order) VALUES (?, ?, ?)",
                'isi',
                [$questionId, $text, $idx]
            );
        }
    }

    // ── Delete operations ─────────────────────────────────────────────────────

    private function syncOptions(int $questionId, array $question): void
    {
        if (!in_array($question['question_type'], ['multiple_choice', 'checkbox'], true)) {
            $this->deleteUnansweredOptions($questionId);
            return;
        }

        $desiredTexts = $this->normalizeOptionTexts($question['options'] ?? []);
        $existingOptions = dbFetchAll(
            "SELECT o.option_id,
                    o.option_text,
                    o.sort_order,
                    COUNT(a.answer_id) AS answers_count
               FROM qa_question_options o
               LEFT JOIN qa_survey_answers a ON a.option_id = o.option_id
              WHERE o.question_id = ?
              GROUP BY o.option_id, o.option_text, o.sort_order
              ORDER BY o.sort_order, o.option_id",
            'i',
            [$questionId]
        );

        $existingByText = [];
        foreach ($existingOptions as $option) {
            $key = $this->optionKey($option['option_text'] ?? '');
            if ($key !== '') {
                $existingByText[$key][] = $option;
            }
        }

        $usedOptionIds = [];
        foreach ($desiredTexts as $idx => $text) {
            $key = $this->optionKey($text);
            $matches = $existingByText[$key] ?? [];
            $canonical = $matches[0] ?? null;

            if ($canonical) {
                $optionId = (int)$canonical['option_id'];
                $usedOptionIds[] = $optionId;

                dbExecute(
                    "UPDATE qa_question_options SET option_text = ?, sort_order = ? WHERE option_id = ?",
                    'sii',
                    [$text, $idx, $optionId]
                );

                foreach (array_slice($matches, 1) as $duplicate) {
                    if ((int)($duplicate['answers_count'] ?? 0) === 0) {
                        dbExecute("DELETE FROM qa_question_options WHERE option_id = ?", 'i', [(int)$duplicate['option_id']]);
                    }
                }
            } else {
                $newId = dbExecute(
                    "INSERT INTO qa_question_options (question_id, option_text, sort_order) VALUES (?, ?, ?)",
                    'isi',
                    [$questionId, $text, $idx]
                );

                if ($newId) {
                    $usedOptionIds[] = (int)$newId;
                }
            }
        }

        foreach ($existingOptions as $option) {
            $optionId = (int)$option['option_id'];
            if (!in_array($optionId, $usedOptionIds, true) && (int)($option['answers_count'] ?? 0) === 0) {
                dbExecute("DELETE FROM qa_question_options WHERE option_id = ?", 'i', [$optionId]);
            }
        }
    }

    private function deleteUnansweredOptions(int $questionId): void
    {
        dbExecute(
            "DELETE o
               FROM qa_question_options o
               LEFT JOIN qa_survey_answers a ON a.option_id = o.option_id
              WHERE o.question_id = ?
                AND a.answer_id IS NULL",
            'i',
            [$questionId]
        );
    }

    private function normalizeOptionTexts(array $options): array
    {
        $texts = [];
        $seen = [];

        foreach ($options as $option) {
            if (is_array($option)) {
                $text = trim((string)($option['option_text'] ?? $option['text'] ?? $option['value'] ?? ''));
            } else {
                $text = trim((string)$option);
            }

            $key = $this->optionKey($text);
            if ($key !== '' && !isset($seen[$key])) {
                $texts[] = $text;
                $seen[$key] = true;
            }
        }

        return $texts;
    }

    private function uniqueOptions(array $options): array
    {
        $unique = [];
        $seen = [];

        foreach ($options as $option) {
            $key = $this->optionKey($option['option_text'] ?? '');
            if ($key !== '' && !isset($seen[$key])) {
                $unique[] = $option;
                $seen[$key] = true;
            }
        }

        return $unique;
    }

    private function optionKey(string $text): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower(trim($text)) : strtolower(trim($text));
    }

    private function deleteRespondent(int $respondentId): void
    {
        if (!dbFetchOne("SELECT respondent_id FROM qa_survey_respondents WHERE respondent_id = ?", 'i', [$respondentId])) {
            jsonResponse(false, 'Response not found', [], 404);
            return;
        }

        dbBegin();
        try {
            dbExecute("DELETE FROM qa_survey_answers     WHERE respondent_id = ?", 'i', [$respondentId]);
            $result = dbExecute("DELETE FROM qa_survey_respondents WHERE respondent_id = ?", 'i', [$respondentId]);

            if ($result === false) {
                throw new \Exception('Delete failed');
            }

            dbCommit();
            jsonResponse(true, 'Response deleted successfully');
        } catch (\Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to delete response: ' . $e->getMessage(), [], 500);
        }
    }

    private function deleteSurvey(int $surveyId): void
    {
        $responses = dbFetchOne(
            "SELECT COUNT(*) AS count FROM qa_survey_respondents WHERE survey_id = ?",
            'i',
            [$surveyId]
        );

        if ((int)($responses['count'] ?? 0) > 0) {
            jsonResponse(false, 'Cannot delete a survey that already has responses.', [], 400);
            return;
        }

        dbBegin();
        try {
            $questions = dbFetchAll(
                "SELECT question_id FROM qa_survey_questions WHERE survey_id = ?",
                'i',
                [$surveyId]
            );

            foreach ($questions as $q) {
                dbExecute("DELETE FROM qa_question_options WHERE question_id = ?", 'i', [$q['question_id']]);
            }

            dbExecute("DELETE FROM qa_survey_questions WHERE survey_id = ?", 'i', [$surveyId]);
            $result = dbExecute("DELETE FROM qa_surveys WHERE survey_id = ?", 'i', [$surveyId]);

            if ($result === false) {
                throw new \Exception('Delete failed');
            }

            dbCommit();
            jsonResponse(true, 'Survey deleted successfully');
        } catch (\Exception $e) {
            dbRollback();
            jsonResponse(false, 'Failed to delete survey: ' . $e->getMessage(), [], 500);
        }
    }

    // ── Utility ───────────────────────────────────────────────────────────────

    private function getResponseCount(int $surveyId): int
    {
        $row = dbFetchOne(
            "SELECT COUNT(DISTINCT respondent_id) AS count FROM qa_survey_respondents WHERE survey_id = ?",
            'i',
            [$surveyId]
        );
        return (int)($row['count'] ?? 0);
    }
}

// ─────────────────────────────────────────────────────────────────────────────

$api = new SurveyAPI();
$api->handleRequest();
