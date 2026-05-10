<?php
/**
 * Reports API
 * Quality Assurance Management System
 * backend/api/reports_api.php
 *
 * Aggregates data from all QA modules for the unified reports dashboard.
 * Endpoints (GET ?action=...):
 *   summary          – high-level counts & status breakdowns
 *   audits           – audit list with task counts
 *   tasks            – accreditation task list
 *   kpis             – KPI indicators with latest records
 *   surveys          – survey list with response counts
 *   action_plans     – action plan list
 *   standards        – standards & policy counts
 *   survey_responses – per-survey response details
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

session_start();

if (empty($_SESSION['logged_in'])) {
    jsonResponse(false, 'Unauthorized access. Please login.', [], 401);
}

$action = $_GET['action'] ?? 'summary';

try {
    switch ($action) {
        case 'summary':
            getSummary();
            break;
        case 'audits':
            getAuditsReport();
            break;
        case 'tasks':
            getTasksReport();
            break;
        case 'kpis':
            getKpiReport();
            break;
        case 'surveys':
            getSurveysReport();
            break;
        case 'action_plans':
            getActionPlansReport();
            break;
        case 'standards':
            getStandardsReport();
            break;
        case 'survey_responses':
            getSurveyResponsesReport();
            break;
        default:
            jsonResponse(false, 'Invalid action specified', [], 400);
    }
} catch (Exception $e) {
    error_log('Reports API Error: ' . $e->getMessage());
    jsonResponse(false, 'An unexpected error occurred: ' . $e->getMessage(), [], 500);
}

/* ============================================================
   SUMMARY – overview counts and breakdowns
   ============================================================ */
function getSummary() {
    $conn = getDBConnection();

    // ── Audits ──────────────────────────────────────────────
    $auditStats = dbFetchAll(
        "SELECT status, COUNT(*) AS cnt FROM qa_audits GROUP BY status"
    );
    $auditTotal  = 0;
    $auditByStatus = [];
    foreach ($auditStats as $r) {
        $auditByStatus[$r['status']] = (int) $r['cnt'];
        $auditTotal += (int) $r['cnt'];
    }

    // ── Tasks ───────────────────────────────────────────────
    $taskStats = dbFetchAll(
        "SELECT status, COUNT(*) AS cnt FROM qa_accreditation_tasks GROUP BY status"
    );
    $taskTotal  = 0;
    $taskByStatus = [];
    foreach ($taskStats as $r) {
        $taskByStatus[$r['status']] = (int) $r['cnt'];
        $taskTotal += (int) $r['cnt'];
    }

    // ── Action Plans ────────────────────────────────────────
    $planStats = dbFetchAll(
        "SELECT status, COUNT(*) AS cnt FROM qa_action_plans GROUP BY status"
    );
    $planTotal  = 0;
    $planByStatus = [];
    foreach ($planStats as $r) {
        $planByStatus[$r['status']] = (int) $r['cnt'];
        $planTotal += (int) $r['cnt'];
    }

    // ── KPI Indicators ──────────────────────────────────────
    $kpiCount = (int) (dbFetchOne("SELECT COUNT(*) AS cnt FROM qa_indicators")['cnt'] ?? 0);
    $kpiMeetingTarget = (int) (dbFetchOne(
        "SELECT COUNT(DISTINCT r.indicator_id) AS cnt
         FROM qa_kpi_records r
         JOIN qa_indicators i ON r.indicator_id = i.indicator_id
         WHERE r.actual_value >= i.target_value"
    )['cnt'] ?? 0);

    // Average of latest KPI actual values (one latest record per indicator)
    $kpiAvgRow = dbFetchOne(
        "SELECT AVG(latest_val) AS avg_val FROM (
            SELECT r.actual_value AS latest_val
            FROM qa_kpi_records r
            JOIN (
                SELECT indicator_id, MAX(record_id) AS rid FROM qa_kpi_records GROUP BY indicator_id
            ) m ON r.indicator_id = m.indicator_id AND r.record_id = m.rid
        ) t"
    );
    $kpiAvg = $kpiAvgRow && $kpiAvgRow['avg_val'] !== null ? (float) $kpiAvgRow['avg_val'] : null;

    // ── Surveys ─────────────────────────────────────────────
    $surveyStats = dbFetchAll(
        "SELECT status, COUNT(*) AS cnt FROM qa_surveys GROUP BY status"
    );
    $surveyTotal  = 0;
    $surveyByStatus = [];
    foreach ($surveyStats as $r) {
        $surveyByStatus[$r['status']] = (int) $r['cnt'];
        $surveyTotal += (int) $r['cnt'];
    }
    $totalResponses = (int) (dbFetchOne(
        "SELECT COUNT(*) AS cnt FROM qa_survey_respondents"
    )['cnt'] ?? 0);

    // Recent surveys (last 5)
    $recentSurveys = dbFetchAll(
        "SELECT s.survey_id, s.title, s.target_group, s.status,
                (SELECT COUNT(DISTINCT r.respondent_id) FROM qa_survey_respondents r WHERE r.survey_id = s.survey_id) AS responses_count
         FROM qa_surveys s
         ORDER BY s.survey_id DESC LIMIT 5"
    );

    // ── Standards & Policies ────────────────────────────────
    $standardsCount = (int) (dbFetchOne(
        "SELECT COUNT(*) AS cnt FROM qa_standards WHERE status='Active'"
    )['cnt'] ?? 0);
    $policiesCount = (int) (dbFetchOne(
        "SELECT COUNT(*) AS cnt FROM qa_policies WHERE status='Active'"
    )['cnt'] ?? 0);

    // ── Recent activity (last 5 audits, newest first) ───────
    $recentAudits = dbFetchAll(
        "SELECT audit_id, title, audit_type, status, scheduled_date
         FROM qa_audits ORDER BY audit_id DESC LIMIT 5"
    );

    jsonResponse(true, 'Summary loaded', ['data' => [
        'audits'    => ['total' => $auditTotal,   'by_status' => $auditByStatus],
        'tasks'     => ['total' => $taskTotal,    'by_status' => $taskByStatus],
        'plans'     => ['total' => $planTotal,    'by_status' => $planByStatus],
        'kpis'      => ['total' => $kpiCount,     'meeting_target' => $kpiMeetingTarget, 'avg' => $kpiAvg],
        'surveys'   => ['total' => $surveyTotal,  'by_status' => $surveyByStatus,
                        'total_responses' => $totalResponses],
        'standards' => ['active' => $standardsCount, 'policies' => $policiesCount],
        'recent_audits' => $recentAudits,
        'recent_surveys' => $recentSurveys,
    ]]);
}

/* ============================================================
   AUDITS REPORT
   ============================================================ */
function getAuditsReport() {
    $rows = dbFetchAll(
        "SELECT a.*,
                COUNT(t.task_id)                                       AS total_tasks,
                SUM(t.status = 'Completed')                            AS completed_tasks,
                SUM(t.status = 'In Progress')                          AS inprogress_tasks,
                SUM(t.status = 'Pending')                              AS pending_tasks
         FROM qa_audits a
         LEFT JOIN qa_accreditation_tasks t ON t.audit_id = a.audit_id
         GROUP BY a.audit_id
         ORDER BY a.scheduled_date DESC, a.audit_id DESC"
    );

    jsonResponse(true, 'Audits report loaded', ['data' => $rows]);
}

/* ============================================================
   TASKS REPORT
   ============================================================ */
function getTasksReport() {
    $rows = dbFetchAll(
        "SELECT t.*,
                a.title AS audit_title, a.audit_type,
                s.title AS standard_title, s.body AS standard_body
         FROM qa_accreditation_tasks t
         LEFT JOIN qa_audits a     ON a.audit_id     = t.audit_id
         LEFT JOIN qa_standards s  ON s.standard_id  = t.standard_id
         ORDER BY t.due_date ASC, t.task_id DESC"
    );

    jsonResponse(true, 'Tasks report loaded', ['data' => $rows]);
}

/* ============================================================
   KPI REPORT
   ============================================================ */
function getKpiReport() {
    $indicators = dbFetchAll(
        "SELECT * FROM qa_indicators ORDER BY category, name"
    );

    foreach ($indicators as &$ind) {
        $records = dbFetchAll(
            "SELECT * FROM qa_kpi_records
             WHERE indicator_id = ?
             ORDER BY period_year DESC, period_term",
            'i', [$ind['indicator_id']]
        );
        $ind['records'] = $records;

        // Latest record
        $latest = $records[0] ?? null;
        $ind['latest_value']  = $latest ? (float) $latest['actual_value'] : null;
        $ind['latest_period'] = $latest ? $latest['period_year'] . ' ' . $latest['period_term'] : null;
        $ind['meets_target']  = $latest && $ind['target_value'] !== null
                                    ? ((float) $latest['actual_value'] >= (float) $ind['target_value'])
                                    : null;
    }

    jsonResponse(true, 'KPI report loaded', ['data' => $indicators]);
}

/* ============================================================
   SURVEYS REPORT
   ============================================================ */
function getSurveysReport() {
    $rows = dbFetchAll(
        "SELECT s.*,
                u.full_name AS creator_name,
                (SELECT COUNT(*) FROM qa_survey_questions q WHERE q.survey_id = s.survey_id)         AS questions_count,
                (SELECT COUNT(DISTINCT r.respondent_id) FROM qa_survey_respondents r WHERE r.survey_id = s.survey_id) AS responses_count
         FROM qa_surveys s
         LEFT JOIN qa_users u ON u.user_id = s.created_by
         ORDER BY s.survey_id DESC"
    );

    jsonResponse(true, 'Surveys report loaded', ['data' => $rows]);
}

/* ============================================================
   ACTION PLANS REPORT
   ============================================================ */
function getActionPlansReport() {
    $rows = dbFetchAll(
        "SELECT p.*, a.title AS audit_title, a.audit_type
         FROM qa_action_plans p
         LEFT JOIN qa_audits a ON a.audit_id = p.audit_id
         ORDER BY p.target_date ASC, p.plan_id DESC"
    );

    jsonResponse(true, 'Action plans report loaded', ['data' => $rows]);
}

/* ============================================================
   STANDARDS REPORT
   ============================================================ */
function getStandardsReport() {
    $rows = dbFetchAll(
        "SELECT s.*,
                COUNT(p.policy_id)                     AS total_policies,
                SUM(p.status = 'Active')               AS active_policies,
                COUNT(t.task_id)                       AS linked_tasks
         FROM qa_standards s
         LEFT JOIN qa_policies p  ON p.standard_id = s.standard_id
         LEFT JOIN qa_accreditation_tasks t ON t.standard_id = s.standard_id
         GROUP BY s.standard_id
         ORDER BY s.body, s.title"
    );

    jsonResponse(true, 'Standards report loaded', ['data' => $rows]);
}

/* ============================================================
   SURVEY RESPONSES REPORT (per-survey rollup)
   ============================================================ */
function getSurveyResponsesReport() {
    $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : null;

    if ($surveyId) {
        $surveys = dbFetchAll(
            "SELECT survey_id, title, target_group, status FROM qa_surveys WHERE survey_id = ?",
            'i', [$surveyId]
        );
    } else {
        $surveys = dbFetchAll(
            "SELECT survey_id, title, target_group, status FROM qa_surveys ORDER BY survey_id DESC"
        );
    }

    $payload = [];
    foreach ($surveys as $survey) {
        $sid = (int) $survey['survey_id'];

        $questions = dbFetchAll(
            "SELECT question_id, question_text, question_type FROM qa_survey_questions
             WHERE survey_id = ? ORDER BY sort_order", 'i', [$sid]
        );

        $responses = (int) (dbFetchOne(
            "SELECT COUNT(DISTINCT respondent_id) AS cnt FROM qa_survey_respondents WHERE survey_id = ?",
            'i', [$sid]
        )['cnt'] ?? 0);

        // Rating averages per question
        $ratingAverages = dbFetchAll(
            "SELECT a.question_id, AVG(a.rating_value) AS avg_rating, COUNT(*) AS answer_count
             FROM qa_survey_answers a
             JOIN qa_survey_respondents r ON r.respondent_id = a.respondent_id
             WHERE r.survey_id = ? AND a.rating_value IS NOT NULL
             GROUP BY a.question_id",
            'i', [$sid]
        );
        $ratingsMap = [];
        foreach ($ratingAverages as $ra) {
            $ratingsMap[$ra['question_id']] = [
                'avg'   => round((float) $ra['avg_rating'], 2),
                'count' => (int) $ra['answer_count'],
            ];
        }

        // Option frequencies per question
        $optionFreqs = dbFetchAll(
            "SELECT a.question_id, o.option_text, COUNT(*) AS freq
             FROM qa_survey_answers a
             JOIN qa_survey_respondents r ON r.respondent_id = a.respondent_id
             JOIN qa_question_options o   ON o.option_id     = a.option_id
             WHERE r.survey_id = ? AND a.option_id IS NOT NULL
             GROUP BY a.question_id, a.option_id
             ORDER BY a.question_id, freq DESC",
            'i', [$sid]
        );
        $optFreqMap = [];
        foreach ($optionFreqs as $of) {
            $optFreqMap[$of['question_id']][] = [
                'label' => $of['option_text'],
                'count' => (int) $of['freq'],
            ];
        }

        // Attach summaries to questions
        foreach ($questions as &$q) {
            $qid = $q['question_id'];
            $q['rating_summary'] = $ratingsMap[$qid] ?? null;
            $q['option_summary'] = $optFreqMap[$qid]  ?? null;
        }

        $payload[] = [
            'survey_id'       => $sid,
            'title'           => $survey['title'],
            'target_group'    => $survey['target_group'],
            'status'          => $survey['status'],
            'responses_count' => $responses,
            'questions'       => $questions,
        ];
    }

    jsonResponse(true, 'Survey responses report loaded', ['data' => $payload]);
}