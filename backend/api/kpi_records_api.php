<?php
/**
 * KPI Records API
 * RESTful endpoints for qa_kpi_records table with external data import
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

// Check if this is a special action
if ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_external') {
    fetchExternalData();
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            getRecordById($id);
        } else {
            getAllRecords();
        }
        break;
    case 'POST':
        createRecord();
        break;
    case 'PUT':
        if ($id) {
            updateRecord($id);
        } else {
            jsonResponse(false, 'Record ID is required for update', [], 400);
        }
        break;
    case 'DELETE':
        if ($id) {
            deleteRecord($id);
        } else {
            jsonResponse(false, 'Record ID is required for deletion', [], 400);
        }
        break;
    default:
        jsonResponse(false, 'Method not allowed', [], 405);
}

function getAllRecords() {
    $year = isset($_GET['year']) ? sanitize($_GET['year']) : '';
    $term = isset($_GET['term']) ? sanitize($_GET['term']) : '';
    $indicator_id = isset($_GET['indicator_id']) ? (int)$_GET['indicator_id'] : 0;
    
    $sql = "SELECT r.*, i.name as indicator_name, i.unit as unit, i.target_value 
            FROM qa_kpi_records r 
            LEFT JOIN qa_indicators i ON r.indicator_id = i.indicator_id 
            WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($year) {
        $sql .= " AND r.school_year = ?";
        $params[] = $year;
        $types .= 's';
    }
    
    if ($term) {
        $sql .= " AND r.period_term = ?";
        $params[] = $term;
        $types .= 's';
    }
    
    if ($indicator_id > 0) {
        $sql .= " AND r.indicator_id = ?";
        $params[] = $indicator_id;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY r.school_year DESC, r.record_id DESC";
    
    $result = dbFetchAll($sql, $types, $params);
    
    jsonResponse(true, 'Records retrieved successfully', ['data' => $result]);
}

function getRecordById($id) {
    $sql = "SELECT r.*, i.name as indicator_name, i.unit as unit, i.target_value 
            FROM qa_kpi_records r 
            LEFT JOIN qa_indicators i ON r.indicator_id = i.indicator_id 
            WHERE r.record_id = ?";
    $result = dbFetchOne($sql, 'i', [$id]);
    
    if ($result) {
        jsonResponse(true, 'Record found', ['data' => $result]);
    } else {
        jsonResponse(false, 'Record not found', [], 404);
    }
}

function createRecord() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(false, 'Invalid input data', [], 400);
    }
    
    $errors = validateRequired(['indicator_id', 'school_year', 'period_term', 'actual_value'], $input);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors], 400);
    }
    
    $indicator_id = (int)$input['indicator_id'];
    $school_year = sanitize($input['school_year']);
    $period_term = sanitize($input['period_term']);
    $actual_value = floatval($input['actual_value']);
    $remarks = sanitize($input['remarks'] ?? '');
    
    // Check if record already exists for this indicator, year, term
    $checkSql = "SELECT COUNT(*) as count FROM qa_kpi_records 
                 WHERE indicator_id = ? AND school_year = ? AND period_term = ?";
    $checkResult = dbFetchOne($checkSql, 'iss', [$indicator_id, $school_year, $period_term]);
    
    if ($checkResult && $checkResult['count'] > 0) {
        jsonResponse(false, 'A record already exists for this indicator, year, and term', [], 400);
    }
    
    $sql = "INSERT INTO qa_kpi_records (indicator_id, school_year, period_term, actual_value, remarks) 
            VALUES (?, ?, ?, ?, ?)";
    
    $result = dbExecute($sql, 'issds', [$indicator_id, $school_year, $period_term, $actual_value, $remarks]);
    
    if ($result !== false) {
        jsonResponse(true, 'Record created successfully', ['record_id' => $result]);
    } else {
        jsonResponse(false, 'Failed to create record', [], 500);
    }
}

function updateRecord($id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(false, 'Invalid input data', [], 400);
    }
    
    $indicator_id = isset($input['indicator_id']) ? (int)$input['indicator_id'] : 0;
    $school_year = sanitize($input['school_year'] ?? '');
    $period_term = sanitize($input['period_term'] ?? '');
    $actual_value = isset($input['actual_value']) ? floatval($input['actual_value']) : 0;
    $remarks = sanitize($input['remarks'] ?? '');
    
    $sql = "UPDATE qa_kpi_records SET 
            indicator_id = ?, school_year = ?, period_term = ?, 
            actual_value = ?, remarks = ? 
            WHERE record_id = ?";
    
    $result = dbExecute($sql, 'issdsi', [$indicator_id, $school_year, $period_term, $actual_value, $remarks, $id]);
    
    if ($result !== false) {
        jsonResponse(true, 'Record updated successfully');
    } else {
        jsonResponse(false, 'Failed to update record', [], 500);
    }
}

function deleteRecord($id) {
    $sql = "DELETE FROM qa_kpi_records WHERE record_id = ?";
    $result = dbExecute($sql, 'i', [$id]);
    
    if ($result !== false) {
        jsonResponse(true, 'Record deleted successfully');
    } else {
        jsonResponse(false, 'Failed to delete record', [], 500);
    }
}

function fetchExternalData() {
    $source = isset($_POST['source']) ? sanitize($_POST['source']) : '';
    $indicator_id = isset($_POST['indicator_id']) ? (int)$_POST['indicator_id'] : 0;
    $year = isset($_POST['year']) ? sanitize($_POST['year']) : date('Y');
    $term = isset($_POST['term']) ? sanitize($_POST['term']) : '';
    
    if (!$source || !$indicator_id) {
        jsonResponse(false, 'Source and indicator are required', [], 400);
    }
    
    // Get indicator details to determine what data to fetch
    $indicatorSql = "SELECT * FROM qa_indicators WHERE indicator_id = ?";
    $indicator = dbFetchOne($indicatorSql, 'i', [$indicator_id]);
    
    if (!$indicator) {
        jsonResponse(false, 'Indicator not found', [], 404);
    }
    
    // Sample external data - in production, this would call actual APIs or query other tables
    $externalData = getSampleExternalData($source, $indicator, $year, $term);
    
    if ($externalData) {
        jsonResponse(true, 'External data retrieved successfully', ['data' => $externalData]);
    } else {
        jsonResponse(false, 'No data available for the selected criteria', [], 404);
    }
}

function getSampleExternalData($source, $indicator, $year, $term) {
    // This is sample data simulation
    // In production, replace with actual API calls or database queries
    
    $sampleDataFile = '../external_sample_data.json';
    if (file_exists($sampleDataFile)) {
        $sampleData = json_decode(file_get_contents($sampleDataFile), true);
        
        if ($source === 'lms' && isset($sampleData['lms'])) {
            foreach ($sampleData['lms'] as $data) {
                if ($data['year'] == $year && $data['term'] == $term) {
                    return [
                        'actual_value' => $data['value'],
                        'unit' => $data['unit'],
                        'year' => $year,
                        'term' => $term,
                        'remarks' => "LMS Import: {$data['description']}"
                    ];
                }
            }
        } elseif ($source === 'faculty_eval' && isset($sampleData['faculty_evaluation'])) {
            foreach ($sampleData['faculty_evaluation'] as $data) {
                if ($data['year'] == $year && $data['term'] == $term) {
                    return [
                        'actual_value' => $data['value'],
                        'unit' => $data['unit'],
                        'year' => $year,
                        'term' => $term,
                        'remarks' => "Faculty Evaluation Import: {$data['description']}"
                    ];
                }
            }
        }
    }
    
    // Fallback sample data
    return [
        'actual_value' => rand(70, 95),
        'unit' => $indicator['unit'] ?? 'Percentage (%)',
        'year' => $year,
        'term' => $term,
        'remarks' => "Imported from {$source} for {$year} {$term}"
    ];
}
?>