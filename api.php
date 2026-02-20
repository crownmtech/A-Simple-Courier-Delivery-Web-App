<?php
require_once 'config.php';

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request method and URI
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Remove query string
$request_uri = strtok($request_uri, '?');

// Get endpoint from URL path
$endpoint = '';
if (strpos($request_uri, '/api.php/') !== false) {
    $endpoint = substr($request_uri, strpos($request_uri, '/api.php/') + 9);
} elseif (strpos($request_uri, '/api/') !== false) {
    $endpoint = substr($request_uri, strpos($request_uri, '/api/') + 5);
} elseif (isset($_GET['endpoint'])) {
    $endpoint = $_GET['endpoint'];
}

// Debug logging
error_log("API Request: Method=$request_method, URI=$request_uri, Endpoint=$endpoint");

// Route the request
switch ($request_method) {
    case 'GET':
        handleGetRequest($endpoint);
        break;
    case 'POST':
        handlePostRequest($endpoint);
        break;
    case 'PUT':
        handlePutRequest($endpoint);
        break;
    case 'DELETE':
        handleDeleteRequest($endpoint);
        break;
    default:
        sendResponse(false, null, "Method not allowed", 405);
}

function handleGetRequest($endpoint) {
    // Parse query parameters
    $query_params = $_GET;
    
    switch ($endpoint) {
        case 'parcels':
            if (isset($query_params['tracking_number'])) {
                getParcelById($query_params['tracking_number']);
            } else {
                getParcels();
            }
            break;
        case 'track':
            if (isset($query_params['tracking_number'])) {
                trackParcel($query_params['tracking_number']);
            } else {
                sendResponse(false, null, "Tracking number is required", 400);
            }
            break;
        case 'stats':
            getStats();
            break;
        case 'distributors':
            getDistributors();
            break;
        case 'sms-settings':
            getSMSSettings();
            break;
        case 'sms-logs':
            getSMSLogs($query_params);
            break;
        case '':
        case 'test':
            // Root endpoint
            sendResponse(true, [
                'message' => 'CrownCourier API v1.0',
                'endpoint' => $endpoint,
                'timestamp' => date('Y-m-d H:i:s')
            ], "API is running");
            break;
        default:
            sendResponse(false, null, "Invalid endpoint: $endpoint", 404);
    }
}

function handlePostRequest($endpoint) {
    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // If JSON decode fails, try form data
    if ($data === null && !empty($_POST)) {
        $data = $_POST;
    }
    
    switch ($endpoint) {
        case 'parcels':
            createParcel($data);
            break;
        case 'tracking':
            addTrackingHistory($data);
            break;
        case 'distributors':
            createDistributor($data);
            break;
        case 'send-sms':
            sendSMS($data);
            break;
        case 'sms-settings':
            saveSMSSettings($data);
            break;
        case 'login':
            login($data);
            break;
        default:
            sendResponse(false, null, "Invalid endpoint: $endpoint", 404);
    }
}

function handlePutRequest($endpoint) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    switch ($endpoint) {
        case 'parcels':
            if (isset($_GET['action']) && $_GET['action'] === 'assign-distributor') {
                assignDistributor($data);
            } else {
                updateParcel($data);
            }
            break;
        case 'distributors':
            updateDistributor($data);
            break;
        default:
            sendResponse(false, null, "Invalid endpoint: $endpoint", 404);
    }
}

function handleDeleteRequest($endpoint) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    switch ($endpoint) {
        case 'parcels':
            deleteParcel($_GET);
            break;
        case 'distributors':
            deleteDistributor($data);
            break;
        default:
            sendResponse(false, null, "Invalid endpoint: $endpoint", 404);
    }
}

// Helper Functions
function executeQuery($sql, $params = [], $types = "") {
    $conn = getConnection();
    
    if (!$conn) {
        return ["success" => false, "error" => "Database connection failed"];
    }
    
    try {
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $error = $conn->error;
            $conn->close();
            return ["success" => false, "error" => "Prepare failed: " . $error];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        
        // Check if this is a SELECT query
        if (strpos(strtoupper($sql), 'SELECT') === 0) {
            $result = $stmt->get_result();
            $data = [];
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            return ["success" => true, "data" => $data];
        } else {
            $affected_rows = $stmt->affected_rows;
            $insert_id = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            return [
                "success" => true, 
                "affected_rows" => $affected_rows,
                "insert_id" => $insert_id
            ];
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        if (isset($conn) && $conn) {
            $conn->close();
        }
        return ["success" => false, "error" => $e->getMessage()];
    }
}

function sendResponse($success, $data = null, $message = "", $http_code = 200) {
    http_response_code($http_code);
    
    $response = [
        "success" => $success,
        "message" => $message,
        "data" => $data
    ];
    
    echo json_encode($response);
    exit;
}

// API Functions
function getParcels() {
    $sql = "SELECT p.*, d.name as distributor_name, d.phone_number as distributor_phone 
            FROM parcels p 
            LEFT JOIN distributors d ON p.distributor_id = d.id 
            ORDER BY p.created_at DESC";
    $result = executeQuery($sql);
    
    if ($result["success"]) {
        sendResponse(true, $result["data"]);
    } else {
        sendResponse(false, null, "Failed to fetch parcels: " . ($result["error"] ?? "Unknown error"));
    }
}

function getParcelById($tracking_number) {
    if (empty($tracking_number)) {
        sendResponse(false, null, "Tracking number is required", 400);
    }
    
    $sql = "SELECT p.*, d.name as distributor_name, d.phone_number as distributor_phone 
            FROM parcels p 
            LEFT JOIN distributors d ON p.distributor_id = d.id 
            WHERE p.tracking_number = ?";
    $result = executeQuery($sql, [$tracking_number], "s");
    
    if ($result["success"]) {
        if (empty($result["data"])) {
            sendResponse(false, null, "Parcel not found", 404);
        } else {
            sendResponse(true, $result["data"][0]);
        }
    } else {
        sendResponse(false, null, "Failed to fetch parcel: " . ($result["error"] ?? "Unknown error"));
    }
}

function trackParcel($tracking_number) {
    if (empty($tracking_number)) {
        sendResponse(false, null, "Tracking number is required", 400);
    }
    
    // Get parcel info
    $sql1 = "SELECT p.*, d.name as distributor_name, d.phone_number as distributor_phone 
            FROM parcels p 
            LEFT JOIN distributors d ON p.distributor_id = d.id 
            WHERE p.tracking_number = ?";
    $parcel = executeQuery($sql1, [$tracking_number], "s");
    
    if (!$parcel["success"]) {
        sendResponse(false, null, "Database error: " . ($parcel["error"] ?? "Unknown error"));
    }
    
    if (empty($parcel["data"])) {
        sendResponse(false, null, "Tracking number not found", 404);
    }
    
    // Get tracking history
    $sql2 = "SELECT * FROM tracking_history WHERE tracking_number = ? ORDER BY timestamp DESC";
    $history = executeQuery($sql2, [$tracking_number], "s");
    
    $response = [
        "parcel" => $parcel["data"][0],
        "history" => $history["success"] ? $history["data"] : []
    ];
    
    sendResponse(true, $response);
}

function getStats() {
    $sql = "SELECT 
        COUNT(*) as total_parcels,
        SUM(CASE WHEN status = 'In Transit' THEN 1 ELSE 0 END) as in_transit,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_parcels,
        (SELECT COUNT(*) FROM distributors WHERE is_active = 1) as active_distributors
    FROM parcels";
    
    $result = executeQuery($sql);
    
    if ($result["success"]) {
        sendResponse(true, $result["data"][0]);
    } else {
        sendResponse(false, null, "Failed to fetch stats: " . ($result["error"] ?? "Unknown error"));
    }
}

function createParcel($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    $required = ['recipient_name', 'address', 'origin', 'destination'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(false, null, "$field is required", 400);
        }
    }
    
    $tracking_number = generateTrackingNumber();
    
    $sql = "INSERT INTO parcels (tracking_number, recipient_name, recipient_phone, address, weight, dimensions, origin, destination, status, distributor_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $tracking_number,
        $data['recipient_name'] ?? '',
        $data['recipient_phone'] ?? '',
        $data['address'] ?? '',
        $data['weight'] ?? 0,
        $data['dimensions'] ?? '',
        $data['origin'] ?? '',
        $data['destination'] ?? '',
        $data['status'] ?? 'Processing',
        $data['distributor_id'] ?? null
    ];
    
    $result = executeQuery($sql, $params, "ssssdssssi");
    
    if ($result["success"] && $result["affected_rows"] > 0) {
        // Add initial tracking history
        $history_sql = "INSERT INTO tracking_history (tracking_number, status, description, location) 
                       VALUES (?, ?, ?, ?)";
        
        $history_desc = $data['initial_description'] ?? 'Parcel registered in system';
        $history_params = [
            $tracking_number,
            $data['status'] ?? 'Processing',
            $history_desc,
            $data['origin'] ?? ''
        ];
        
        executeQuery($history_sql, $history_params, "ssss");
        
        sendResponse(true, ["tracking_number" => $tracking_number], "Parcel created successfully", 201);
    } else {
        sendResponse(false, null, "Failed to create parcel: " . ($result["error"] ?? "Unknown error"));
    }
}

function updateParcel($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    if (empty($data['tracking_number'])) {
        sendResponse(false, null, "Tracking number is required", 400);
    }
    
    // First check if parcel exists
    $check_sql = "SELECT id FROM parcels WHERE tracking_number = ?";
    $check = executeQuery($check_sql, [$data['tracking_number']], "s");
    
    if (!$check["success"]) {
        sendResponse(false, null, "Database error: " . ($check["error"] ?? "Unknown error"));
    }
    
    if (empty($check["data"])) {
        sendResponse(false, null, "Parcel not found", 404);
    }
    
    $fields = [];
    $params = [];
    $types = "";
    
    $allowed_fields = ['recipient_name', 'recipient_phone', 'address', 'weight', 'dimensions', 'origin', 'destination', 'status', 'distributor_id'];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            // Handle null distributor_id
            if ($field == 'distributor_id') {
                if ($data[$field] === null || $data[$field] === '' || $data[$field] === 'null') {
                    $fields[] = "$field = NULL";
                } else {
                    $fields[] = "$field = ?";
                    $params[] = intval($data[$field]);
                    $types .= "i";
                }
            } else {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= $field == 'weight' ? "d" : "s";
            }
        }
    }
    
    if (empty($fields)) {
        sendResponse(false, null, "No fields to update", 400);
    }
    
    // Add updated_at timestamp
    $fields[] = "updated_at = NOW()";
    
    $params[] = $data['tracking_number'];
    $types .= "s";
    
    $sql = "UPDATE parcels SET " . implode(', ', $fields) . " WHERE tracking_number = ?";
    $result = executeQuery($sql, $params, $types);
    
    if ($result["success"]) {
        // Add tracking history if status changed and description provided
        if (isset($data['status']) && isset($data['status_description']) && !empty($data['status_description'])) {
            $history_sql = "INSERT INTO tracking_history (tracking_number, status, description, location) 
                           VALUES (?, ?, ?, ?)";
            
            $history_params = [
                $data['tracking_number'],
                $data['status'],
                $data['status_description'],
                $data['location'] ?? $data['destination'] ?? ''
            ];
            
            executeQuery($history_sql, $history_params, "ssss");
        }
        
        sendResponse(true, null, "Parcel updated successfully");
    } else {
        sendResponse(false, null, "Failed to update parcel: " . ($result["error"] ?? "Unknown error"));
    }
}

function assignDistributor($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    if (empty($data['tracking_number']) || empty($data['distributor_id'])) {
        sendResponse(false, null, "Tracking number and distributor ID are required", 400);
    }
    
    $sql = "UPDATE parcels SET distributor_id = ?, updated_at = NOW() WHERE tracking_number = ?";
    $result = executeQuery($sql, [$data['distributor_id'], $data['tracking_number']], "is");
    
    if ($result["success"] && $result["affected_rows"] > 0) {
        // Add tracking history
        $history_sql = "INSERT INTO tracking_history (tracking_number, status, description, location) 
                       VALUES (?, ?, ?, ?)";
        
        $history_params = [
            $data['tracking_number'],
            'Distributor Assigned',
            'Distributor assigned to parcel for delivery',
            ''
        ];
        
        executeQuery($history_sql, $history_params, "ssss");
        
        sendResponse(true, null, "Distributor assigned successfully");
    } else {
        sendResponse(false, null, "Failed to assign distributor: " . ($result["error"] ?? "Unknown error"));
    }
}

function addTrackingHistory($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    $required = ['tracking_number', 'status', 'description'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(false, null, "$field is required", 400);
        }
    }
    
    // Check if parcel exists
    $check_sql = "SELECT id FROM parcels WHERE tracking_number = ?";
    $check = executeQuery($check_sql, [$data['tracking_number']], "s");
    
    if (!$check["success"]) {
        sendResponse(false, null, "Database error: " . ($check["error"] ?? "Unknown error"));
    }
    
    if (empty($check["data"])) {
        sendResponse(false, null, "Parcel not found", 404);
    }
    
    // Update parcel status
    $update_sql = "UPDATE parcels SET status = ? WHERE tracking_number = ?";
    $update_result = executeQuery($update_sql, [$data['status'], $data['tracking_number']], "ss");
    
    if (!$update_result["success"]) {
        sendResponse(false, null, "Failed to update parcel status: " . ($update_result["error"] ?? "Unknown error"));
    }
    
    // Add tracking history
    $sql = "INSERT INTO tracking_history (tracking_number, status, description, location) 
            VALUES (?, ?, ?, ?)";
    
    $params = [
        $data['tracking_number'],
        $data['status'],
        $data['description'],
        $data['location'] ?? ''
    ];
    
    $result = executeQuery($sql, $params, "ssss");
    
    if ($result["success"]) {
        sendResponse(true, null, "Tracking history added successfully");
    } else {
        sendResponse(false, null, "Failed to add tracking history: " . ($result["error"] ?? "Unknown error"));
    }
}

function deleteParcel($params) {
    $tracking_number = $params['tracking_number'] ?? '';
    
    if (empty($tracking_number)) {
        sendResponse(false, null, "Tracking number is required", 400);
    }
    
    $sql = "DELETE FROM parcels WHERE tracking_number = ?";
    $result = executeQuery($sql, [$tracking_number], "s");
    
    if ($result["success"] && $result["affected_rows"] > 0) {
        sendResponse(true, null, "Parcel deleted successfully");
    } else {
        sendResponse(false, null, "Failed to delete parcel: " . ($result["error"] ?? "Unknown error"));
    }
}

function getDistributors() {
    $sql = "SELECT * FROM distributors WHERE is_active = 1 ORDER BY name";
    $result = executeQuery($sql);
    
    if ($result["success"]) {
        sendResponse(true, $result["data"]);
    } else {
        sendResponse(false, null, "Failed to fetch distributors: " . ($result["error"] ?? "Unknown error"));
    }
}

function createDistributor($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    $required = ['name', 'phone_number', 'country', 'state', 'lga'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(false, null, "$field is required", 400);
        }
    }
    
    $sql = "INSERT INTO distributors (name, phone_number, country, state, lga, is_active) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['name'],
        $data['phone_number'],
        $data['country'],
        $data['state'],
        $data['lga'],
        $data['is_active'] ?? 1
    ];
    
    $result = executeQuery($sql, $params, "sssssi");
    
    if ($result["success"] && $result["affected_rows"] > 0) {
        sendResponse(true, ["id" => $result["insert_id"]], "Distributor created successfully", 201);
    } else {
        sendResponse(false, null, "Failed to create distributor: " . ($result["error"] ?? "Unknown error"));
    }
}

function updateDistributor($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    if (empty($data['id'])) {
        sendResponse(false, null, "Distributor ID is required", 400);
    }
    
    $fields = [];
    $params = [];
    $types = "";
    
    $allowed_fields = ['name', 'phone_number', 'country', 'state', 'lga', 'is_active'];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
            $types .= $field == 'is_active' ? "i" : "s";
        }
    }
    
    if (empty($fields)) {
        sendResponse(false, null, "No fields to update", 400);
    }
    
    $params[] = $data['id'];
    $types .= "i";
    
    $sql = "UPDATE distributors SET " . implode(', ', $fields) . " WHERE id = ?";
    $result = executeQuery($sql, $params, $types);
    
    if ($result["success"] && $result["affected_rows"] > 0) {
        sendResponse(true, null, "Distributor updated successfully");
    } else {
        sendResponse(false, null, "Failed to update distributor: " . ($result["error"] ?? "Unknown error"));
    }
}

function deleteDistributor($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    if (empty($data['id'])) {
        sendResponse(false, null, "Distributor ID is required", 400);
    }
    
    $sql = "DELETE FROM distributors WHERE id = ?";
    $result = executeQuery($sql, [$data['id']], "i");
    
    if ($result["success"] && $result["affected_rows"] > 0) {
        sendResponse(true, null, "Distributor deleted successfully");
    } else {
        sendResponse(false, null, "Failed to delete distributor: " . ($result["error"] ?? "Unknown error"));
    }
}

function sendSMS($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    $required = ['tracking_number', 'distributor_id'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(false, null, "$field is required", 400);
        }
    }
    
    // Get parcel details
    $parcel_sql = "SELECT p.*, d.name as distributor_name, d.phone_number as distributor_phone 
                  FROM parcels p 
                  LEFT JOIN distributors d ON p.distributor_id = d.id 
                  WHERE p.tracking_number = ?";
    $parcel_result = executeQuery($parcel_sql, [$data['tracking_number']], "s");
    
    if (!$parcel_result["success"]) {
        sendResponse(false, null, "Database error: " . ($parcel_result["error"] ?? "Unknown error"));
    }
    
    if (empty($parcel_result["data"])) {
        sendResponse(false, null, "Parcel not found", 404);
    }
    
    $parcel = $parcel_result["data"][0];
    
    // Get selected distributor
    $dist_sql = "SELECT * FROM distributors WHERE id = ?";
    $dist_result = executeQuery($dist_sql, [$data['distributor_id']], "i");
    
    if (!$dist_result["success"]) {
        sendResponse(false, null, "Database error: " . ($dist_result["error"] ?? "Unknown error"));
    }
    
    if (empty($dist_result["data"])) {
        sendResponse(false, null, "Distributor not found", 404);
    }
    
    $distributor = $dist_result["data"][0];
    
    // Generate SMS message
    $sms_message = generateSMSMessage($parcel, $distributor);
    
    // Send SMS via ebulksms
    $sms_result = sendSMSviaAPI($parcel['recipient_phone'], $sms_message);
    
    // Log SMS
    $log_sql = "INSERT INTO sms_logs (tracking_number, recipient_phone, message, status, response) 
               VALUES (?, ?, ?, ?, ?)";
    
    $log_status = $sms_result['success'] ? 'sent' : 'failed';
    $log_response = json_encode($sms_result);
    
    $log_result = executeQuery($log_sql, [
        $data['tracking_number'],
        $parcel['recipient_phone'],
        $sms_message,
        $log_status,
        $log_response
    ], "sssss");
    
    if ($sms_result['success']) {
        sendResponse(true, $sms_result, "SMS sent successfully");
    } else {
        sendResponse(false, $sms_result, "Failed to send SMS: " . ($sms_result['message'] ?? "Unknown error"));
    }
}

function generateSMSMessage($parcel, $distributor) {
    $tracking_code = $parcel['tracking_number'];
    $status = $parcel['status'];
    
    switch($status) {
        case 'Processing':
            return "CTL: Your package $tracking_code is being processed. We'll notify you when it's ready for dispatch. Contact 08048619168 for any inquiries.";
            
        case 'In Transit':
            return "CTL: Your shipment $tracking_code is currently in transit. We'll update you on its progress. For inquiries: 08048619168";
            
        case 'Out for Delivery':
            $dist_name = $distributor['name'];
            $dist_phone = $distributor['phone_number'];
            return "CTL: Your package $tracking_code is scheduled for delivery today via $dist_name ($dist_phone). Do not pay extra for delivery. Contact 08048619168.";
            
        case 'Delivered':
            return "CTL: Your shipment $tracking_code has been successfully delivered. Any concern, please contact us via our hotline: 08048619168";
            
        case 'Arrived at Hub':
            return "CTL: Your package $tracking_code has arrived at our distribution hub. It will be dispatched for delivery soon. Contact: 08048619168";
            
        case 'Ready for Pickup':
            return "CTL: Your package $tracking_code is ready for pickup at our nearest collection point. Please bring valid ID. Contact: 08048619168";
            
        default:
            return "CTL: Your package $tracking_code is currently $status. For updates, contact 08048619168";
    }
}

function sendSMSviaAPI($phone, $message) {
    // Get SMS settings
    $settings_sql = "SELECT * FROM sms_settings LIMIT 1";
    $settings_result = executeQuery($settings_sql);
    
    if (!$settings_result["success"]) {
        return ['success' => false, 'message' => 'Database error fetching SMS settings'];
    }
    
    if (empty($settings_result["data"])) {
        return ['success' => false, 'message' => 'SMS settings not configured. Please configure your eBulkSMS credentials in SMS Settings.'];
    }
    
    $settings = $settings_result["data"][0];
    
    // Validate settings
    if (empty($settings['api_username']) || empty($settings['api_password'])) {
        return ['success' => false, 'message' => 'eBulkSMS credentials are missing. Please configure in SMS Settings.'];
    }
    
    // Clean phone number - eBulkSMS accepts Nigerian format without country code prefix
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to proper format for eBulkSMS
    // If starts with 234, remove it
    if (strpos($phone, '234') === 0) {
        $phone = '0' . substr($phone, 3);
    }
    // If doesn't start with 0, add it
    if (strpos($phone, '0') !== 0) {
        $phone = '0' . $phone;
    }
    
    // Validate phone number format (should be 11 digits starting with 0)
    if (strlen($phone) != 11 || strpos($phone, '0') !== 0) {
        return ['success' => false, 'message' => 'Invalid phone number format. Must be 11 digits starting with 0.'];
    }
    
    // Prepare ebulksms API request according to their JSON API documentation
    $username = $settings['api_username'];
    $apikey = $settings['api_password'];
    $sendername = $settings['sender_name'] ?? 'CTL';
    
    $data = array(
        'SMS' => array(
            'auth' => array(
                'username' => $username,
                'apikey' => $apikey
            ),
            'message' => array(
                'sender' => $sendername,
                'messagetext' => $message,
                'flash' => '0'
            ),
            'recipients' => array(
                'gsm' => array(
                    array(
                        'msidn' => $phone,
                        'msgid' => uniqid()
                    )
                )
            )
        )
    );
    
    $json_data = json_encode($data);
    
    // Log the request for debugging
    error_log("eBulkSMS Request to: " . $settings['api_url']);
    error_log("Phone: " . $phone);
    error_log("Sender: " . $sendername);
    
    // Send request to ebulksms
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $settings['api_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log response for debugging
    error_log("eBulkSMS HTTP Code: " . $httpcode);
    error_log("eBulkSMS Response: " . $response);
    
    // Handle cURL errors
    if ($curl_error) {
        return [
            'success' => false, 
            'message' => 'Connection error: ' . $curl_error,
            'http_code' => $httpcode
        ];
    }
    
    // Check HTTP response code
    if ($httpcode != 200) {
        return [
            'success' => false, 
            'message' => 'API returned error code ' . $httpcode,
            'response' => $response
        ];
    }
    
    // Parse JSON response
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false, 
            'message' => 'Invalid JSON response from eBulkSMS',
            'response' => $response
        ];
    }
    
    // Check eBulkSMS response format
    // Response format: {"response":{"status":"SUCCESS"|"FAILED"}}
    if (isset($result['response'])) {
        $status = strtoupper($result['response']['status'] ?? '');
        
        if ($status === 'SUCCESS') {
            return [
                'success' => true, 
                'message' => 'SMS sent successfully',
                'response' => $result
            ];
        } else {
            // Get error message if available
            $error_msg = 'SMS sending failed';
            if (isset($result['response']['message'])) {
                $error_msg .= ': ' . $result['response']['message'];
            }
            return [
                'success' => false, 
                'message' => $error_msg,
                'response' => $result
            ];
        }
    }
    
    // Unexpected response format
    return [
        'success' => false, 
        'message' => 'Unexpected response format from eBulkSMS',
        'response' => $result
    ];
}

function getSMSSettings() {
    $sql = "SELECT * FROM sms_settings LIMIT 1";
    $result = executeQuery($sql);
    
    if ($result["success"]) {
        $settings = empty($result["data"]) ? [] : $result["data"][0];
        // Don't expose password in response
        if (isset($settings['api_password'])) {
            $settings['api_password'] = '********';
        }
        sendResponse(true, $settings);
    } else {
        sendResponse(false, null, "Failed to fetch SMS settings: " . ($result["error"] ?? "Unknown error"));
    }
}

function saveSMSSettings($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    $required = ['api_username', 'api_password', 'sender_name'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(false, null, "$field is required", 400);
        }
    }
    
    // Check if settings exist
    $check_sql = "SELECT id FROM sms_settings LIMIT 1";
    $check_result = executeQuery($check_sql);
    
    if ($check_result["success"] && !empty($check_result["data"])) {
        // Update existing
        $sql = "UPDATE sms_settings SET 
                api_username = ?, 
                api_password = ?, 
                sender_name = ?, 
                api_url = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $data['api_username'],
            $data['api_password'],
            $data['sender_name'],
            $data['api_url'] ?? 'http://api.ebulksms.com:8080/sendsms.json',
            $check_result["data"][0]['id']
        ];
        
        $result = executeQuery($sql, $params, "ssssi");
    } else {
        // Insert new
        $sql = "INSERT INTO sms_settings (api_username, api_password, sender_name, api_url) 
                VALUES (?, ?, ?, ?)";
        
        $params = [
            $data['api_username'],
            $data['api_password'],
            $data['sender_name'],
            $data['api_url'] ?? 'http://api.ebulksms.com:8080/sendsms.json'
        ];
        
        $result = executeQuery($sql, $params, "ssss");
    }
    
    if ($result["success"] && $result["affected_rows"] > 0) {
        sendResponse(true, null, "SMS settings saved successfully");
    } else {
        sendResponse(false, null, "Failed to save SMS settings: " . ($result["error"] ?? "Unknown error"));
    }
}

function getSMSLogs($params) {
    $limit = $params['limit'] ?? 50;
    $tracking_number = $params['tracking_number'] ?? '';
    
    if (!empty($tracking_number)) {
        $sql = "SELECT * FROM sms_logs WHERE tracking_number = ? ORDER BY sent_at DESC LIMIT ?";
        $result = executeQuery($sql, [$tracking_number, $limit], "si");
    } else {
        $sql = "SELECT * FROM sms_logs ORDER BY sent_at DESC LIMIT ?";
        $result = executeQuery($sql, [$limit], "i");
    }
    
    if ($result["success"]) {
        sendResponse(true, $result["data"]);
    } else {
        sendResponse(false, null, "Failed to fetch SMS logs: " . ($result["error"] ?? "Unknown error"));
    }
}

function login($data) {
    if (empty($data)) {
        sendResponse(false, null, "No data provided", 400);
    }
    
    if (empty($data['username']) || empty($data['password'])) {
        sendResponse(false, null, "Username and password are required", 400);
    }
    
    $sql = "SELECT * FROM users WHERE username = ?";
    $result = executeQuery($sql, [$data['username']], "s");
    
    if (!$result["success"]) {
        sendResponse(false, null, "Database error: " . ($result["error"] ?? "Unknown error"));
    }
    
    if (empty($result["data"])) {
        sendResponse(false, null, "Invalid username or password");
    }
    
    $user = $result["data"][0];
    
    // Verify password (using password_verify for hashed passwords)
    if (password_verify($data['password'], $user['password_hash'])) {
        $response = [
            "success" => true,
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ];
        sendResponse(true, $response, "Login successful");
    } else {
        sendResponse(false, null, "Invalid username or password");
    }
}

function generateTrackingNumber() {
    $prefix = 'SC';
    $year = date('Y');
    $random = sprintf('%06d', mt_rand(1, 999999));
    return $prefix . $year . $random;
}

// If we reach here with no endpoint matched
sendResponse(false, null, "Invalid endpoint", 404);
?>