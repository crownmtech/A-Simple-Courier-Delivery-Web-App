<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Get all parcels with distributor info
$conn = getConnection();
$parcels = [];
$distributors = [];

if ($conn) {
    // Get all parcels
    $sql = "SELECT p.*, d.name as distributor_name, d.phone_number as distributor_phone 
            FROM parcels p 
            LEFT JOIN distributors d ON p.distributor_id = d.id 
            ORDER BY p.created_at DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $parcels[] = $row;
        }
    }
    
    // Get all distributors
    $distributor_sql = "SELECT * FROM distributors WHERE is_active = 1 ORDER BY name";
    $distributor_result = $conn->query($distributor_sql);
    
    if ($distributor_result->num_rows > 0) {
        while($row = $distributor_result->fetch_assoc()) {
            $distributors[] = $row;
        }
    }
    
    $conn->close();
}

// Handle SMS sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_sms'])) {
    $tracking_number = $_POST['tracking_number'];
    $distributor_id = $_POST['distributor_id'];
    
    // Get parcel and distributor details
    $conn = getConnection();
    if ($conn) {
        // Get parcel details
        $parcel_sql = "SELECT p.*, d.name as distributor_name, d.phone_number as distributor_phone 
                      FROM parcels p 
                      LEFT JOIN distributors d ON p.distributor_id = d.id 
                      WHERE p.tracking_number = ?";
        $stmt = $conn->prepare($parcel_sql);
        $stmt->bind_param("s", $tracking_number);
        $stmt->execute();
        $parcel_result = $stmt->get_result();
        $parcel = $parcel_result->fetch_assoc();
        
        // Get selected distributor
        $dist_sql = "SELECT * FROM distributors WHERE id = ?";
        $dist_stmt = $conn->prepare($dist_sql);
        $dist_stmt->bind_param("i", $distributor_id);
        $dist_stmt->execute();
        $dist_result = $dist_stmt->get_result();
        $distributor = $dist_result->fetch_assoc();
        
        // Generate SMS message based on status
        $sms_message = generateSMSMessage($parcel, $distributor);
        
        // Send SMS
        $sms_result = sendSMS($parcel['recipient_phone'], $sms_message);
        
        // Log SMS
        $log_sql = "INSERT INTO sms_logs (tracking_number, recipient_phone, message, status, response) 
                   VALUES (?, ?, ?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_status = $sms_result['success'] ? 'sent' : 'failed';
        $log_response = json_encode($sms_result);
        $log_stmt->bind_param("sssss", 
            $tracking_number, 
            $parcel['recipient_phone'], 
            $sms_message, 
            $log_status, 
            $log_response
        );
        $log_stmt->execute();
        
        $conn->close();
        
        $sms_status = $sms_result['success'] ? 'success' : 'error';
        $sms_message_display = $sms_result['message'];
    }
}

// Function to generate SMS message
function generateSMSMessage($parcel, $distributor) {
    $tracking_code = $parcel['tracking_number'];
    $status = $parcel['status'];
    
    switch($status) {
        case 'Processing':
            return "CTL: Your package $tracking_code is being processed. We'll notify you when it's ready for dispatch. Contact 08048619168 for any inquiries.";
            
        case 'In Transit':
            return "CTL: Your shipment $tracking_code is currently in transit. We'll update you on its progress. For inquiries: 08048619168";
            
        case 'Out for Delivery':
            $dist_name = $distributor['name'] ?? 'our delivery agent';
            $dist_phone = $distributor['phone_number'] ?? '';
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

// Function to send SMS via ebulksms
function sendSMS($phone, $message) {
    // Get SMS settings from database
    $conn = getConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    $sql = "SELECT * FROM sms_settings LIMIT 1";
    $result = $conn->query($sql);
    $settings = $result->fetch_assoc();
    $conn->close();
    
    if (!$settings) {
        return ['success' => false, 'message' => 'SMS settings not configured'];
    }
    
    // Prepare SMS data
    $username = $settings['api_username'];
    $apikey = $settings['api_password'];
    $sendername = $settings['sender_name'];
    
    // Clean phone number
    $phone = '234' . substr($phone, 1);
    
    // Prepare request data
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
    
    // Send request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $settings['api_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode == 200) {
        $result = json_decode($response, true);
        if (isset($result['response']['status']) && $result['response']['status'] == 'success') {
            return ['success' => true, 'message' => 'SMS sent successfully'];
        }
    }
    
    return ['success' => false, 'message' => 'Failed to send SMS: ' . $response];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrownCourier Admin | Track Parcel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #1e293b;
            color: white;
            padding: 20px 0;
        }
        
        .logo {
            padding: 0 20px 30px 20px;
            border-bottom: 1px solid #374151;
        }
        
        .logo h1 {
            color: #e30613;
            font-size: 24px;
        }
        
        .logo span {
            color: white;
        }
        
        .user-info {
            padding: 20px;
            border-bottom: 1px solid #374151;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: #374151;
            color: white;
            border-left: 3px solid #e30613;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-x: auto;
        }
        
        .header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #6b7280;
        }
        
        .content-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .card-title {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #e30613;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #c10510;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #0da271;
        }
        
        .btn-info {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #2563eb;
        }
        
        .db-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-connected {
            background-color: #10b981;
            color: white;
        }
        
        .status-disconnected {
            background-color: #ef4444;
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background-color: #f8fafc;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table tr:hover {
            background-color: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-processing { background-color: #fbbf24; color: #78350f; }
        .status-in-transit { background-color: #3b82f6; color: white; }
        .status-delivered { background-color: #10b981; color: white; }
        .status-out-for-delivery { background-color: #8b5cf6; color: white; }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 300px;
            max-width: 100%;
        }
    </style>
</head>
<body>
    <?php
    // Test database connection
    $conn = getConnection();
    $db_connected = ($conn && $conn->ping());
    $conn->close();
    ?>
    
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <h1>CROWN<span>COURIER</span></h1>
                <p>Admin Panel</p>
                <span class="db-status <?php echo $db_connected ? 'status-connected' : 'status-disconnected'; ?>">
                    <?php echo $db_connected ? 'DB Connected' : 'DB Disconnected'; ?>
                </span>
            </div>
            
            <div class="user-info">
                <p>Welcome, <strong><?php echo $_SESSION['user']['username']; ?></strong></p>
                <small>Role: <?php echo $_SESSION['user']['role']; ?></small>
            </div>
            
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    📊 Dashboard
                </a>
                <a href="add-parcel.php" class="nav-item">
                    📦 Add Parcel
                </a>
                <a href="track-parcel.php" class="nav-item active">
                    🔍 Track Parcel
                </a>
                <a href="distributors.php" class="nav-item">
                    👤 Distributors
                </a>
                <a href="sms-settings.php" class="nav-item">
                    ⚙️ SMS Settings
                </a>
                <a href="logout.php" class="nav-item">
                    🚪 Logout
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Track Parcel</h1>
                <p>View all parcels and send SMS notifications to recipients</p>
            </div>
            
            <?php if (isset($sms_status)): ?>
                <div class="alert alert-<?php echo $sms_status; ?>">
                    <?php echo $sms_message_display; ?>
                </div>
            <?php endif; ?>
            
            <div class="content-card">
                <h2 class="card-title">All Parcels</h2>
                
                <div class="search-box">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by tracking number, recipient name, or phone..." onkeyup="searchTable()">
                </div>
                
                <div class="table-container">
                    <table class="data-table" id="parcelsTable">
                        <thead>
                            <tr>
                                <th>Tracking #</th>
                                <th>Recipient</th>
                                <th>Phone</th>
                                <th>Destination</th>
                                <th>Status</th>
                                <th>Distributor</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcels as $parcel): ?>
                                <?php
                                $status_class = '';
                                switch($parcel['status']) {
                                    case 'Processing': $status_class = 'status-processing'; break;
                                    case 'In Transit': $status_class = 'status-in-transit'; break;
                                    case 'Out for Delivery': $status_class = 'status-out-for-delivery'; break;
                                    case 'Delivered': $status_class = 'status-delivered'; break;
                                    default: $status_class = 'status-processing';
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo $parcel['tracking_number']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($parcel['recipient_name']); ?></td>
                                    <td><?php echo $parcel['recipient_phone']; ?></td>
                                    <td><?php echo $parcel['destination']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $parcel['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($parcel['distributor_name']): ?>
                                            <?php echo htmlspecialchars($parcel['distributor_name']); ?><br>
                                            <small><?php echo $parcel['distributor_phone']; ?></small>
                                        <?php else: ?>
                                            <em>Not assigned</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($parcel['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <button class="btn btn-sm btn-primary" onclick="viewDetails('<?php echo $parcel['tracking_number']; ?>')">
                                                View
                                            </button>
                                            <button class="btn btn-sm btn-success" onclick="openSMSModal('<?php echo $parcel['tracking_number']; ?>')">
                                                Send SMS
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SMS Modal -->
    <div id="smsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSMSModal()">&times;</span>
            <h2>Send SMS Notification</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Tracking Number</label>
                    <input type="text" id="modalTrackingNumber" name="tracking_number" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Distributor</label>
                    <select name="distributor_id" class="form-control" required>
                        <option value="">-- Select Distributor --</option>
                        <?php foreach ($distributors as $distributor): ?>
                            <option value="<?php echo $distributor['id']; ?>">
                                <?php echo htmlspecialchars($distributor['name']); ?> - <?php echo $distributor['phone_number']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #6b7280;">Distributor details will be included in the SMS message</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Preview Message</label>
                    <textarea id="smsPreview" class="form-control" rows="4" readonly style="background-color: #f9fafb;"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="send_sms" class="btn btn-primary">
                        Send SMS
                    </button>
                    <button type="button" class="btn" onclick="closeSMSModal()" style="background-color: #6b7280; color: white;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <div id="detailsContent"></div>
        </div>
    </div>

    <script>
        // Search function
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('parcelsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const tdTracking = tr[i].getElementsByTagName('td')[0];
                const tdRecipient = tr[i].getElementsByTagName('td')[1];
                const tdPhone = tr[i].getElementsByTagName('td')[2];
                
                if (tdTracking && tdRecipient && tdPhone) {
                    const txtValue = tdTracking.textContent + tdRecipient.textContent + tdPhone.textContent;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
        
        // SMS Modal functions
        function openSMSModal(trackingNumber) {
            document.getElementById('modalTrackingNumber').value = trackingNumber;
            document.getElementById('smsModal').style.display = 'block';
            updateSMSPreview();
        }
        
        function closeSMSModal() {
            document.getElementById('smsModal').style.display = 'none';
        }
        
        function updateSMSPreview() {
            // In a real implementation, you would fetch the parcel details
            // and generate a preview based on the selected distributor
            const trackingNumber = document.getElementById('modalTrackingNumber').value;
            const distributorSelect = document.querySelector('select[name="distributor_id"]');
            const preview = document.getElementById('smsPreview');
            
            if (distributorSelect.value) {
                const selectedOption = distributorSelect.options[distributorSelect.selectedIndex];
                const distributorName = selectedOption.text.split(' - ')[0];
                
                preview.value = `CTL: Your package ${trackingNumber} is scheduled for delivery today via ${distributorName}. Do not pay extra for delivery. Contact 08048619168.`;
            } else {
                preview.value = `CTL: Your package ${trackingNumber} status update. Contact 08048619168 for details.`;
            }
        }
        
        // View parcel details
        function viewDetails(trackingNumber) {
            fetch(`api.php/track?tracking_number=${trackingNumber}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const parcel = data.data.parcel;
                        const history = data.data.history;
                        
                        let historyHTML = '';
                        if (history.length > 0) {
                            historyHTML = '<h3>Tracking History</h3><ul style="list-style: none; padding-left: 0;">';
                            history.forEach(entry => {
                                const date = new Date(entry.timestamp).toLocaleString();
                                historyHTML += `
                                    <li style="margin-bottom: 10px; padding: 10px; background-color: #f9fafb; border-left: 3px solid #e30613;">
                                        <strong>${date}</strong><br>
                                        <strong>Status:</strong> ${entry.status}<br>
                                        ${entry.description ? `<strong>Description:</strong> ${entry.description}<br>` : ''}
                                        ${entry.location ? `<strong>Location:</strong> ${entry.location}` : ''}
                                    </li>
                                `;
                            });
                            historyHTML += '</ul>';
                        }
                        
                        document.getElementById('detailsContent').innerHTML = `
                            <h2>Parcel Details</h2>
                            <div style="margin-bottom: 20px;">
                                <p><strong>Tracking Number:</strong> ${parcel.tracking_number}</p>
                                <p><strong>Recipient:</strong> ${parcel.recipient_name}</p>
                                <p><strong>Phone:</strong> ${parcel.recipient_phone}</p>
                                <p><strong>Address:</strong> ${parcel.address}</p>
                                <p><strong>Destination:</strong> ${parcel.destination}</p>
                                <p><strong>Status:</strong> <span class="status-badge">${parcel.status}</span></p>
                                <p><strong>Weight:</strong> ${parcel.weight} kg</p>
                                <p><strong>Created:</strong> ${new Date(parcel.created_at).toLocaleString()}</p>
                            </div>
                            ${historyHTML}
                        `;
                        
                        document.getElementById('detailsModal').style.display = 'block';
                    }
                })
                .catch(error => {
                    alert('Error loading details: ' + error.message);
                });
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const smsModal = document.getElementById('smsModal');
            const detailsModal = document.getElementById('detailsModal');
            
            if (event.target == smsModal) {
                smsModal.style.display = 'none';
            }
            if (event.target == detailsModal) {
                detailsModal.style.display = 'none';
            }
        }
        
        // Update SMS preview when distributor changes
        document.querySelector('select[name="distributor_id"]').addEventListener('change', updateSMSPreview);
    </script>
</body>
</html>