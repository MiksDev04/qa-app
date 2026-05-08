<?php
/**
 * Policies API - CRUD Operations
 * backend/api/policies.php
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
                getPolicyById((int)$_GET['id']);
            } else if ($action === 'list') {
                listPolicies();
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
                    deletePolicy($data['id'] ?? 0);
                } else if ($data['action'] === 'create') {
                    createPolicy($data);
                } else if ($data['action'] === 'update') {
                    updatePolicy($data);
                } else {
                    jsonResponse(false, 'Invalid action');
                }
            } else if (isset($data['policy_id']) && $data['policy_id']) {
                updatePolicy($data);
            } else {
                createPolicy($data);
            }
            break;
            
        default:
            jsonResponse(false, 'Method not allowed', [], 405);
    }
} catch (Exception $e) {
    error_log('Policies API Error: ' . $e->getMessage());
    jsonResponse(false, 'Server error occurred', [], 500);
}

/**
 * Get all policies with filtering and joined data
 */
function listPolicies(): void {
    $conn = getDBConnection();
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : '';
    
    $sql = "
        SELECT p.*, s.title as standard_title, s.body as standard_body
        FROM qa_policies p
        LEFT JOIN qa_standards s ON p.standard_id = s.standard_id
        WHERE 1=1
    ";
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $sql .= " AND (p.title LIKE ? OR p.content LIKE ? OR s.title LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    if ($status !== '') {
        $sql .= " AND p.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $sql .= " ORDER BY p.created_date DESC, p.policy_id DESC";
    
    $stmt = $conn->prepare($sql);
    if ($types && count($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $policies = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    jsonResponse(true, 'Policies retrieved', ['data' => $policies]);
}

/**
 * Get single policy by ID with joined data
 */
function getPolicyById(int $id): void {
    if ($id <= 0) {
        jsonResponse(false, 'Invalid policy ID');
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT p.*, s.title as standard_title 
        FROM qa_policies p
        LEFT JOIN qa_standards s ON p.standard_id = s.standard_id
        WHERE p.policy_id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $policy = $result->fetch_assoc();
    $stmt->close();
    
    if ($policy) {
        jsonResponse(true, 'Policy found', ['data' => $policy]);
    } else {
        jsonResponse(false, 'Policy not found');
    }
}

/**
 * Create a new policy
 */
function createPolicy(array $data): void {
    $errors = validatePolicyData($data);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $conn = getDBConnection();
    
    $title = $data['title'];
    $standard_id = !empty($data['standard_id']) ? (int)$data['standard_id'] : null;
    $content = $data['content'];
    $document_url = !empty($data['document_url']) ? $data['document_url'] : null;
    $created_date = !empty($data['created_date']) ? $data['created_date'] : date('Y-m-d');
    $status = $data['status'] ?? 'Active';
    
    // Validate standard_id if provided
    if ($standard_id) {
        $checkStmt = $conn->prepare("SELECT standard_id FROM qa_standards WHERE standard_id = ?");
        $checkStmt->bind_param('i', $standard_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            jsonResponse(false, 'Validation failed', ['errors' => ['standard_id' => 'Selected standard does not exist']]);
            return;
        }
        $checkStmt->close();
    }
    
    $stmt = $conn->prepare("
        INSERT INTO qa_policies (standard_id, title, content, document_url, created_date, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('isssss', $standard_id, $title, $content, $document_url, $created_date, $status);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Policy created successfully', ['policy_id' => $id]);
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to create policy');
    }
}

/**
 * Update an existing policy
 */
function updatePolicy(array $data): void {
    $id = $data['policy_id'] ?? 0;
    if ($id <= 0) {
        jsonResponse(false, 'Invalid policy ID');
    }
    
    $errors = validatePolicyData($data, true);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $conn = getDBConnection();
    
    $title = $data['title'];
    $standard_id = !empty($data['standard_id']) ? (int)$data['standard_id'] : null;
    $content = $data['content'];
    $document_url = !empty($data['document_url']) ? $data['document_url'] : null;
    $created_date = !empty($data['created_date']) ? $data['created_date'] : date('Y-m-d');
    $status = $data['status'] ?? 'Active';
    
    // Validate standard_id if provided
    if ($standard_id) {
        $checkStmt = $conn->prepare("SELECT standard_id FROM qa_standards WHERE standard_id = ?");
        $checkStmt->bind_param('i', $standard_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            jsonResponse(false, 'Validation failed', ['errors' => ['standard_id' => 'Selected standard does not exist']]);
            return;
        }
        $checkStmt->close();
    }
    
    $stmt = $conn->prepare("
        UPDATE qa_policies 
        SET standard_id = ?, title = ?, content = ?, document_url = ?, created_date = ?, status = ?
        WHERE policy_id = ?
    ");
    $stmt->bind_param('isssssi', $standard_id, $title, $content, $document_url, $created_date, $status, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Policy updated successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to update policy');
    }
}

/**
 * Delete a policy
 */
function deletePolicy(int $id): void {
    if ($id <= 0) {
        jsonResponse(false, 'Invalid policy ID');
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("DELETE FROM qa_policies WHERE policy_id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Policy deleted successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to delete policy');
    }
}

/**
 * Validate policy data
 */
function validatePolicyData(array $data, bool $isUpdate = false): array {
    $errors = [];
    
    if (empty($data['title']) || trim($data['title']) === '') {
        $errors['title'] = 'Title is required';
    } elseif (strlen($data['title']) > 150) {
        $errors['title'] = 'Title must not exceed 150 characters';
    }
    
    if (empty($data['content']) || trim($data['content']) === '') {
        $errors['content'] = 'Content is required';
    }
    
    if (!empty($data['document_url'])) {
        if (!filter_var($data['document_url'], FILTER_VALIDATE_URL)) {
            $errors['document_url'] = 'Please enter a valid URL';
        }
        if (strlen($data['document_url']) > 255) {
            $errors['document_url'] = 'URL must not exceed 255 characters';
        }
    }
    
    if (!empty($data['created_date']) && !validateDate($data['created_date'])) {
        $errors['created_date'] = 'Please enter a valid date';
    }
    
    $allowedStatuses = ['Active', 'Archived'];
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