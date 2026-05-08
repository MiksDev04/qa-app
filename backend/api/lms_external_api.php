<?php
/**
 * LMS External API Handler
 * Fetches data from ArtisansLMS API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('LMS_API_URL', 'https://artisanslms.onrender.com/backend/api/export_student_performance.php');
define('LMS_API_KEY', '0fvBAvRhGAkES6QVHXYojIVDQq5iPiRl');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = isset($input['action']) ? $input['action'] : '';
$source = isset($input['source']) ? $input['source'] : '';
$year = isset($input['year']) ? $input['year'] : '';
$term = isset($input['term']) ? $input['term'] : '';

if ($action !== 'fetch_external') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

if ($source === 'lms') {
    fetchLMSData($year, $term);
} else if ($source === 'faculty_eval') {
    fetchFacultyEvalData($year, $term);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data source']);
}

function fetchLMSData($year, $term) {
    $url = LMS_API_URL . '?action=get_overview';
    
    if (!empty($year)) {
        $url .= '&year=' . urlencode($year);
    }
    
    if (!empty($term) && $term !== 'Annual') {
        $semester = str_replace(['st', 'nd', 'rd', 'th'], '', $term);
        $semester = trim($semester);
        $url .= '&semester=' . urlencode($semester);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . LMS_API_KEY,
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $curlError]);
        return;
    }
    
    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'message' => 'API returned HTTP code: ' . $httpCode]);
        return;
    }
    
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON response from LMS API']);
        return;
    }
    
    // Extract the data object from {status: 'success', data: {...}}
    if (isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
        $result = $data['data'];
    } else if (isset($data[0]) && is_array($data[0])) {
        $result = $data[0];
    } else {
        $result = $data;
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
}

function fetchFacultyEvalData($year, $term) {
    // Mock data - replace with actual faculty evaluation API
    $mockData = [
        'avg_grade' => 87.5,
        'submission_rate' => 11.8,
        'quiz_pass_rate' => 100,
        'quiz_attempts' => 1,
        'quiz_passed' => 1,
        'total_quizzes' => 2,
        'total_submitted' => 11,
        'total_tasks' => 11,
        'total_students' => 13,
        'total_expected' => 93,
        'total_classes' => 7,
        'avg_rating' => 4.2,
        'response_rate' => 85.5,
        'total_responses' => 342
    ];
    
    echo json_encode(['success' => true, 'data' => $mockData]);
}
?>