<?php
/**
 * KPI Indicators API
 * RESTful endpoints for qa_indicators table
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

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            getIndicatorById($id);
        } else {
            getAllIndicators();
        }
        break;
    case 'POST':
        createIndicator();
        break;
    case 'PUT':
        if ($id) {
            updateIndicator($id);
        } else {
            jsonResponse(false, 'Indicator ID is required for update', [], 400);
        }
        break;
    case 'DELETE':
        if ($id) {
            deleteIndicator($id);
        } else {
            jsonResponse(false, 'Indicator ID is required for deletion', [], 400);
        }
        break;
    default:
        jsonResponse(false, 'Method not allowed', [], 405);
}

function getAllIndicators() {
    $sql = "SELECT * FROM qa_indicators ORDER BY indicator_id DESC";
    $result = dbFetchAll($sql);
    
    jsonResponse(true, 'Indicators retrieved successfully', ['data' => $result]);
}

function getIndicatorById($id) {
    $sql = "SELECT * FROM qa_indicators WHERE indicator_id = ?";
    $result = dbFetchOne($sql, 'i', [$id]);
    
    if ($result) {
        jsonResponse(true, 'Indicator found', ['data' => $result]);
    } else {
        jsonResponse(false, 'Indicator not found', [], 404);
    }
}

function createIndicator() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(false, 'Invalid input data', [], 400);
    }
    
    $errors = validateRequired(['name', 'category', 'unit', 'target_value'], $input);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors], 400);
    }
    
    $name = sanitize($input['name']);
    $description = sanitize($input['description'] ?? '');
    $category = sanitize($input['category']);
    $unit = sanitize($input['unit']);
    $target_value = floatval($input['target_value']);
    $benchmark_source = sanitize($input['benchmark_source'] ?? '');
    
    $sql = "INSERT INTO qa_indicators (name, description, category, unit, target_value, benchmark_source) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $result = dbExecute($sql, 'ssssds', [$name, $description, $category, $unit, $target_value, $benchmark_source]);
    
    if ($result !== false) {
        jsonResponse(true, 'Indicator created successfully', ['indicator_id' => $result]);
    } else {
        jsonResponse(false, 'Failed to create indicator', [], 500);
    }
}

function updateIndicator($id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(false, 'Invalid input data', [], 400);
    }
    
    $name = sanitize($input['name'] ?? '');
    $description = sanitize($input['description'] ?? '');
    $category = sanitize($input['category'] ?? '');
    $unit = sanitize($input['unit'] ?? '');
    $target_value = isset($input['target_value']) ? floatval($input['target_value']) : null;
    $benchmark_source = sanitize($input['benchmark_source'] ?? '');
    
    $sql = "UPDATE qa_indicators SET 
            name = ?, description = ?, category = ?, unit = ?, 
            target_value = ?, benchmark_source = ? 
            WHERE indicator_id = ?";
    
    $result = dbExecute($sql, 'ssssdsi', [$name, $description, $category, $unit, $target_value, $benchmark_source, $id]);
    
    if ($result !== false) {
        jsonResponse(true, 'Indicator updated successfully');
    } else {
        jsonResponse(false, 'Failed to update indicator', [], 500);
    }
}

function deleteIndicator($id) {
     if ($id <= 0) {
        jsonResponse(false, 'Invalid KPI ID');
    }
    
    $conn = getDBConnection();
    
    // Check if standard has related policies
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM qa_kpi_records WHERE indicator_id = ?");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($row['count'] > 0) {
        jsonResponse(false, 'Cannot delete: This indicator has associated records. Delete it instead.');
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM qa_indicators WHERE indicator_id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'KPI deleted successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to delete KPI');
    }
}
?>