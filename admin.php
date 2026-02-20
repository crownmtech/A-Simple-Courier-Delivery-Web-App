<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Get all distributors for dropdown
$conn = getConnection();
$distributors = [];
if ($conn) {
    $sql = "SELECT * FROM distributors WHERE is_active = 1 ORDER BY name";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $distributors[] = $row;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrownCourier Admin Panel</title>
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
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 20px 30px 20px;
            border-bottom: 1px solid #374151;
        }
        
        .logo h1 {
            color: #e30613;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .logo span {
            color: white;
        }
        
        .logo p {
            color: #d1d5db;
            font-size: 14px;
            margin-bottom: 10px;
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
            cursor: pointer;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: #374151;
            color: white;
            border-left: 3px solid #e30613;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            width: calc(100% - 250px);
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
        
        .form-control:focus {
            outline: none;
            border-color: #e30613;
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
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
        
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #4b5563;
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
        
        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
        }
        
        .db-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-connected {
            background-color: #10b981;
            color: white;
        }
        
        .status-disconnected {
            background-color: #ef4444;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left-color: #10b981;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left-color: #ef4444;
        }
        
        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
            border-left-color: #f59e0b;
        }
        
        .alert-info {
            background-color: #e0f2fe;
            color: #075985;
            border-left-color: #0ea5e9;
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
        .status-pending { background-color: #6b7280; color: white; }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 300px;
            max-width: 100%;
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
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: #6b7280;
        }
        
        .close:hover {
            color: #374151;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
        }
        
        .stat-card h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .page-link {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            text-decoration: none;
            color: #374151;
            cursor: pointer;
        }
        
        .page-link.active {
            background-color: #e30613;
            color: white;
            border-color: #e30613;
        }
        
        .page-link:hover:not(.active) {
            background-color: #f3f4f6;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #10b981;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e5e7eb;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 30px;
            padding-left: 30px;
        }
        
        .timeline-dot {
            position: absolute;
            left: 0;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #e30613;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #f3f4f6;
        }
        
        .timeline-content {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #e30613;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .loading::after {
            content: '...';
            animation: dots 1.5s steps(4, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
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
                <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong></p>
                <small>Role: <?php echo htmlspecialchars($_SESSION['user']['role']); ?></small>
            </div>
            
            <div class="nav-menu">
                <a href="#" class="nav-item active" onclick="showTab('dashboard')">
                    📊 Dashboard
                </a>
                <a href="#" class="nav-item" onclick="showTab('add-parcel')">
                    📦 Add Parcel
                </a>
                <a href="#" class="nav-item" onclick="showTab('track-parcel')">
                    🔍 Track Parcel
                </a>
                <a href="#" class="nav-item" onclick="showTab('manage-parcels')">
                    📋 Manage Parcels
                </a>
                <a href="#" class="nav-item" onclick="showTab('distributors')">
                    👤 Distributors
                </a>
                <a href="#" class="nav-item" onclick="showTab('sms-settings')">
                    ⚙️ SMS Settings
                </a>
                <a href="logout.php" class="nav-item">
                    🚪 Logout
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>CrownCourier Parcel Management System</h1>
                <p>Database Status: <?php echo $db_connected ? '✅ Connected' : '❌ Disconnected'; ?></p>
            </div>
            
            <!-- Dashboard Tab -->
            <div class="tab-content active" id="dashboard">
                <div class="content-card">
                    <h2 class="card-title">Dashboard Overview</h2>
                    <div id="statsContainer" class="loading">
                        Loading statistics...
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="showTab('add-parcel')">
                            📦 Add New Parcel
                        </button>
                        <button class="btn btn-success" onclick="showTab('manage-parcels')">
                            📋 Manage Parcels
                        </button>
                        <button class="btn btn-info" onclick="showTab('distributors')">
                            👤 Manage Distributors
                        </button>
                        <button class="btn btn-warning" onclick="testConnection()">
                            🔄 Test Connection
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Add Parcel Tab -->
            <div class="tab-content" id="add-parcel">
                <div class="content-card">
                    <h2 class="card-title">Add New Parcel</h2>
                    <div id="addAlert"></div>
                    
                    <form id="parcelForm">
                        <div class="form-group">
                            <label class="form-label">Recipient Information</label>
                            <div class="form-row">
                                <div>
                                    <input type="text" class="form-control" id="recipientName" placeholder="Recipient Name" required>
                                </div>
                                <div>
                                    <input type="tel" class="form-control" id="recipientPhone" placeholder="Phone Number" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Address Details</label>
                            <textarea class="form-control" id="address" placeholder="Full Address" rows="2" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Parcel Details</label>
                            <div class="form-row">
                                <div>
                                    <input type="number" class="form-control" id="weight" placeholder="Weight (kg)" step="0.01" required>
                                </div>
                                <div>
                                    <input type="text" class="form-control" id="dimensions" placeholder="Dimensions (LxWxH)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Shipping Information</label>
                            <div class="form-row">
                                <div>
                                    <select class="form-control" id="origin" required>
                                        <option value="">Select Origin</option>
                                        <option value="China">China</option>
                                        <option value="USA">USA</option>
                                        <option value="UK">UK</option>
                                        <option value="Nigeria">Nigeria</option>
                                        <option value="South Africa">South Africa</option>
                                    </select>
                                </div>
                                <div>
                                    <select class="form-control" id="destination" required>
                                        <option value="">Select Destination</option>
                                        <option value="Nigeria">Nigeria</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Ghana">Ghana</option>
                                        <option value="Egypt">Egypt</option>
                                        <option value="South Africa">South Africa</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Assign Distributor</label>
                            <select class="form-control" id="distributor">
                                <option value="">-- Select Distributor (Optional) --</option>
                                <?php foreach ($distributors as $distributor): ?>
                                    <option value="<?php echo $distributor['id']; ?>">
                                        <?php echo htmlspecialchars($distributor['name']); ?> - <?php echo $distributor['phone_number']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Initial Status</label>
                            <select class="form-control" id="initialStatus">
                                <option value="Processing">Processing</option>
                                <option value="In Transit">In Transit</option>
                                <option value="Out for Delivery">Out for Delivery</option>
                                <option value="Delivered">Delivered</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Initial Status Description</label>
                            <textarea class="form-control" id="statusDescription" rows="3" placeholder="Enter status description (e.g., 'Parcel received at warehouse')"></textarea>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Add Parcel</button>
                            <button type="reset" class="btn btn-secondary">Clear Form</button>
                            <button type="button" class="btn btn-info" onclick="showTab('dashboard')">Back to Dashboard</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Track Parcel Tab -->
            <div class="tab-content" id="track-parcel">
                <div class="content-card">
                    <h2 class="card-title">Track Parcel</h2>
                    
                    <div class="search-box">
                        <div class="form-group">
                            <label class="form-label">Enter Tracking Number</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" class="form-control" id="trackingInput" placeholder="e.g., NG020827070029" style="flex: 1;">
                                <button class="btn btn-primary" onclick="trackParcel()">Track</button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="trackingResult">
                        <!-- Tracking results will appear here -->
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-info" onclick="showTab('manage-parcels')">View All Parcels</button>
                        <button class="btn btn-secondary" onclick="showTab('dashboard')">Back to Dashboard</button>
                    </div>
                </div>
            </div>
            
            <!-- Manage Parcels Tab -->
            <div class="tab-content" id="manage-parcels">
                <div class="content-card">
                    <h2 class="card-title">Manage Parcels</h2>
                    
                    <div class="search-box">
                        <input type="text" id="searchParcels" class="form-control" placeholder="Search parcels by tracking number, recipient name, or phone..." onkeyup="searchParcels()">
                    </div>
                    
                    <div id="parcelsContainer" class="loading">
                        Loading parcels...
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="showTab('add-parcel')">Add New Parcel</button>
                        <button class="btn btn-secondary" onclick="showTab('dashboard')">Back to Dashboard</button>
                    </div>
                </div>
            </div>
            
            <!-- Distributors Tab -->
            <div class="tab-content" id="distributors">
                <div class="content-card">
                    <h2 class="card-title">Manage Distributors</h2>
                    
                    <div id="distributorAlert"></div>
                    
                    <h3>Register New Distributor</h3>
                    <form id="distributorForm">
                        <div class="form-group">
                            <label class="form-label">Distributor Name</label>
                            <input type="text" class="form-control" id="distName" placeholder="Full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="distPhone" placeholder="e.g., 07063964841" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <select class="form-control" id="distCountry" required>
                                <option value="">Select Country</option>
                                <option value="Nigeria" selected>Nigeria</option>
                                <option value="Ghana">Ghana</option>
                                <option value="Kenya">Kenya</option>
                                <option value="South Africa">South Africa</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" id="distState" placeholder="e.g., Lagos" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Local Government Area (LGA)</label>
                            <input type="text" class="form-control" id="distLGA" placeholder="e.g., Ikeja" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" style="display: flex; align-items: center; gap: 10px;">
                                <div class="toggle-switch">
                                    <input type="checkbox" id="distActive" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                Active Distributor
                            </label>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Register Distributor</button>
                            <button type="reset" class="btn btn-secondary">Clear Form</button>
                        </div>
                    </form>
                    
                    <h3 style="margin-top: 40px;">All Distributors</h3>
                    <div id="distributorsContainer" class="loading">
                        Loading distributors...
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-info" onclick="loadDistributors()">Refresh List</button>
                        <button class="btn btn-secondary" onclick="showTab('dashboard')">Back to Dashboard</button>
                    </div>
                </div>
            </div>
            
            <!-- SMS Settings Tab -->
            <div class="tab-content" id="sms-settings">
                <div class="content-card">
                    <h2 class="card-title">SMS Settings</h2>
                    
                    <div id="smsAlert"></div>
                    
                    <form id="smsSettingsForm">
                        <div class="form-group">
                            <label class="form-label">eBulkSMS API Username</label>
                            <input type="text" class="form-control" id="smsUsername" placeholder="Your ebulksms.com username" required>
                            <small style="color: #6b7280;">Your ebulksms.com account username</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">eBulkSMS API Password/Key</label>
                            <input type="password" class="form-control" id="smsPassword" placeholder="Your API password or key" required>
                            <small style="color: #6b7280;">Your ebulksms.com API password or key</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Sender Name</label>
                            <input type="text" class="form-control" id="smsSender" placeholder="CTL" maxlength="11" required>
                            <small style="color: #6b7280;">SMS will appear as sent from this name (max 11 characters)</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">API URL</label>
                            <input type="url" class="form-control" id="smsUrl" placeholder="http://api.ebulksms.com:8080/sendsms.json" required>
                            <small style="color: #6b7280;">Default ebulksms API endpoint</small>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                            <button type="button" class="btn btn-info" onclick="testSMS()">Test SMS</button>
                            <button class="btn btn-secondary" onclick="showTab('dashboard')">Back to Dashboard</button>
                        </div>
                    </form>
                    
                    <h3 style="margin-top: 40px;">Recent SMS Logs</h3>
                    <div id="smsLogsContainer" class="loading">
                        Loading SMS logs...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Send SMS Modal -->
    <div id="smsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSMSModal()">&times;</span>
            <h2>Send SMS Notification</h2>
            
            <form id="sendSMSForm">
                <div class="form-group">
                    <label class="form-label">Tracking Number</label>
                    <input type="text" id="modalTrackingNumber" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Recipient Phone</label>
                    <input type="text" id="modalRecipientPhone" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Distributor</label>
                    <select id="modalDistributor" class="form-control" required>
                        <option value="">-- Select Distributor --</option>
                        <?php foreach ($distributors as $distributor): ?>
                            <option value="<?php echo $distributor['id']; ?>">
                                <?php echo htmlspecialchars($distributor['name']); ?> - <?php echo $distributor['phone_number']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #6b7280;">Distributor details will be included in the SMS</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message Preview</label>
                    <textarea id="smsPreview" class="form-control" rows="4" readonly style="background-color: #f9fafb;"></textarea>
                </div>
                
                <div id="smsModalAlert"></div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Send SMS</button>
                    <button type="button" class="btn btn-secondary" onclick="closeSMSModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Parcel Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <div id="detailsContent"></div>
        </div>
    </div>
    
    <!-- Edit Parcel Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Parcel</h2>
            
            <form id="editParcelForm">
                <input type="hidden" id="editTrackingNumber">
                
                <div class="form-group">
                    <label class="form-label">Tracking Number</label>
                    <input type="text" id="editTrackingDisplay" class="form-control" readonly style="background-color: #f3f4f6;">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Recipient Name</label>
                    <input type="text" id="editRecipientName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Recipient Phone</label>
                    <input type="tel" id="editRecipientPhone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea id="editAddress" class="form-control" rows="2" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" id="editWeight" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dimensions</label>
                        <input type="text" id="editDimensions" class="form-control" placeholder="LxWxH">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Origin</label>
                        <select id="editOrigin" class="form-control" required>
                            <option value="China">China</option>
                            <option value="USA">USA</option>
                            <option value="UK">UK</option>
                            <option value="Nigeria">Nigeria</option>
                            <option value="South Africa">South Africa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Destination</label>
                        <select id="editDestination" class="form-control" required>
                            <option value="Nigeria">Nigeria</option>
                            <option value="Kenya">Kenya</option>
                            <option value="Ghana">Ghana</option>
                            <option value="Egypt">Egypt</option>
                            <option value="South Africa">South Africa</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Assign Distributor</label>
                    <select id="editDistributor" class="form-control">
                        <option value="">-- No Distributor --</option>
                        <?php foreach ($distributors as $distributor): ?>
                            <option value="<?php echo $distributor['id']; ?>">
                                <?php echo htmlspecialchars($distributor['name']); ?> - <?php echo $distributor['phone_number']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="editStatus" class="form-control" required>
                        <option value="Processing">Processing</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Arrived at Hub">Arrived at Hub</option>
                        <option value="Ready for Pickup">Ready for Pickup</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status Update Description</label>
                    <textarea id="editStatusDescription" class="form-control" rows="2" placeholder="Enter description for this status update (e.g., 'Package arrived at Lagos hub')"></textarea>
                    <small style="color: #6b7280;">This will be recorded in the tracking history</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" id="editLocation" class="form-control" placeholder="Current location (e.g., 'Lagos Distribution Center')">
                </div>
                
                <div id="editModalAlert"></div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Update Parcel</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

   <script>
    // API Base URL - Fixed to use query parameters
    const API_BASE = 'api.php';
    
    // Tab navigation
    function showTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabId).classList.add('active');
        
        // Update active nav item
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('onclick') === `showTab('${tabId}')`) {
                item.classList.add('active');
            }
        });
        
        // Load data based on tab
        switch(tabId) {
            case 'dashboard':
                loadStats();
                break;
            case 'manage-parcels':
                loadParcels();
                break;
            case 'distributors':
                loadDistributors();
                break;
            case 'sms-settings':
                loadSMSSettings();
                loadSMSLogs();
                break;
        }
    }
    
    // Handle JSON parsing errors
    async function parseJSONResponse(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (error) {
            console.error('JSON parse error:', error);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server. Please check the API endpoint.');
        }
    }
    
    // Load statistics
    async function loadStats() {
        const container = document.getElementById('statsContainer');
        container.innerHTML = '<div class="loading">Loading statistics...</div>';
        
        try {
            // FIXED: Use query parameter instead of path
            const response = await fetch(`${API_BASE}?endpoint=stats`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const stats = data.data;
                container.innerHTML = `
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Total Parcels</h3>
                            <div class="value">${stats.total_parcels}</div>
                            <div class="label">All parcels in system</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3>In Transit</h3>
                            <div class="value">${stats.in_transit}</div>
                            <div class="label">Currently moving</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3>Delivered</h3>
                            <div class="value">${stats.delivered}</div>
                            <div class="label">Successfully delivered</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <h3>Today's Parcels</h3>
                            <div class="value">${stats.today_parcels}</div>
                            <div class="label">Added today</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);">
                            <h3>Distributors</h3>
                            <div class="value">${stats.active_distributors || 0}</div>
                            <div class="label">Active distributors</div>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="alert alert-error">
                        Error loading statistics: ${data.message || 'Unknown error'}
                    </div>
                `;
            }
        } catch (error) {
            container.innerHTML = `
                <div class="alert alert-error">
                    Error loading statistics: ${error.message}
                </div>
            `;
        }
    }
    
    // Add parcel form submission
    document.getElementById('parcelForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const parcelData = {
            recipient_name: document.getElementById('recipientName').value,
            recipient_phone: document.getElementById('recipientPhone').value,
            address: document.getElementById('address').value,
            weight: parseFloat(document.getElementById('weight').value),
            dimensions: document.getElementById('dimensions').value,
            origin: document.getElementById('origin').value,
            destination: document.getElementById('destination').value,
            distributor_id: document.getElementById('distributor').value || null,
            status: document.getElementById('initialStatus').value,
            initial_description: document.getElementById('statusDescription').value || 'Parcel registered in system'
        };
        
        try {
            // FIXED: Use query parameter for endpoint
            const response = await fetch(`${API_BASE}?endpoint=parcels`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(parcelData)
            });
            
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                document.getElementById('addAlert').innerHTML = `
                    <div class="alert alert-success">
                        ✅ Parcel added successfully! Tracking Number: <strong>${data.data.tracking_number}</strong>
                    </div>
                `;
                
                this.reset();
                
                setTimeout(() => {
                    showTab('track-parcel');
                    document.getElementById('trackingInput').value = data.data.tracking_number;
                    trackParcel();
                }, 1500);
            } else {
                document.getElementById('addAlert').innerHTML = `
                    <div class="alert alert-error">
                        ❌ Error: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('addAlert').innerHTML = `
                <div class="alert alert-error">
                    ❌ Network error: ${error.message}
                </div>
            `;
        }
    });
    
    // Track parcel
    async function trackParcel() {
        const trackingNumber = document.getElementById('trackingInput').value.trim();
        const resultDiv = document.getElementById('trackingResult');
        
        if (!trackingNumber) {
            resultDiv.innerHTML = `
                <div class="alert alert-warning">
                    Please enter a tracking number
                </div>
            `;
            return;
        }
        
        resultDiv.innerHTML = '<div class="loading">Tracking parcel...</div>';
        
        try {
            // FIXED: Use query parameters
            const response = await fetch(`${API_BASE}?endpoint=track&tracking_number=${encodeURIComponent(trackingNumber)}`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const parcel = data.data.parcel;
                const history = data.data.history;
                
                let statusClass = 'status-pending';
                if (parcel.status === 'Delivered') statusClass = 'status-delivered';
                if (parcel.status === 'In Transit') statusClass = 'status-in-transit';
                if (parcel.status === 'Out for Delivery') statusClass = 'status-out-for-delivery';
                if (parcel.status === 'Processing') statusClass = 'status-processing';
                
                let timelineHTML = '';
                if (history.length > 0) {
                    history.forEach(entry => {
                        const date = new Date(entry.timestamp).toLocaleString();
                        timelineHTML += `
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div style="font-weight: 600; color: #374151; margin-bottom: 5px;">${date}</div>
                                    <div style="color: #6b7280; line-height: 1.5;">
                                        <strong>Status:</strong> ${entry.status}<br>
                                        ${entry.description ? `<strong>Description:</strong> ${entry.description}<br>` : ''}
                                        ${entry.location ? `<strong>Location:</strong> ${entry.location}` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                resultDiv.innerHTML = `
                    <div style="background-color: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #e30613;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                            <div style="flex: 1;">
                                <div style="font-size: 24px; font-weight: bold; color: #1e293b;">${trackingNumber}</div>
                                <div style="margin-top: 10px;">
                                    <strong>Recipient:</strong> ${parcel.recipient_name}<br>
                                    <strong>Phone:</strong> ${parcel.recipient_phone}<br>
                                    <strong>Destination:</strong> ${parcel.destination}<br>
                                    <strong>Weight:</strong> ${parcel.weight} kg<br>
                                    ${parcel.distributor_name ? `<strong>Distributor:</strong> ${parcel.distributor_name} (${parcel.distributor_phone})` : ''}
                                </div>
                            </div>
                            <div>
                                <span class="status-badge ${statusClass}">${parcel.status}</span>
                                <div style="margin-top: 10px;">
                                    <button class="btn btn-sm btn-success" onclick="openSMSModal('${trackingNumber}', '${parcel.recipient_phone}')">
                                        📱 Send SMS
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h3 style="margin-bottom: 20px;">Tracking History</h3>
                    ${history.length > 0 ? `
                        <div class="timeline">
                            ${timelineHTML}
                        </div>
                    ` : `
                        <div class="alert alert-info">
                            No tracking history available for this parcel
                        </div>
                    `}
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-error">
                        ${data.message || 'Tracking number not found'}
                    </div>
                `;
            }
        } catch (error) {
            resultDiv.innerHTML = `
                <div class="alert alert-error">
                    Network error: ${error.message}
                </div>
            `;
        }
    }
    
    // Load all parcels
    async function loadParcels() {
        const container = document.getElementById('parcelsContainer');
        container.innerHTML = '<div class="loading">Loading parcels...</div>';
        
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=parcels`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const parcels = data.data;
                
                if (parcels.length === 0) {
                    container.innerHTML = `
                        <div class="alert alert-info">
                            No parcels found. <a href="#" onclick="showTab('add-parcel')">Add your first parcel</a>
                        </div>
                    `;
                    return;
                }
                
                let tableHTML = `
                    <div class="table-container">
                        <table class="data-table" id="parcelsTable">
                            <thead>
                                <tr>
                                    <th>Tracking #</th>
                                    <th>Recipient</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Distributor</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                parcels.forEach(parcel => {
                    let statusClass = 'status-pending';
                    if (parcel.status === 'Delivered') statusClass = 'status-delivered';
                    if (parcel.status === 'In Transit') statusClass = 'status-in-transit';
                    if (parcel.status === 'Out for Delivery') statusClass = 'status-out-for-delivery';
                    if (parcel.status === 'Processing') statusClass = 'status-processing';
                    
                    const createdDate = new Date(parcel.created_at).toLocaleDateString();
                    
                    tableHTML += `
                        <tr>
                            <td><strong>${parcel.tracking_number}</strong></td>
                            <td>${parcel.recipient_name}</td>
                            <td>${parcel.recipient_phone}</td>
                            <td><span class="status-badge ${statusClass}">${parcel.status}</span></td>
                            <td>${parcel.distributor_name || '<em>Not assigned</em>'}</td>
                            <td>${createdDate}</td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <button class="btn btn-sm btn-info" onclick="viewParcelDetails('${parcel.tracking_number}')">
                                        View
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="openSMSModal('${parcel.tracking_number}', '${parcel.recipient_phone}')">
                                        SMS
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editParcel('${parcel.tracking_number}')">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                container.innerHTML = tableHTML;
            } else {
                container.innerHTML = `
                    <div class="alert alert-error">
                        Error loading parcels: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            container.innerHTML = `
                <div class="alert alert-error">
                    Error loading parcels: ${error.message}
                </div>
            `;
        }
    }
    
    // Search parcels
    function searchParcels() {
        const input = document.getElementById('searchParcels');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('parcelsTable');
        if (!table) return;
        
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
    
    // Load distributors
    async function loadDistributors() {
        const container = document.getElementById('distributorsContainer');
        container.innerHTML = '<div class="loading">Loading distributors...</div>';
        
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=distributors`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const distributors = data.data;
                
                if (distributors.length === 0) {
                    container.innerHTML = `
                        <div class="alert alert-info">
                            No distributors found. Register one above.
                        </div>
                    `;
                    return;
                }
                
                let tableHTML = `
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Country</th>
                                    <th>State</th>
                                    <th>LGA</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                distributors.forEach(dist => {
                    const createdDate = new Date(dist.created_at).toLocaleDateString();
                    
                    tableHTML += `
                        <tr>
                            <td>${dist.name}</td>
                            <td>${dist.phone_number}</td>
                            <td>${dist.country}</td>
                            <td>${dist.state}</td>
                            <td>${dist.lga}</td>
                            <td>
                                ${dist.is_active ? 
                                    '<span class="status-badge status-delivered">Active</span>' : 
                                    '<span class="status-badge status-processing">Inactive</span>'
                                }
                            </td>
                            <td>${createdDate}</td>
                        </tr>
                    `;
                });
                
                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                container.innerHTML = tableHTML;
            } else {
                container.innerHTML = `
                    <div class="alert alert-error">
                        Error loading distributors: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            container.innerHTML = `
                <div class="alert alert-error">
                    Error loading distributors: ${error.message}
                </div>
            `;
        }
    }
    
    // Add distributor form submission
    document.getElementById('distributorForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const distributorData = {
            name: document.getElementById('distName').value,
            phone_number: document.getElementById('distPhone').value,
            country: document.getElementById('distCountry').value,
            state: document.getElementById('distState').value,
            lga: document.getElementById('distLGA').value,
            is_active: document.getElementById('distActive').checked ? 1 : 0
        };
        
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=distributors`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(distributorData)
            });
            
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                document.getElementById('distributorAlert').innerHTML = `
                    <div class="alert alert-success">
                        ✅ Distributor registered successfully!
                    </div>
                `;
                
                this.reset();
                loadDistributors();
                
                setTimeout(() => {
                    document.getElementById('distributorAlert').innerHTML = '';
                }, 3000);
            } else {
                document.getElementById('distributorAlert').innerHTML = `
                    <div class="alert alert-error">
                        ❌ Error: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('distributorAlert').innerHTML = `
                <div class="alert alert-error">
                    ❌ Network error: ${error.message}
                </div>
            `;
        }
    });
    
    // Load SMS settings
    async function loadSMSSettings() {
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=sms-settings`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const settings = data.data;
                if (settings) {
                    document.getElementById('smsUsername').value = settings.api_username || '';
                    document.getElementById('smsPassword').value = settings.api_password || '';
                    document.getElementById('smsSender').value = settings.sender_name || 'CTL';
                    document.getElementById('smsUrl').value = settings.api_url || 'http://api.ebulksms.com:8080/sendsms.json';
                }
            }
        } catch (error) {
            console.error('Error loading SMS settings:', error);
        }
    }
    
    // Save SMS settings
    document.getElementById('smsSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const smsData = {
            api_username: document.getElementById('smsUsername').value,
            api_password: document.getElementById('smsPassword').value,
            sender_name: document.getElementById('smsSender').value,
            api_url: document.getElementById('smsUrl').value
        };
        
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=sms-settings`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(smsData)
            });
            
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                document.getElementById('smsAlert').innerHTML = `
                    <div class="alert alert-success">
                        ✅ SMS settings saved successfully!
                    </div>
                `;
                
                setTimeout(() => {
                    document.getElementById('smsAlert').innerHTML = '';
                }, 3000);
            } else {
                document.getElementById('smsAlert').innerHTML = `
                    <div class="alert alert-error">
                        ❌ Error: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('smsAlert').innerHTML = `
                <div class="alert alert-error">
                    ❌ Network error: ${error.message}
                </div>
            `;
        }
    });
    
    // Load SMS logs
    async function loadSMSLogs() {
        const container = document.getElementById('smsLogsContainer');
        container.innerHTML = '<div class="loading">Loading SMS logs...</div>';
        
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=sms-logs&limit=10`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const logs = data.data;
                
                if (logs.length === 0) {
                    container.innerHTML = `
                        <div class="alert alert-info">
                            No SMS logs found yet.
                        </div>
                    `;
                    return;
                }
                
                let tableHTML = `
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tracking #</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                logs.forEach(log => {
                    const sentDate = new Date(log.sent_at).toLocaleString();
                    const statusClass = log.status === 'sent' ? 'status-delivered' : 'status-processing';
                    
                    tableHTML += `
                        <tr>
                            <td>${log.tracking_number}</td>
                            <td>${log.recipient_phone}</td>
                            <td><span class="status-badge ${statusClass}">${log.status}</span></td>
                            <td>${sentDate}</td>
                        </tr>
                    `;
                });
                
                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                container.innerHTML = tableHTML;
            }
        } catch (error) {
            container.innerHTML = `
                <div class="alert alert-error">
                    Error loading SMS logs: ${error.message}
                </div>
            `;
        }
    }
    
    // Test SMS
    async function testSMS() {
        alert('Test SMS functionality requires valid ebulksms credentials. Please save your settings first and then use the Send SMS button on parcels.');
    }
    
    // SMS Modal functions
    function openSMSModal(trackingNumber, recipientPhone) {
        document.getElementById('modalTrackingNumber').value = trackingNumber;
        document.getElementById('modalRecipientPhone').value = recipientPhone;
        document.getElementById('smsModal').style.display = 'block';
        document.getElementById('smsPreview').value = `CTL: Your package ${trackingNumber} status update. Contact 08048619168 for details.`;
    }
    
    function closeSMSModal() {
        document.getElementById('smsModal').style.display = 'none';
        document.getElementById('smsModalAlert').innerHTML = '';
    }
    
    // Send SMS form submission
    document.getElementById('sendSMSForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const smsData = {
            tracking_number: document.getElementById('modalTrackingNumber').value,
            distributor_id: document.getElementById('modalDistributor').value
        };
        
        if (!smsData.distributor_id) {
            document.getElementById('smsModalAlert').innerHTML = `
                <div class="alert alert-warning">
                    Please select a distributor
                </div>
            `;
            return;
        }
        
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=send-sms`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(smsData)
            });
            
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                document.getElementById('smsModalAlert').innerHTML = `
                    <div class="alert alert-success">
                        ✅ SMS sent successfully!
                    </div>
                `;
                
                setTimeout(() => {
                    closeSMSModal();
                    loadSMSLogs();
                }, 2000);
            } else {
                document.getElementById('smsModalAlert').innerHTML = `
                    <div class="alert alert-error">
                        ❌ Error: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('smsModalAlert').innerHTML = `
                <div class="alert alert-error">
                    ❌ Network error: ${error.message}
                </div>
            `;
        }
    });
    
    // View parcel details
    async function viewParcelDetails(trackingNumber) {
        const modal = document.getElementById('detailsModal');
        const content = document.getElementById('detailsContent');
        
        content.innerHTML = '<div class="loading">Loading details...</div>';
        modal.style.display = 'block';
        
        try {
            // FIXED: Use query parameters
            const response = await fetch(`${API_BASE}?endpoint=track&tracking_number=${encodeURIComponent(trackingNumber)}`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const parcel = data.data.parcel;
                const history = data.data.history;
                
                let statusClass = 'status-pending';
                if (parcel.status === 'Delivered') statusClass = 'status-delivered';
                if (parcel.status === 'In Transit') statusClass = 'status-in-transit';
                if (parcel.status === 'Out for Delivery') statusClass = 'status-out-for-delivery';
                if (parcel.status === 'Processing') statusClass = 'status-processing';
                
                let historyHTML = '';
                if (history.length > 0) {
                    historyHTML = '<h3 style="margin-top: 20px;">Tracking History</h3><ul style="list-style: none; padding-left: 0;">';
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
                
                content.innerHTML = `
                    <h2>Parcel Details</h2>
                    <div style="margin-bottom: 20px;">
                        <p><strong>Tracking Number:</strong> ${parcel.tracking_number}</p>
                        <p><strong>Recipient:</strong> ${parcel.recipient_name}</p>
                        <p><strong>Phone:</strong> ${parcel.recipient_phone}</p>
                        <p><strong>Address:</strong> ${parcel.address}</p>
                        <p><strong>Origin:</strong> ${parcel.origin}</p>
                        <p><strong>Destination:</strong> ${parcel.destination}</p>
                        <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${parcel.status}</span></p>
                        <p><strong>Weight:</strong> ${parcel.weight} kg</p>
                        <p><strong>Dimensions:</strong> ${parcel.dimensions || 'N/A'}</p>
                        ${parcel.distributor_name ? `<p><strong>Distributor:</strong> ${parcel.distributor_name} (${parcel.distributor_phone})</p>` : ''}
                        <p><strong>Created:</strong> ${new Date(parcel.created_at).toLocaleString()}</p>
                    </div>
                    ${historyHTML}
                    <div style="margin-top: 20px;">
                        <button class="btn btn-primary" onclick="openSMSModal('${trackingNumber}', '${parcel.recipient_phone}')">
                            Send SMS Notification
                        </button>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-error">
                        Error loading details: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            content.innerHTML = `
                <div class="alert alert-error">
                    Error loading details: ${error.message}
                </div>
            `;
        }
    }
    
    function closeDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }
    
    // Edit parcel - Open edit modal and populate with parcel data
    async function editParcel(trackingNumber) {
        const modal = document.getElementById('editModal');
        const alertDiv = document.getElementById('editModalAlert');
        alertDiv.innerHTML = '';
        
        // Show modal with loading state
        modal.style.display = 'block';
        
        try {
            // Fetch parcel details
            const response = await fetch(`${API_BASE}?endpoint=parcels&tracking_number=${encodeURIComponent(trackingNumber)}`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                const parcel = data.data;
                
                // Populate form fields
                document.getElementById('editTrackingNumber').value = parcel.tracking_number;
                document.getElementById('editTrackingDisplay').value = parcel.tracking_number;
                document.getElementById('editRecipientName').value = parcel.recipient_name || '';
                document.getElementById('editRecipientPhone').value = parcel.recipient_phone || '';
                document.getElementById('editAddress').value = parcel.address || '';
                document.getElementById('editWeight').value = parcel.weight || '';
                document.getElementById('editDimensions').value = parcel.dimensions || '';
                document.getElementById('editOrigin').value = parcel.origin || 'China';
                document.getElementById('editDestination').value = parcel.destination || 'Nigeria';
                document.getElementById('editDistributor').value = parcel.distributor_id || '';
                document.getElementById('editStatus').value = parcel.status || 'Processing';
                document.getElementById('editStatusDescription').value = '';
                document.getElementById('editLocation').value = '';
            } else {
                alertDiv.innerHTML = `
                    <div class="alert alert-error">
                        Error loading parcel: ${data.message || 'Unknown error'}
                    </div>
                `;
            }
        } catch (error) {
            alertDiv.innerHTML = `
                <div class="alert alert-error">
                    Network error: ${error.message}
                </div>
            `;
        }
    }
    
    // Close edit modal
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('editModalAlert').innerHTML = '';
    }
    
    // Edit parcel form submission
    document.getElementById('editParcelForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const alertDiv = document.getElementById('editModalAlert');
        const trackingNumber = document.getElementById('editTrackingNumber').value;
        
        const parcelData = {
            tracking_number: trackingNumber,
            recipient_name: document.getElementById('editRecipientName').value,
            recipient_phone: document.getElementById('editRecipientPhone').value,
            address: document.getElementById('editAddress').value,
            weight: parseFloat(document.getElementById('editWeight').value) || 0,
            dimensions: document.getElementById('editDimensions').value,
            origin: document.getElementById('editOrigin').value,
            destination: document.getElementById('editDestination').value,
            distributor_id: document.getElementById('editDistributor').value || null,
            status: document.getElementById('editStatus').value,
            status_description: document.getElementById('editStatusDescription').value || `Status updated to ${document.getElementById('editStatus').value}`
        };
        
        alertDiv.innerHTML = '<div class="loading">Updating parcel...</div>';
        
        try {
            // Update parcel via PUT request
            const response = await fetch(`${API_BASE}?endpoint=parcels`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(parcelData)
            });
            
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                // Also add tracking history if status description is provided
                const statusDesc = document.getElementById('editStatusDescription').value;
                const location = document.getElementById('editLocation').value;
                
                if (statusDesc) {
                    const trackingData = {
                        tracking_number: trackingNumber,
                        status: parcelData.status,
                        description: statusDesc,
                        location: location || parcelData.destination
                    };
                    
                    await fetch(`${API_BASE}?endpoint=tracking`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(trackingData)
                    });
                }
                
                alertDiv.innerHTML = `
                    <div class="alert alert-success">
                        ✅ Parcel updated successfully!
                    </div>
                `;
                
                // Refresh parcels list and close modal after delay
                setTimeout(() => {
                    closeEditModal();
                    loadParcels();
                }, 1500);
            } else {
                alertDiv.innerHTML = `
                    <div class="alert alert-error">
                        ❌ Error: ${data.message || 'Failed to update parcel'}
                    </div>
                `;
            }
        } catch (error) {
            alertDiv.innerHTML = `
                <div class="alert alert-error">
                    ❌ Network error: ${error.message}
                </div>
            `;
        }
    });
    
    // Test database connection
    async function testConnection() {
        try {
            // FIXED: Use query parameter
            const response = await fetch(`${API_BASE}?endpoint=stats`);
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                alert('✅ Database connection successful!\n\nTotal parcels in database: ' + data.data.total_parcels);
                loadStats();
            } else {
                alert('❌ Database connection failed: ' + data.message);
            }
        } catch (error) {
            alert('❌ Connection error: ' + error.message);
        }
    }
    
    // Update SMS preview when distributor changes
    function updateSMSPreview() {
        const trackingNumber = document.getElementById('modalTrackingNumber').value;
        const selectedOption = document.getElementById('modalDistributor').options[document.getElementById('modalDistributor').selectedIndex];
        
        if (selectedOption.value) {
            const distributorName = selectedOption.text.split(' - ')[0];
            document.getElementById('smsPreview').value = `CTL: Your package ${trackingNumber} is scheduled for delivery today via ${distributorName}. Do not pay extra for delivery. Contact 08048619168.`;
        } else {
            document.getElementById('smsPreview').value = `CTL: Your package ${trackingNumber} status update. Contact 08048619168 for details.`;
        }
    }
    
    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        
        // Set up SMS preview update
        document.getElementById('modalDistributor').addEventListener('change', updateSMSPreview);
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const smsModal = document.getElementById('smsModal');
            const detailsModal = document.getElementById('detailsModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == smsModal) {
                closeSMSModal();
            }
            if (event.target == detailsModal) {
                closeDetailsModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }
        
        // Add event listeners for Enter key in search
        document.getElementById('searchParcels').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchParcels();
            }
        });
        
        document.getElementById('trackingInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                trackParcel();
            }
        });
    });
</script>
</body>
</html>