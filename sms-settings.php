<?php
require_once 'config.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get current settings
$conn = getConnection();
$settings = [];
if ($conn) {
    $sql = "SELECT * FROM sms_settings LIMIT 1";
    $result = $conn->query($sql);
    $settings = $result->fetch_assoc();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['api_username'];
        $password = $_POST['api_password'];
        $sender_name = $_POST['sender_name'];
        $api_url = $_POST['api_url'];
        
        if ($settings) {
            // Update existing
            $sql = "UPDATE sms_settings SET 
                    api_username = ?, 
                    api_password = ?, 
                    sender_name = ?, 
                    api_url = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $username, $password, $sender_name, $api_url, $settings['id']);
        } else {
            // Insert new
            $sql = "INSERT INTO sms_settings (api_username, api_password, sender_name, api_url) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $password, $sender_name, $api_url);
        }
        
        if ($stmt->execute()) {
            $message = "Settings saved successfully!";
            $message_type = "success";
            
            // Refresh settings
            $result = $conn->query("SELECT * FROM sms_settings LIMIT 1");
            $settings = $result->fetch_assoc();
        } else {
            $message = "Error saving settings: " . $conn->error;
            $message_type = "error";
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
    <title>SMS Settings | CrownCourier</title>
    <style>
        /* Same styles as other pages */
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar same as other pages -->
        <div class="main-content">
            <div class="header">
                <h1>SMS Settings</h1>
                <p>Configure ebulksms.com API credentials</p>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="content-card">
                <h2 class="card-title">eBulkSMS API Configuration</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">API Username</label>
                        <input type="text" name="api_username" class="form-control" 
                               value="<?php echo $settings['api_username'] ?? ''; ?>" required>
                        <small>Your ebulksms.com username</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">API Password/Key</label>
                        <input type="password" name="api_password" class="form-control" 
                               value="<?php echo $settings['api_password'] ?? ''; ?>" required>
                        <small>Your ebulksms.com API password or key</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Sender Name</label>
                        <input type="text" name="sender_name" class="form-control" 
                               value="<?php echo $settings['sender_name'] ?? 'CTL'; ?>" required maxlength="11">
                        <small>SMS will appear as sent from this name (max 11 characters)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">API URL</label>
                        <input type="url" name="api_url" class="form-control" 
                               value="<?php echo $settings['api_url'] ?? 'http://api.ebulksms.com:8080/sendsms.json'; ?>" required>
                        <small>Default: http://api.ebulksms.com:8080/sendsms.json</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
            
            <div class="content-card">
                <h2 class="card-title">SMS Test & Statistics</h2>
                
                <div class="balance-card">
                    <h3>Account Balance</h3>
                    <p style="font-size: 32px; font-weight: bold;">
                        ₦<?php echo number_format($settings['balance'] ?? 0, 2); ?>
                    </p>
                    <small>Last checked: <?php echo $settings['last_checked'] ?? 'Never'; ?></small>
                </div>
                
                <div style="margin-top: 20px;">
                    <button class="btn btn-info" onclick="checkBalance()">Check Balance</button>
                    <button class="btn btn-success" onclick="testSMS()">Send Test SMS</button>
                </div>
                
                <div id="testResult" style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>
    
    <script>
        function checkBalance() {
            // This would call an API endpoint to check SMS balance
            alert('In a real implementation, this would call the ebulksms API to check balance');
        }
        
        function testSMS() {
            // This would send a test SMS
            alert('Test SMS functionality would be implemented here');
        }
    </script>
</body>
</html>