<?php
/**
 * Audit Management API
 * Quality Assurance Management System
 * backend/api/audit_api.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Start session for authentication check
session_start();

// Verify user is logged in
if (empty($_SESSION['logged_in'])) {
    jsonResponse(false, 'Unauthorized access. Please login.', [], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            jsonResponse(false, 'Invalid request method');
    }
} catch (Exception $e) {
    error_log('Audit API Error: ' . $e->getMessage());
    jsonResponse(false, 'An unexpected error occurred: ' . $e->getMessage());
}

/**
 * Handle GET requests
 */
function handleGetRequest($action) {
    switch ($action) {
        case 'get_all_audits':
            getAllAudits();
            break;
        case 'get_audit':
            getAuditById();
            break;
        case 'get_all_tasks':
            getAllTasks();
            break;
        case 'get_task':
            getTaskById();
            break;
        case 'get_audits_for_dropdown':
            getAuditsForDropdown();
            break;
        case 'get_tasks_by_audit':
            getTasksByAudit();
            break;
        default:
            jsonResponse(false, 'Invalid action specified');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($action) {
    switch ($action) {
        case 'create_audit':
            createAudit();
            break;
        case 'update_audit':
            updateAudit();
            break;
        case 'delete_audit':
            deleteAudit();
            break;
        case 'create_task':
            createTask();
            break;
        case 'update_task':
            updateTask();
            break;
        case 'delete_task':
            deleteTask();
            break;
        default:
            jsonResponse(false, 'Invalid action specified');
    }
}

/**
 * Get all audits with optional filtering
 */
function getAllAudits() {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM qa_audits ORDER BY scheduled_date DESC, audit_id DESC";
    $result = $conn->query($sql);
    
    $audits = [];
    while ($row = $result->fetch_assoc()) {
        $audits[] = $row;
    }
    
    jsonResponse(true, 'Audits retrieved successfully', ['data' => $audits]);
}

/**
 * Get single audit by ID
 */
function getAuditById() {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        jsonResponse(false, 'Audit ID is required');
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM qa_audits WHERE audit_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $audit = $result->fetch_assoc();
    
    if (!$audit) {
        jsonResponse(false, 'Audit not found');
    }
    
    jsonResponse(true, 'Audit retrieved successfully', ['data' => $audit]);
}

/**
 * Get all tasks with audit information
 */
function getAllTasks() {
    $conn = getDBConnection();
    
    $sql = "SELECT t.*, a.title as audit_title 
            FROM qa_accreditation_tasks t
            LEFT JOIN qa_audits a ON t.audit_id = a.audit_id
            ORDER BY t.due_date ASC, t.task_id DESC";
    
    $result = $conn->query($sql);
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    
    jsonResponse(true, 'Tasks retrieved successfully', ['data' => $tasks]);
}

/**
 * Get single task by ID
 */
function getTaskById() {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        jsonResponse(false, 'Task ID is required');
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM qa_accreditation_tasks WHERE task_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    
    if (!$task) {
        jsonResponse(false, 'Task not found');
    }
    
    jsonResponse(true, 'Task retrieved successfully', ['data' => $task]);
}

/**
 * Get audits for dropdown (only ID and title)
 */
function getAuditsForDropdown() {
    $conn = getDBConnection();
    
    $sql = "SELECT audit_id, title FROM qa_audits ORDER BY title";
    $result = $conn->query($sql);
    
    $audits = [];
    while ($row = $result->fetch_assoc()) {
        $audits[] = $row;
    }
    
    jsonResponse(true, 'Audits retrieved successfully', ['data' => $audits]);
}

/**
 * Get tasks by specific audit
 */
function getTasksByAudit() {
    $auditId = $_GET['audit_id'] ?? 0;
    
    if (!$auditId) {
        jsonResponse(false, 'Audit ID is required');
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM qa_accreditation_tasks WHERE audit_id = ? ORDER BY due_date ASC");
    $stmt->bind_param("i", $auditId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    
    jsonResponse(true, 'Tasks retrieved successfully', ['data' => $tasks]);
}

/**
 * Create new audit
 */
function createAudit() {
    // Validate required fields
    $errors = validateAuditData($_POST);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $title = trim($_POST['title']);
    $auditType = $_POST['audit_type'];
    $scheduledDate = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
    $completionDate = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    $status = $_POST['status'] ?? 'Scheduled';
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
    
    $conn = getDBConnection();
    
    $sql = "INSERT INTO qa_audits (title, audit_type, scheduled_date, completion_date, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $title, $auditType, $scheduledDate, $completionDate, $status, $notes);
    
    if ($stmt->execute()) {
        $auditId = $conn->insert_id;
        jsonResponse(true, 'Audit created successfully', ['audit_id' => $auditId]);
    } else {
        jsonResponse(false, 'Failed to create audit: ' . $conn->error);
    }
}

/**
 * Update existing audit
 */
function updateAudit() {
    $auditId = $_POST['audit_id'] ?? 0;
    
    if (!$auditId) {
        jsonResponse(false, 'Audit ID is required');
    }
    
    // Validate required fields
    $errors = validateAuditData($_POST);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $title = trim($_POST['title']);
    $auditType = $_POST['audit_type'];
    $scheduledDate = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
    $completionDate = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    $status = $_POST['status'] ?? 'Scheduled';
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
    
    $conn = getDBConnection();
    
    $sql = "UPDATE qa_audits 
            SET title = ?, audit_type = ?, scheduled_date = ?, completion_date = ?, status = ?, notes = ? 
            WHERE audit_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $title, $auditType, $scheduledDate, $completionDate, $status, $notes, $auditId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            jsonResponse(true, 'Audit updated successfully');
        } else {
            jsonResponse(true, 'No changes were made');
        }
    } else {
        jsonResponse(false, 'Failed to update audit: ' . $conn->error);
    }
}

/**
 * Delete audit and its associated tasks
 */
function deleteAudit() {
    $auditId = $_POST['id'] ?? 0;
    
    if (!$auditId) {
        jsonResponse(false, 'Audit ID is required');
    }
    
    $conn = getDBConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete associated tasks
        $stmt = $conn->prepare("DELETE FROM qa_accreditation_tasks WHERE audit_id = ?");
        $stmt->bind_param("i", $auditId);
        $stmt->execute();
        
        // Then delete the audit
        $stmt = $conn->prepare("DELETE FROM qa_audits WHERE audit_id = ?");
        $stmt->bind_param("i", $auditId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            jsonResponse(true, 'Audit and its tasks deleted successfully');
        } else {
            $conn->rollback();
            jsonResponse(false, 'Audit not found');
        }
    } catch (Exception $e) {
        $conn->rollback();
        jsonResponse(false, 'Failed to delete audit: ' . $e->getMessage());
    }
}

/**
 * Create new task
 */
function createTask() {
    // Validate required fields
    $errors = validateTaskData($_POST);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $auditId = $_POST['audit_id'];
    $title = trim($_POST['title']);
    $standardId = !empty($_POST['standard_id']) ? $_POST['standard_id'] : null;
    $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $status = $_POST['status'] ?? 'Pending';
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;
    
    $conn = getDBConnection();
    
    $sql = "INSERT INTO qa_accreditation_tasks (audit_id, standard_id, title, due_date, status, remarks) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", $auditId, $standardId, $title, $dueDate, $status, $remarks);
    
    if ($stmt->execute()) {
        $taskId = $conn->insert_id;
        jsonResponse(true, 'Task created successfully', ['task_id' => $taskId]);
    } else {
        jsonResponse(false, 'Failed to create task: ' . $conn->error);
    }
}

/**
 * Update existing task
 */
function updateTask() {
    $taskId = $_POST['task_id'] ?? 0;
    
    if (!$taskId) {
        jsonResponse(false, 'Task ID is required');
    }
    
    // Validate required fields
    $errors = validateTaskData($_POST);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }
    
    $auditId = $_POST['audit_id'];
    $title = trim($_POST['title']);
    $standardId = !empty($_POST['standard_id']) ? $_POST['standard_id'] : null;
    $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $status = $_POST['status'] ?? 'Pending';
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;
    
    $conn = getDBConnection();
    
    $sql = "UPDATE qa_accreditation_tasks 
            SET audit_id = ?, standard_id = ?, title = ?, due_date = ?, status = ?, remarks = ? 
            WHERE task_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssi", $auditId, $standardId, $title, $dueDate, $status, $remarks, $taskId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            jsonResponse(true, 'Task updated successfully');
        } else {
            jsonResponse(true, 'No changes were made');
        }
    } else {
        jsonResponse(false, 'Failed to update task: ' . $conn->error);
    }
}

/**
 * Delete task
 */
function deleteTask() {
    $taskId = $_POST['id'] ?? 0;
    
    if (!$taskId) {
        jsonResponse(false, 'Task ID is required');
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("DELETE FROM qa_accreditation_tasks WHERE task_id = ?");
    $stmt->bind_param("i", $taskId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            jsonResponse(true, 'Task deleted successfully');
        } else {
            jsonResponse(false, 'Task not found');
        }
    } else {
        jsonResponse(false, 'Failed to delete task: ' . $conn->error);
    }
}

/**
 * Validate audit data
 */
function validateAuditData($data) {
    $errors = [];
    
    if (empty(trim($data['title'] ?? ''))) {
        $errors['title'] = 'Title is required';
    } elseif (strlen(trim($data['title'])) > 150) {
        $errors['title'] = 'Title must not exceed 150 characters';
    }
    
    if (empty($data['audit_type'] ?? '')) {
        $errors['audit_type'] = 'Audit type is required';
    } elseif (!in_array($data['audit_type'], ['Internal', 'External', 'Accreditation'])) {
        $errors['audit_type'] = 'Invalid audit type';
    }
    
    // Validate dates
    if (!empty($data['scheduled_date']) && !empty($data['completion_date'])) {
        if (strtotime($data['completion_date']) < strtotime($data['scheduled_date'])) {
            $errors['completion_date'] = 'Completion date cannot be earlier than scheduled date';
        }
    }
    
    if (!empty($data['status']) && !in_array($data['status'], ['Scheduled', 'In Progress', 'Completed', 'Cancelled'])) {
        $errors['status'] = 'Invalid status value';
    }
    
    return $errors;
}

/**
 * Validate task data
 */
function validateTaskData($data) {
    $errors = [];
    
    if (empty($data['audit_id'] ?? '')) {
        $errors['audit_id'] = 'Audit selection is required';
    } elseif (!is_numeric($data['audit_id']) || $data['audit_id'] <= 0) {
        $errors['audit_id'] = 'Invalid audit selection';
    }
    
    if (empty(trim($data['title'] ?? ''))) {
        $errors['title'] = 'Task title is required';
    } elseif (strlen(trim($data['title'])) > 150) {
        $errors['title'] = 'Task title must not exceed 150 characters';
    }
    
    if (!empty($data['standard_id']) && (!is_numeric($data['standard_id']) || $data['standard_id'] <= 0)) {
        $errors['standard_id'] = 'Invalid standard ID';
    }
    
    if (!empty($data['due_date'])) {
        $dueDate = strtotime($data['due_date']);
        $today = strtotime(date('Y-m-d'));
        if ($dueDate < $today) {
            // Warning only, not error
        }
    }
    
    if (!empty($data['status']) && !in_array($data['status'], ['Pending', 'In Progress', 'Completed'])) {
        $errors['status'] = 'Invalid status value';
    }
    
    return $errors;
}
?>