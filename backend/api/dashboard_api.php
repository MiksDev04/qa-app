<?php
/**
 * Dashboard API - Complete API for dashboard data
 * backend/api/dashboard_api.php
 */

require_once '../config/database.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request action
$action = $_GET['action'] ?? '';

// Route to appropriate handler
switch ($action) {
    case 'get_stats':
        getDashboardStats();
        break;
    case 'get_audits':
        getRecentAudits();
        break;
    case 'get_action_plans':
        getActionPlans();
        break;
    case 'get_kpis':
        getKPIs();
        break;
    case 'get_activity':
        getRecentActivity();
        break;
    case 'get_survey_summary':
        getSurveySummary();
        break;
    case 'create_item':
        createItem();
        break;
    default:
        jsonResponse(false, 'Invalid action specified', [], 400);
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    try {
        $conn = getDBConnection();
        
        // Active standards count
        $standardsQuery = "SELECT COUNT(*) as count FROM qa_standards WHERE status = 'Active'";
        $standardsResult = $conn->query($standardsQuery);
        $standardsCount = $standardsResult->fetch_assoc()['count'];
        
        // Ongoing audits (Scheduled or In Progress)
        $auditsQuery = "SELECT COUNT(*) as count FROM qa_audits WHERE status IN ('Scheduled', 'In Progress')";
        $auditsResult = $conn->query($auditsQuery);
        $auditsCount = $auditsResult->fetch_assoc()['count'];
        
        // Average KPI score based on the latest available record per indicator
        $kpiQuery = "SELECT AVG((latest.actual_value / i.target_value) * 100) as avg_score
                     FROM qa_indicators i
                     LEFT JOIN (
                         SELECT kr1.indicator_id, kr1.actual_value
                         FROM qa_kpi_records kr1
                         INNER JOIN (
                             SELECT indicator_id, MAX(record_id) AS max_record_id
                             FROM qa_kpi_records
                             WHERE actual_value IS NOT NULL
                             GROUP BY indicator_id
                         ) latest_record ON latest_record.indicator_id = kr1.indicator_id
                                        AND latest_record.max_record_id = kr1.record_id
                     ) latest ON latest.indicator_id = i.indicator_id
                     WHERE i.target_value > 0 AND latest.actual_value IS NOT NULL";
        $kpiResult = $conn->query($kpiQuery);
        $kpiAvg = round($kpiResult->fetch_assoc()['avg_score'] ?? 0, 1);
        
        // Open action plans
        $plansQuery = "SELECT COUNT(*) as count FROM qa_action_plans WHERE status IN ('Open', 'In Progress')";
        $plansResult = $conn->query($plansQuery);
        $plansCount = $plansResult->fetch_assoc()['count'];
        
        jsonResponse(true, 'Stats retrieved successfully', [
            'stats' => [
                'standards' => (int)$standardsCount,
                'audits' => (int)$auditsCount,
                'kpi_avg' => $kpiAvg,
                'open_plans' => (int)$plansCount
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Error in get_stats: ' . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve statistics', [], 500);
    }
}

/**
 * Get recent audits
 */
function getRecentAudits() {
    try {
        $conn = getDBConnection();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        
        $query = "SELECT audit_id, title, status, scheduled_date, completion_date,
                         CASE 
                             WHEN status = 'Completed' THEN 100
                             WHEN status = 'In Progress' THEN 50
                             WHEN status = 'Scheduled' THEN 25
                             ELSE 0
                         END as progress,
                         DATE_FORMAT(COALESCE(completion_date, scheduled_date), '%b %d, %Y') as updated
                  FROM qa_audits
                  ORDER BY scheduled_date DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $audits = [];
        while ($row = $result->fetch_assoc()) {
            $audits[] = [
                'id' => $row['audit_id'],
                'title' => $row['title'],
                'status' => $row['status'],
                'progress' => (int)$row['progress'],
                'updated' => $row['updated']
            ];
        }
        
        jsonResponse(true, 'Audits retrieved successfully', ['audits' => $audits]);
        
    } catch (Exception $e) {
        error_log('Error in get_audits: ' . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve audits', [], 500);
    }
}

/**
 * Get action plans
 */
function getActionPlans() {
    try {
        $conn = getDBConnection();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $status = isset($_GET['status']) ? $_GET['status'] : 'open';
        
        $statusCondition = '';
        if ($status === 'open') {
            $statusCondition = "WHERE status IN ('Open', 'In Progress')";
        }
        
        $query = "SELECT plan_id, title, status, target_date,
                         CASE 
                             WHEN status = 'Resolved' THEN 100
                             WHEN status = 'Closed' THEN 100
                             WHEN status = 'In Progress' THEN 60
                             WHEN status = 'Open' THEN 20
                             ELSE 0
                         END as progress,
                         DATE_FORMAT(target_date, '%b %d, %Y') as target_date_formatted
                  FROM qa_action_plans
                  $statusCondition
                  ORDER BY target_date ASC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $plans = [];
        while ($row = $result->fetch_assoc()) {
            $plans[] = [
                'id' => $row['plan_id'],
                'title' => $row['title'],
                'status' => $row['status'],
                'progress' => (int)$row['progress'],
                'target_date' => $row['target_date_formatted'] ?? '—'
            ];
        }
        
        jsonResponse(true, 'Action plans retrieved successfully', ['plans' => $plans]);
        
    } catch (Exception $e) {
        error_log('Error in get_action_plans: ' . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve action plans', [], 500);
    }
}

/**
 * Get KPIs
 */
function getKPIs() {
    try {
        $conn = getDBConnection();
        $indicatorQuery = "SELECT indicator_id, name, target_value
                           FROM qa_indicators
                           WHERE target_value IS NOT NULL AND target_value > 0
                           ORDER BY indicator_id
                           LIMIT 4";

        $result = $conn->query($indicatorQuery);

        if (!$result) {
            throw new Exception($conn->error);
        }

        $kpis = [];
        while ($row = $result->fetch_assoc()) {
            $actualQuery = "SELECT actual_value
                            FROM qa_kpi_records
                            WHERE indicator_id = ?
                            ORDER BY school_year DESC, record_id DESC
                            LIMIT 1";

            $actualStmt = $conn->prepare($actualQuery);
            if (!$actualStmt) {
                throw new Exception($conn->error);
            }

            $indicatorId = (int) $row['indicator_id'];
            $actualStmt->bind_param('i', $indicatorId);
            $actualStmt->execute();
            $actualResult = $actualStmt->get_result();
            $actualRow = $actualResult ? $actualResult->fetch_assoc() : null;

            $kpis[] = [
                'id' => (int) $row['indicator_id'],
                'name' => $row['name'],
                'target' => (float)$row['target_value'],
                'actual' => isset($actualRow['actual_value']) ? (float) $actualRow['actual_value'] : 0,
            ];

            $actualStmt->close();
        }
        
        jsonResponse(true, 'KPIs retrieved successfully', ['kpis' => $kpis]);
        
    } catch (Exception $e) {
        error_log('Error in get_kpis: ' . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve KPIs', [], 500);
    }
}

/**
 * Get recent activity
 */
function getRecentActivity() {
    try {
        $conn = getDBConnection();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Build activity queries from multiple tables
        $activities = [];
        
        // Get recent audits
        $auditsQuery = "SELECT 'audit' as module, title as activity, status, scheduled_date as date,
                        'audit_id' as id_field, audit_id as id
                        FROM qa_audits";
        
        // Get recent action plans
        $plansQuery = "SELECT 'action_plan' as module, title as activity, status, created_date as date,
                       'plan_id' as id_field, plan_id as id
                       FROM qa_action_plans";
        
        // Get recent surveys
        $surveysQuery = "SELECT 'survey' as module, title as activity, status, start_date as date,
                        'survey_id' as id_field, survey_id as id
                        FROM qa_surveys";
        
        // Union all activities
        $unionQuery = "SELECT * FROM (($auditsQuery) UNION ALL ($plansQuery) UNION ALL ($surveysQuery)) as all_activities";
        
        if (!empty($search)) {
            $unionQuery .= " WHERE activity LIKE ?";
        }
        
        $unionQuery .= " ORDER BY date DESC LIMIT ? OFFSET ?";
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM (($auditsQuery) UNION ALL ($plansQuery) UNION ALL ($surveysQuery)) as all_activities";
        if (!empty($search)) {
            $countQuery .= " WHERE activity LIKE ?";
        }
        
        $stmt = $conn->prepare($unionQuery);
        $params = [];
        $types = "";
        
        if (!empty($search)) {
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $types .= "s";
            $params[] = $limit;
            $types .= "i";
            $params[] = $offset;
            $types .= "i";
        } else {
            $params[] = $limit;
            $types .= "i";
            $params[] = $offset;
            $types .= "i";
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => $row['id'],
                'activity' => $row['activity'],
                'module' => ucwords(str_replace('_', ' ', $row['module'])),
                'user' => 'System',
                'status' => $row['status'],
                'date' => date('M d, Y', strtotime($row['date']))
            ];
        }
        
        // Get total count
        $countStmt = $conn->prepare($countQuery);
        if (!empty($search)) {
            $countStmt->bind_param("s", $searchParam);
        }
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        $totalPages = ceil($total / $limit);
        
        jsonResponse(true, 'Activity retrieved successfully', [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages
        ]);
        
    } catch (Exception $e) {
        error_log('Error in get_activity: ' . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve activity', [], 500);
    }
}

/**
 * Get survey summary data
 */
function getSurveySummary() {
    try {
        $conn = getDBConnection();
        
        $query = "SELECT 
                    COUNT(*) as total_surveys,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_surveys,
                    AVG(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) * 100 as response_rate
                  FROM qa_surveys";
        
        $result = $conn->query($query);
        $data = $result->fetch_assoc();
        
        jsonResponse(true, 'Survey summary retrieved successfully', [
            'total_surveys' => (int)$data['total_surveys'],
            'active_surveys' => (int)$data['active_surveys'],
            'response_rate' => round($data['response_rate'] ?? 0, 1)
        ]);
        
    } catch (Exception $e) {
        error_log('Error in get_survey_summary: ' . $e->getMessage());
        jsonResponse(false, 'Failed to retrieve survey summary', [], 500);
    }
}

/**
 * Create new item (audit, action plan, survey, standard)
 */
function createItem() {
    try {
        // Check if POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Method not allowed', [], 405);
        }
        
        $type = $_POST['type'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? null;
        
        // Validate required fields
        if (empty($type)) {
            jsonResponse(false, 'Type is required', ['errors' => ['type' => 'Please select a type.']]);
        }
        
        if (empty($title)) {
            jsonResponse(false, 'Title is required', ['errors' => ['title' => 'Title is required.']]);
        }
        
        if (strlen($title) < 3) {
            jsonResponse(false, 'Title must be at least 3 characters', ['errors' => ['title' => 'Title must be at least 3 characters.']]);
        }
        
        if (strlen($title) > 150) {
            jsonResponse(false, 'Title must not exceed 150 characters', ['errors' => ['title' => 'Title must not exceed 150 characters.']]);
        }
        
        $conn = getDBConnection();
        $success = false;
        $message = '';
        
        // Create item based on type
        switch ($type) {
            case 'audit':
                $query = "INSERT INTO qa_audits (audit_type, title, scheduled_date, notes, status) 
                          VALUES (?, ?, ?, ?, 'Scheduled')";
                $stmt = $conn->prepare($query);
                $auditType = 'Internal';
                $stmt->bind_param("ssss", $auditType, $title, $due_date, $description);
                $success = $stmt->execute();
                $message = 'Audit created successfully';
                break;
                
            case 'action_plan':
                $query = "INSERT INTO qa_action_plans (title, description, target_date, status, created_date) 
                          VALUES (?, ?, ?, 'Open', CURDATE())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $title, $description, $due_date);
                $success = $stmt->execute();
                $message = 'Action plan created successfully';
                break;
                
            case 'survey':
                $query = "INSERT INTO qa_surveys (title, description, target_group, status, created_by) 
                          VALUES (?, ?, 'All', 'Draft', 3)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $title, $description);
                $success = $stmt->execute();
                $message = 'Survey created successfully';
                break;
                
            case 'standard':
                $query = "INSERT INTO qa_standards (title, body, description, status) 
                          VALUES (?, 'Institutional', ?, 'Active')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $title, $description);
                $success = $stmt->execute();
                $message = 'Standard created successfully';
                break;

            default:
                jsonResponse(false, 'Invalid item type');
        }
        
        if ($success) {
            jsonResponse(true, $message);
        } else {
            jsonResponse(false, 'Failed to create item');
        }
        
    } catch (Exception $e) {
        error_log('Error in create_item: ' . $e->getMessage());
        jsonResponse(false, 'Failed to create item: ' . $e->getMessage(), [], 500);
    }
}

