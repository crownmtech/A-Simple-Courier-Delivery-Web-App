<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $country = $_POST['country'];
    $state = $_POST['state'];
    $lga = $_POST['lga'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $conn = getConnection();
    if ($conn) {
        $sql = "INSERT INTO distributors (name, phone_number, country, state, lga, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $phone, $country, $state, $lga, $is_active);
        
        if ($stmt->execute()) {
            $message = "Distributor added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding distributor: " . $conn->error;
            $message_type = "error";
        }
        $conn->close();
    }
}

// Get all distributors
$conn = getConnection();
$distributors = [];
if ($conn) {
    $sql = "SELECT * FROM distributors ORDER BY created_at DESC";
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
    <title>Distributors | CrownCourier</title>
    <style>
        /* Include all styles from track-parcel.php */
        /* Add specific styles for this page */
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
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #10b981;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- Same sidebar as track-parcel.php -->
    <!-- Add this CSS to the existing styles -->
    
    <div class="container">
        <!-- Sidebar same as track-parcel.php -->
        <div class="main-content">
            <div class="header">
                <h1>Distributors Management</h1>
                <p>Register and manage delivery distributors</p>
            </div>
            
            <div class="content-card">
                <h2 class="card-title">Register New Distributor</h2>
                
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Distributor Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="Full name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required placeholder="e.g., 07063964841">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <select name="country" class="form-control" required>
                            <option value="">Select Country</option>
                            <option value="Nigeria" selected>Nigeria</option>
                            <option value="Ghana">Ghana</option>
                            <option value="Kenya">Kenya</option>
                            <option value="South Africa">South Africa</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" required placeholder="e.g., Lagos">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Local Government Area (LGA)</label>
                        <input type="text" name="lga" class="form-control" required placeholder="e.g., Ikeja">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" style="display: flex; align-items: center; gap: 10px;">
                            <div class="toggle-switch">
                                <input type="checkbox" name="is_active" checked>
                                <span class="slider round"></span>
                            </div>
                            Active Distributor
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register Distributor</button>
                </form>
            </div>
            
            <div class="content-card">
                <h2 class="card-title">All Distributors</h2>
                
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
                            <?php foreach ($distributors as $distributor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($distributor['name']); ?></td>
                                    <td><?php echo $distributor['phone_number']; ?></td>
                                    <td><?php echo $distributor['country']; ?></td>
                                    <td><?php echo $distributor['state']; ?></td>
                                    <td><?php echo $distributor['lga']; ?></td>
                                    <td>
                                        <?php if ($distributor['is_active']): ?>
                                            <span class="status-badge status-delivered">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-processing">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($distributor['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>