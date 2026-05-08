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
                getStandardById((int)$_GET['id']);
            } else if ($action === 'list') {
                listStandards();
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
                    deleteStandard($data['id'] ?? 0);
                } else if ($data['action'] === 'create') {
                    createStandard($data);
                } else if ($data['action'] === 'update') {
                    updateStandard($data);
                } else {
                    jsonResponse(false, 'Invalid action');
                }
            } else if (isset($data['standard_id']) && $data['standard_id']) {
                updateStandard($data);
            } else {
                createStandard($data);
            }
            break;
            
        default:
            jsonResponse(false, 'Method not allowed', [], 405);
    }
} catch (Exception $e) {
    error_log('Standards API Error: ' . $e->getMessage());
    jsonResponse(false, 'Server error occurred', [], 500);
}

/**
 * Get all standards with filtering
 */
function listStandards(): void {
    $conn = getDBConnection();
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : '';
    
    $sql = "SELECT * FROM qa_standards WHERE 1=1";
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
    
    $sql .= " ORDER BY standard_id DESC";
    
    $stmt = $conn->prepare($sql);
    if ($types && count($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $standards = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    jsonResponse(true, 'Standards retrieved', ['data' => $standards]);
}

/**
 * Get single standard by ID
 */
function getStandardById(int $id): void {
    if ($id <= 0) {
        jsonResponse(false, 'Invalid standard ID');
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM qa_standards WHERE standard_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $standard = $result->fetch_assoc();
    $stmt->close();
    
    if ($standard) {
        jsonResponse(true, 'Standard found', ['data' => $standard]);
    } else {
        jsonResponse(false, 'Standard not found');
    }
}

/**
 * Create a new standard
 */
function createStandard(array $data): void {
    $errors = validateStandardData($data);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $conn = getDBConnection();
    
    $title = $data['title'];
    $body = $data['body'];
    $description = $data['description'] ?? '';
    $version = $data['version'] ?? '';
    $effective_date = !empty($data['effective_date']) ? $data['effective_date'] : null;
    $status = $data['status'] ?? 'Active';
    
    $stmt = $conn->prepare("
        INSERT INTO qa_standards (title, body, description, version, effective_date, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssss', $title, $body, $description, $version, $effective_date, $status);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Standard created successfully', ['standard_id' => $id]);
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to create standard');
    }
}

/**
 * Update an existing standard
 */
function updateStandard(array $data): void {
    $id = $data['standard_id'] ?? 0;
    if ($id <= 0) {
        jsonResponse(false, 'Invalid standard ID');
    }
    
    $errors = validateStandardData($data, true);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $conn = getDBConnection();
    
    $title = $data['title'];
    $body = $data['body'];
    $description = $data['description'] ?? '';
    $version = $data['version'] ?? '';
    $effective_date = !empty($data['effective_date']) ? $data['effective_date'] : null;
    $status = $data['status'] ?? 'Active';
    
    $stmt = $conn->prepare("
        UPDATE qa_standards 
        SET title = ?, body = ?, description = ?, version = ?, effective_date = ?, status = ?
        WHERE standard_id = ?
    ");
    $stmt->bind_param('ssssssi', $title, $body, $description, $version, $effective_date, $status, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Standard updated successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to update standard');
    }
}

/**
 * Delete a standard
 */
function deleteStandard(int $id): void {
    if ($id <= 0) {
        jsonResponse(false, 'Invalid standard ID');
    }
    
    $conn = getDBConnection();
    
    // Check if standard has related policies
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM qa_policies WHERE standard_id = ?");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($row['count'] > 0) {
        jsonResponse(false, 'Cannot delete: This standard has associated policies. Archive it instead.');
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM qa_standards WHERE standard_id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Standard deleted successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to delete standard');
    }
}

/**
 * Validate standard data
 */
function validateStandardData(array $data, bool $isUpdate = false): array {
    $errors = [];
    
    if (empty($data['title']) || trim($data['title']) === '') {
        $errors['title'] = 'Title is required';
    } elseif (strlen($data['title']) > 150) {
        $errors['title'] = 'Title must not exceed 150 characters';
    }
    
    $allowedBodies = ['CHED', 'ISO', 'Institutional', 'Other'];
    if (empty($data['body']) || !in_array($data['body'], $allowedBodies)) {
        $errors['body'] = 'Please select a valid body/type';
    }
    
    if (!empty($data['version']) && strlen($data['version']) > 20) {
        $errors['version'] = 'Version must not exceed 20 characters';
    }
    
    if (!empty($data['effective_date']) && !validateDate($data['effective_date'])) {
        $errors['effective_date'] = 'Please enter a valid date';
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