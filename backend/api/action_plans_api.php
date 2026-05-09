<?php
/**
 * Standards API - CRUD Operations
 * backend/api/standards.php
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

// Check authentication
if (empty($_SESSION['logged_in'])) {
    jsonResponse(false, 'Unauthorized access', [], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'get' && isset($_GET['id'])) {
                getActionPlanById((int)$_GET['id']);
            } else if ($action === 'list') {
                listActionPlans();
            } else {
                jsonResponse(false, 'Invalid action');
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                jsonResponse(false, 'Invalid JSON data');
            }
            
            if (isset($data['action'])) {
                if ($data['action'] === 'delete') {
                    deleteActionPlan($data['id'] ?? 0);
                } else if ($data['action'] === 'create') {
                    createActionPlan($data);
                } else if ($data['action'] === 'update') {
                    updateActionPlan($data);
                } else {
                    jsonResponse(false, 'Invalid action');
                }
            } else if (isset($data['plan_id']) && $data['plan_id']) {
                updateActionPlan($data);
            } else {
                createActionPlan($data);
            }
            break;
            
        default:
            jsonResponse(false, 'Method not allowed', [], 405);
    }
} catch (Exception $e) {
    error_log('Action Plan API Error: ' . $e->getMessage());
    jsonResponse(false, 'Server error occurred', [], 500);
}

/**
 * Get all action plans with filtering
 */
function listActionPlans(): void {
    $conn = getDBConnection();
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : '';
    
    $sql = "SELECT * FROM qa_action_plans WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }
    
    if ($status !== '') {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $sql .= " ORDER BY plan_id DESC";
    
    $stmt = $conn->prepare($sql);
    if ($types && count($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $actionPlans = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    jsonResponse(true, 'Action Plans retrieved', ['data' => $actionPlans]);
}

/**
 * Get single standard by ID
 */
function getActionPlanById(int $id): void {
    if ($id <= 0) {
        jsonResponse(false, 'Invalid standard ID');
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM qa_action_plans WHERE plan_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $actionPlan = $result->fetch_assoc();
    $stmt->close();
    
    if ($actionPlan) {
        jsonResponse(true, 'Standard found', ['data' => $actionPlan]);
    } else {
        jsonResponse(false, 'Standard not found');
    }
}

/**
 * Create a new standard
 */
function createActionPlan(array $data): void {
    $errors = validateActionPlanData($data);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $conn = getDBConnection();
    
    $audit_id = $data['audit_id'];
    $title = $data['title'];
    $description = $data['description'] ?? '';
    $root_cause = $data['root_cause'];
    $target_date = $data['target_date'] ?? '';
    $status = $data['status'] ?? 'Active';
    
    $stmt = $conn->prepare("
        INSERT INTO qa_action_plans (audit_id, title, description, root_cause, target_date, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('isssss', $audit_id, $title, $description, $root_cause, $target_date, $status);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Action Plan created successfully', ['plan_id' => $id]);
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to create action plan');
    }
}

/**
 * Update an existing standard
 */
function updateActionPlan(array $data): void {
    $id = $data['plan_id'] ?? 0;
    if ($id <= 0) {
        jsonResponse(false, 'Invalid standard ID');
    }
    
    $errors = validateActionPlanData($data, true);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $conn = getDBConnection();
    
    $audit_id = $data['audit_id'];
    $title = $data['title'];
    $description = $data['description'] ?? '';
    $root_cause = $data['root_cause'];
    $target_date = $data['target_date'] ?? '';
    $status = $data['status'] ?? 'Active';
    
    $stmt = $conn->prepare("
        UPDATE qa_action_plans 
        SET audit_id = ?, title = ?, description = ?, root_cause = ?, target_date = ?, status = ?
        WHERE plan_id = ?
    ");
    $stmt->bind_param('isssssi', $title, $body, $description, $version, $target_date, $status, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Action Plan updated successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to update Action Plan');
    }
}

/**
 * Delete a action plan
 */
function deleteActionPlan(int $id): void {
    if ($id <= 0) {
        jsonResponse(false, 'Invalid standard ID');
    }
    
    $conn = getDBConnection();

    
    $stmt = $conn->prepare("DELETE FROM qa_action_plans WHERE plan_id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Action Plan deleted successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to delete action plan');
    }
}

/**
 * Validate standard data
 */
function validateActionPlanData(array $data, bool $isUpdate = false): array {
    $errors = [];
    
    if (empty($data['title']) || trim($data['title']) === '') {
        $errors['title'] = 'Title is required';
    } elseif (strlen($data['title']) > 150) {
        $errors['title'] = 'Title must not exceed 150 characters';
    }
    
    
    // if (!empty($data['version']) && strlen($data['version']) > 20) {
    //     $errors['version'] = 'Version must not exceed 20 characters';
    // }
    
    if (!empty($data['target_date']) && !validateDate($data['target_date'])) {
        $errors['target_date'] = 'Please enter a valid date';
    }
    
    $allowedStatuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
    if (!empty($data['status']) && !in_array($data['status'], $allowedStatuses)) {
        $errors['status'] = 'Invalid status value';
    }
    
    return $errors;
}

/**
 * Validate date format YYYY-MM-DD
 */
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>