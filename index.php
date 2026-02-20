<?php
// Check if we need to connect to database for tracking
$tracking_result = null;
$show_sample_section = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_number'])) {
    require_once 'config.php';
    
    $tracking_number = trim($_POST['tracking_number']);
    
    try {
        $conn = getConnection();
        
        // Get parcel info
        $sql1 = "SELECT * FROM parcels WHERE tracking_number = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("s", $tracking_number);
        $stmt1->execute();
        $parcel_result = $stmt1->get_result();
        $parcel = $parcel_result->fetch_assoc();
        
        if ($parcel) {
            // Get tracking history
            $sql2 = "SELECT * FROM tracking_history WHERE tracking_number = ? ORDER BY timestamp DESC";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("s", $tracking_number);
            $stmt2->execute();
            $history_result = $stmt2->get_result();
            $history = [];
            while ($row = $history_result->fetch_assoc()) {
                $history[] = $row;
            }
            
            $tracking_result = [
                'success' => true,
                'parcel' => $parcel,
                'history' => $history
            ];
            $show_sample_section = false; // Don't show samples when tracking is successful
        } else {
            $tracking_result = [
                'success' => false,
                'message' => 'Tracking number not found'
            ];
            $show_sample_section = true; // Show samples when tracking fails
        }
        
        $stmt1->close();
        if (isset($stmt2)) $stmt2->close();
        $conn->close();
        
    } catch (Exception $e) {
        $tracking_result = [
            'success' => false,
            'message' => 'System temporarily unavailable. Please try again later.'
        ];
        $show_sample_section = false; // Don't show samples on system error
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crown Courier | 国际快递，跨境货运</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft YaHei", "PingFang SC", Arial, sans-serif;
        }
        
        body {
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Main Header */
        .main-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff8c00;
        }
        
        .logo span {
            color: #333;
        }
        
        /* Navigation */
        .main-nav ul {
            display: flex;
            list-style: none;
        }
        
        .main-nav li {
            margin-left: 25px;
        }
        
        .main-nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s;
        }
        
        .main-nav a:hover {
            color: #ff8c00;
        }
        
        /* Language and Login */
        .header-right {
            display: flex;
            align-items: center;
        }
        
        .language-selector {
            display: flex;
            margin-right: 20px;
            border: 1px solid #ddd;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .lang-btn {
            background-color: white;
            color: #333;
            border: none;
            padding: 6px 12px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .lang-btn.active {
            background-color: #ff8c00;
            color: white;
        }
        
        .lang-btn:hover:not(.active) {
            background-color: #f5f5f5;
        }
        
        .login-btn {
            background-color: #ff8c00;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .login-btn:hover {
            background-color: #ff6600;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,140,0,0.3);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(255,140,0,0.1) 0%, rgba(135,206,250,0.2) 100%),
                        url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 180px 0 120px;
            text-align: center;
            min-height: 90vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.5) 100%);
        }
        
        .hero-content {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .tagline {
            font-size: 20px;
            letter-spacing: 4px;
            margin-bottom: 20px;
            background-color: #ff8c00;
            color: white;
            font-weight: bold;
            padding: 12px 40px;
            border-radius: 50px;
            display: inline-block;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(255,140,0,0.4);
        }
        
        .hero-title {
            font-size: 56px;
            margin-bottom: 40px;
            line-height: 1.3;
            font-weight: 800;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
        }
        
        /* Tracking Section */
        .tracking-section {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px 50px;
            border-radius: 15px;
            max-width: 800px;
            margin: 50px auto 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .tracking-title {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
        }
        
        .tracking-form {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .tracking-input {
            flex: 1;
            padding: 18px 25px;
            border: 2px solid #ddd;
            border-right: none;
            border-radius: 8px 0 0 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .tracking-input:focus {
            outline: none;
            border-color: #ff8c00;
        }
        
        .tracking-btn {
            background-color: #ff8c00;
            color: white;
            border: none;
            padding: 0 45px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tracking-btn:hover {
            background-color: #ff6600;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,140,0,0.4);
        }
        
        /* Tracking Results */
        .tracking-result {
            margin-top: 30px;
        }
        
        .parcel-info {
            background-color: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid #ff8c00;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .tracking-number-display {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tracking-number-display::before {
            content: "📦";
            font-size: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .status-delivered {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .status-transit {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .status-processing {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .status-hold {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }
        
        /* NEW: Redesigned Timeline */
        .timeline-container {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-progress {
            position: absolute;
            left: 16px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #ff8c00, #ff6600);
            z-index: 1;
        }
        
        .timeline-progress-fill {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 0;
            background: linear-gradient(to bottom, #4ade80, #22c55e);
            transition: height 1.5s ease;
            border-radius: 2px;
        }
        
        .timeline-items {
            position: relative;
            z-index: 2;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-marker {
            position: relative;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: white;
            border: 3px solid #ff8c00;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(255,140,0,0.3);
            z-index: 3;
        }
        
        .timeline-marker.active {
            background: #ff8c00;
            border-color: #ff8c00;
        }
        
        .timeline-marker.completed {
            background: #10b981;
            border-color: #10b981;
        }
        
        .timeline-marker-icon {
            color: white;
            font-size: 14px;
        }
        
        .timeline-content {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .timeline-content:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .timeline-date {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .timeline-date::before {
            content: "🕒";
            font-size: 11px;
        }
        
        .timeline-description {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .timeline-location {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #4b5563;
            margin-top: 10px;
        }
        
        .timeline-location::before {
            content: "📍";
            font-size: 12px;
        }
        
        .current-status {
            display: inline-block;
            padding: 4px 10px;
            background: linear-gradient(135deg, #ff8c00, #ff6600);
            color: white;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .timeline-title {
            font-size: 18px;
            color: #111827;
            font-weight: 700;
        }
        
        .error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
            border-left: 5px solid #ef4444;
            box-shadow: 0 4px 12px rgba(239,68,68,0.15);
        }
        
        .info-box {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
            border-left: 5px solid #0ea5e9;
            box-shadow: 0 4px 12px rgba(14,165,233,0.15);
        }
        
        .info-box h3 {
            color: #0369a1;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box h3::before {
            content: "💡";
        }
        
        .info-box p {
            color: #475569;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .info-box ul {
            color: #475569;
            margin-left: 20px;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 40px;
        }
        
        .spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,140,0,0.1);
            border-top: 4px solid #ff8c00;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Package Details */
        .package-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 14px;
        }
        
        .detail-value {
            color: #1f2937;
            font-weight: 500;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 50px 0 30px;
            margin-top: 80px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
        }
        
        .footer-logo {
            font-size: 22px;
            font-weight: bold;
            color: #ff8c00;
            margin-bottom: 20px;
        }
        
        .footer-links h4 {
            margin-bottom: 20px;
            font-size: 16px;
            color: #e5e7eb;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-links h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 2px;
            background: #ff8c00;
        }
        
        .footer-links ul {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #d1d5db;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a:hover {
            color: #ff8c00;
            transform: translateX(5px);
        }
        
        .footer-links a::before {
            content: "→";
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .footer-links a:hover::before {
            opacity: 1;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 40px;
            border-top: 1px solid #374151;
            color: #9ca3af;
            font-size: 14px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .main-nav ul {
                flex-wrap: wrap;
                margin-top: 15px;
            }
            
            .main-nav li {
                margin: 5px 15px 5px 0;
            }
            
            .header-right {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }
            
            .hero-title {
                font-size: 32px;
            }
            
            .tracking-form {
                flex-direction: column;
            }
            
            .tracking-input {
                border-right: 2px solid #ff8c00;
                border-radius: 8px;
                margin-bottom: 10px;
            }
            
            .tracking-btn {
                border-radius: 8px;
                padding: 15px;
            }
            
            .tracking-section {
                padding: 25px;
                margin: 30px auto 0;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .timeline-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .timeline-marker {
                margin-bottom: 15px;
                margin-right: 0;
            }
            
            .timeline-content {
                margin-left: 0;
            }
            
            .timeline-progress {
                left: 17.5px;
            }
        }
        
        @media (max-width: 480px) {
            .hero {
                padding: 120px 0 80px;
            }
            
            .hero-title {
                font-size: 28px;
            }
            
            .tracking-section {
                padding: 20px;
                margin: 20px auto 0;
            }
            
            .language-selector {
                flex-direction: row;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .package-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Main Header with Logo and Navigation -->
    <header class="main-header">
        <div class="container">
            <div class="header-content" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="logo" id="logoText">Crown <span style="color: #333;">Courier</span></div>
                
                <div style="display: flex; align-items: center; gap: 30px;">
                    <!-- Navigation Menu -->
                    <nav class="main-nav">
                        <ul>
                            <li><a href="#" class="nav-item" data-key="home">首页</a></li>
                            <li><a href="#" class="nav-item" data-key="track">包裹追踪</a></li>
                            <li><a href="#" class="nav-item" data-key="shipping">寄件服务</a></li>
                            <li><a href="#" class="nav-item" data-key="solutions">解决方案与服务</a></li>
                            <li><a href="#" class="nav-item" data-key="about">关于速达非</a></li>
                        </ul>
                    </nav>
                    
                    <!-- Language and Login -->
                    <div class="header-right">
                        <div class="language-selector">
                            <button class="lang-btn active" data-lang="zh">中文</button>
                            <button class="lang-btn" data-lang="en">English</button>
                        </div>
                        <button class="login-btn" id="loginBtn">注册/登录</button>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <br/><br/>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="tagline" id="tagline">快捷 安全 可靠</div>
                <h1 class="hero-title" id="heroTitle">专注于新兴市场的一站式物流服务提供商</h1>
                
                <!-- Tracking Section -->
                <div class="tracking-section">
                    <h2 class="tracking-title" id="trackingTitle">请输入您的运单号</h2>
                    <form method="POST" action="" id="trackingForm">
                        <div class="tracking-form">
                            <input type="text" class="tracking-input" id="trackingInput" name="tracking_number" 
                                   placeholder="输入运单号" value="<?php echo isset($_POST['tracking_number']) ? htmlspecialchars($_POST['tracking_number']) : ''; ?>"
                                   autocomplete="off">
                            <button type="submit" class="tracking-btn" id="trackBtn">查询</button>
                        </div>
                    </form>
                    
                    <!-- Tracking Results -->
                    <div class="tracking-result" id="trackingResult">
                        <?php if (isset($tracking_result)): ?>
                            <?php if ($tracking_result['success']): ?>
                                <?php 
                                $parcel = $tracking_result['parcel'];
                                $history = $tracking_result['history'];
                                
                                // Determine status class
                                $status_class = 'status-processing';
                                $status_icon = '🔄';
                                if (stripos($parcel['status'], 'delivered') !== false) {
                                    $status_class = 'status-delivered';
                                    $status_icon = '✅';
                                } elseif (stripos($parcel['status'], 'transit') !== false) {
                                    $status_class = 'status-transit';
                                    $status_icon = '🚚';
                                } elseif (stripos($parcel['status'], 'hold') !== false) {
                                    $status_class = 'status-hold';
                                    $status_icon = '⏸️';
                                }
                                ?>
                                <div class="parcel-info">
                                    <div class="tracking-number-display">
                                        <?php echo htmlspecialchars($parcel['tracking_number']); ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_icon . ' ' . htmlspecialchars($parcel['status']); ?>
                                        </span>
                                    </div>
                                    <div class="package-details">
                                        <div class="detail-item">
                                            <span class="detail-label">📦 收件人:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($parcel['recipient_name']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">📍 目的地:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($parcel['destination']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">⚖️ 重量:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($parcel['weight']); ?> kg</span>
                                        </div>
                                        <?php if (!empty($parcel['dimensions'])): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">📏 尺寸:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($parcel['dimensions']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="timeline-header">
                                    <h3 class="timeline-title">包裹追踪历史</h3>
                                    <span class="current-status">实时更新</span>
                                </div>
                                
                                <?php if (!empty($history)): ?>
                                    <div class="timeline-container">
                                        <!-- Progress Line -->
                                        <div class="timeline-progress">
                                            <div class="timeline-progress-fill" id="progressFill"></div>
                                        </div>
                                        
                                        <!-- Timeline Items -->
                                        <div class="timeline-items">
                                            <?php 
                                            $total_items = count($history);
                                            $current_item = 0;
                                            foreach ($history as $index => $entry): 
                                                $current_item++;
                                                // Determine marker class
                                                $marker_class = '';
                                                $marker_icon = '📝';
                                                if ($index === 0) {
                                                    $marker_class = 'active';
                                                    $marker_icon = '📍';
                                                } elseif (stripos($entry['description'], 'delivered') !== false || stripos($entry['description'], '已送达') !== false) {
                                                    $marker_class = 'completed';
                                                    $marker_icon = '✅';
                                                } elseif (stripos($entry['description'], 'transit') !== false || stripos($entry['description'], '运输中') !== false) {
                                                    $marker_icon = '🚚';
                                                } elseif (stripos($entry['description'], 'processed') !== false || stripos($entry['description'], '已处理') !== false) {
                                                    $marker_icon = '🏢';
                                                } elseif (stripos($entry['description'], 'received') !== false || stripos($entry['description'], '已接收') !== false) {
                                                    $marker_icon = '📥';
                                                }
                                            ?>
                                            <div class="timeline-item" data-progress="<?php echo ($current_item / $total_items) * 100; ?>">
                                                <div class="timeline-marker <?php echo $marker_class; ?>">
                                                    <span class="timeline-marker-icon"><?php echo $marker_icon; ?></span>
                                                </div>
                                                <div class="timeline-content">
                                                    <div class="timeline-date">
                                                        <?php echo date('Y年m月d日 H:i', strtotime($entry['timestamp'])); ?>
                                                    </div>
                                                    <div class="timeline-description">
                                                        <?php echo htmlspecialchars($entry['description']); ?>
                                                    </div>
                                                    <?php if (!empty($entry['location'])): ?>
                                                        <div class="timeline-location">
                                                            位置: <?php echo htmlspecialchars($entry['location']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <script>
                                        // Animate progress line
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const items = document.querySelectorAll('.timeline-item');
                                            if (items.length > 0) {
                                                const lastItem = items[items.length - 1];
                                                const progress = lastItem.getAttribute('data-progress');
                                                const progressFill = document.getElementById('progressFill');
                                                
                                                setTimeout(() => {
                                                    progressFill.style.height = progress + '%';
                                                }, 300);
                                            }
                                        });
                                    </script>
                                <?php else: ?>
                                    <div class="error">
                                        <strong>📭 暂无追踪记录</strong><br>
                                        此包裹暂无追踪历史记录，请稍后再试。
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="error">
                                    <strong>❌ 查询失败</strong><br>
                                    <?php echo htmlspecialchars($tracking_result['message']); ?>
                                </div>
                                <?php if ($show_sample_section): ?>
                                <div class="info-box">
                                    <h3>💡 示例运单号</h3>
                                    <p>您可以尝试以下示例运单号进行测试：</p>
                                    <ul>
                                        <li>SC2026123456 - 已送达包裹</li>
                                        <li>SC2026234567 - 运输中包裹</li>
                                        <li>SC2026345678 - 处理中包裹</li>
                                    </ul>
                                    <p>格式要求：SC + 年份 + 6位数字</p>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <div class="footer-logo" id="footerLogo">Crown <span>Courier</span></div>
                    <p style="color: #d1d5db; font-size: 14px; line-height: 1.6;" id="footerDescription">
                        专注于为新兴市场提供一站式物流解决方案，<br>
                        确保您的包裹安全、快速送达。
                    </p>
                </div>
                
                <div class="footer-links">
                    <h4 id="footerQuickLinks">快速链接</h4>
                    <ul>
                        <li><a href="#" id="footerHome">首页</a></li>
                        <li><a href="#" id="footerTrack">包裹追踪</a></li>
                        <li><a href="#" id="footerShipping">寄件服务</a></li>
                        <li><a href="#" id="footerPricing">运费计算</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4 id="footerSupport">服务支持</h4>
                    <ul>
                        <li><a href="#" id="footerHelp">帮助中心</a></li>
                        <li><a href="#" id="footerContact">联系我们</a></li>
                        <li><a href="#" id="footerTerms">服务条款</a></li>
                        <li><a href="#" id="footerPrivacy">隐私政策</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4 id="footerContactUs">联系我们</h4>
                    <ul style="color: #d1d5db; font-size: 14px;">
                        <li id="footerHotline">📞 客服热线: 400-123-4567</li>
                        <li id="footerEmail">✉️ 电子邮件: support@crownmatrixtech.com.ng</li>
                        <li id="footerHours">⏰ 服务时间: 24/7</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p id="copyright">© 2026 Crown Courier. 保留所有权利。Powered By CrownMatrix Technologies Limited</p>
            </div>
        </div>
    </footer>

    <script>
        // Language data
        const translations = {
            zh: {
                // Navigation
                home: "首页",
                track: "包裹追踪",
                shipping: "寄件服务",
                solutions: "解决方案与服务",
                about: "关于速达非",
                
                // Login button
                login: "注册/登录",
                
                // Logo
                logo: "Crown",
                logoSub: "Courier",
                
                // Hero section
                tagline: "快捷 安全 可靠",
                heroTitle: "专注于新兴市场的一站式物流服务提供商",
                
                // Tracking section
                trackingTitle: "请输入您的运单号",
                trackingPlaceholder: "输入运单号",
                trackBtn: "查询",
                
                // Tracking messages
                trackingAlert: "请输入运单号",
                trackingResult: "运单号: {number}\n\n在实际应用中，这里会显示详细的物流追踪信息。",
                
                // Login message
                loginAlert: "在实际应用中，这里会打开登录/注册表单。",
                
                // Navigation click message
                navAlert: "在实际应用中，这里会跳转到{page}页面。",
                
                // Language switch message
                langSwitch: "已切换到{language}",
                
                // Footer
                footerLogo: "CROWN",
                footerDescription: "专注于为新兴市场提供一站式物流解决方案，<br>确保您的包裹安全、快速送达。",
                footerQuickLinks: "快速链接",
                footerHome: "首页",
                footerTrack: "包裹追踪",
                footerShipping: "寄件服务",
                footerPricing: "运费计算",
                footerSupport: "服务支持",
                footerHelp: "帮助中心",
                footerContact: "联系我们",
                footerTerms: "服务条款",
                footerPrivacy: "隐私政策",
                footerContactUs: "联系我们",
                footerHotline: "客服热线: 070-486-19168",
                footerEmail: "电子邮件: support@crownmatrixtech.com.ng",
                footerHours: "服务时间: 24/7",
                copyright: "© 2026 Crown Courier. Powered By CrownMatrix Technologies Limited"
            },
            en: {
                // Navigation
                home: "Home",
                track: "Track",
                shipping: "Shipping",
                solutions: "Solutions & Services",
                about: "About Crown Courier",
                
                // Login button
                login: "Sign in",
                
                // Logo
                logo: "CROWN",
                logoSub: "COURIER",
                
                // Hero section
                tagline: "FAST SAFE RELIABLE",
                heroTitle: "One-Stop Logistics Service Provider Focusing on Emerging Markets",
                
                // Tracking section
                trackingTitle: "Please enter your tracking number",
                trackingPlaceholder: "Enter tracking number",
                trackBtn: "Track",
                
                // Tracking messages
                trackingAlert: "Please enter a tracking number",
                trackingResult: "Tracking number: {number}\n\nThis would show detailed tracking information in a real application.",
                
                // Login message
                loginAlert: "Login/Registration form would open here in a real application.",
                
                // Navigation click message
                navAlert: "This would navigate to the {page} page in a real application.",
                
                // Language switch message
                langSwitch: "Switched to {language}",
                
                // Footer
                footerLogo: "CROWN",
                footerDescription: "One-stop logistics solutions for emerging markets,<br>ensuring your packages are delivered safely and quickly.",
                footerQuickLinks: "Quick Links",
                footerHome: "Home",
                footerTrack: "Track Parcel",
                footerShipping: "Shipping Services",
                footerPricing: "Price Calculator",
                footerSupport: "Support",
                footerHelp: "Help Center",
                footerContact: "Contact Us",
                footerTerms: "Terms of Service",
                footerPrivacy: "Privacy Policy",
                footerContactUs: "Contact Us",
                footerHotline: "Hotline: 400-123-4567",
                footerEmail: "Email: support@crownmatrixtech.com.ng",
                footerHours: "Service Hours: 24/7",
                copyright: "© 2026 Crown Courier. All rights reserved. Powered By CrownMatrix Technologies Limited"
            }
        };

        // Current language (default is Chinese)
        let currentLang = 'zh';

        // DOM elements that need translation
        const elementsToTranslate = {
            // Navigation items
            '.nav-item[data-key="home"]': 'home',
            '.nav-item[data-key="track"]': 'track',
            '.nav-item[data-key="shipping"]': 'shipping',
            '.nav-item[data-key="solutions"]': 'solutions',
            '.nav-item[data-key="about"]': 'about',
            
            // Login button
            '#loginBtn': 'login',
            
            // Logo
            '#logoText': 'logo',
            
            // Hero section
            '#tagline': 'tagline',
            '#heroTitle': 'heroTitle',
            
            // Tracking section
            '#trackingTitle': 'trackingTitle',
            '#trackingInput': 'trackingPlaceholder',
            '#trackBtn': 'trackBtn'
        };

        // Function to apply translations
        function applyTranslations(lang) {
            // Update all translatable elements
            for (const selector in elementsToTranslate) {
                const element = document.querySelector(selector);
                const key = elementsToTranslate[selector];
                
                if (element) {
                    if (selector === '#logoText') {
                        // Special handling for logo
                        element.innerHTML = `${translations[lang].logo} <span>${translations[lang].logoSub}</span>`;
                    } else if (selector === '#trackingInput') {
                        // For input placeholder
                        element.placeholder = translations[lang][key];
                    } else {
                        // For regular text content
                        element.textContent = translations[lang][key];
                    }
                }
            }
            
            // Update footer elements
            updateFooter(lang);
            
            // Update active language button
            document.querySelectorAll('.lang-btn').forEach(btn => {
                if (btn.getAttribute('data-lang') === lang) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // Update document title
            document.title = lang === 'zh' 
                ? 'Crown Courier | 国际快递，跨境货运' 
                : 'Crown Courier | International Shipping, Cross-border Logistics';
            
            // Update HTML lang attribute
            document.documentElement.lang = lang === 'zh' ? 'zh-CN' : 'en';
        }
        
        // Update footer text based on language
        function updateFooter(lang) {
            const footerElements = {
                footerLogo: 'footerLogo',
                footerDescription: 'footerDescription',
                footerQuickLinks: 'footerQuickLinks',
                footerHome: 'footerHome',
                footerTrack: 'footerTrack',
                footerShipping: 'footerShipping',
                footerPricing: 'footerPricing',
                footerSupport: 'footerSupport',
                footerHelp: 'footerHelp',
                footerContact: 'footerContact',
                footerTerms: 'footerTerms',
                footerPrivacy: 'footerPrivacy',
                footerContactUs: 'footerContactUs',
                footerHotline: 'footerHotline',
                footerEmail: 'footerEmail',
                footerHours: 'footerHours',
                copyright: 'copyright'
            };
            
            for (const key in footerElements) {
                const element = document.getElementById(footerElements[key]);
                if (element) {
                    if (key === 'footerLogo') {
                        element.innerHTML = `${translations[lang].footerLogo} <span>COURIER</span>`;
                    } else if (key === 'footerDescription') {
                        element.innerHTML = translations[lang].footerDescription;
                    } else {
                        element.textContent = translations[lang][key];
                    }
                }
            }
        }

        // Function to switch language
        function switchLanguage(lang) {
            if (lang === currentLang) return;
            
            currentLang = lang;
            applyTranslations(lang);
            
            // Show language switch message
            const langName = lang === 'zh' ? '中文' : 'English';
            alert(translations[lang].langSwitch.replace('{language}', langName));
        }

        // Login button functionality - redirect to login.php
        document.getElementById('loginBtn').addEventListener('click', function() {
            window.location.href = 'login.php';
        });

        // Form validation and submission
        document.getElementById('trackingForm').addEventListener('submit', function(e) {
            const trackingNumber = document.getElementById('trackingInput').value.trim();
            
            if (!trackingNumber) {
                e.preventDefault();
                alert(translations[currentLang].trackingAlert);
                return;
            }
            
            // Validate tracking number format (SC followed by year + 6 digits, e.g., SC2026192882)
            const trackingRegex = /^SC\d{10}$/;
            if (!trackingRegex.test(trackingNumber)) {
                e.preventDefault();
                alert(currentLang === 'zh' 
                    ? '运单号格式不正确。正确格式：SC + 年份 + 6位数字（例如：SC2026192882）'
                    : 'Invalid tracking number format. Correct format: SC + Year + 6 digits (e.g., SC2026192882)');
                return;
            }
            
            // Show loading state
            const trackBtn = document.getElementById('trackBtn');
            const originalText = trackBtn.textContent;
            trackBtn.textContent = currentLang === 'zh' ? '查询中...' : 'Tracking...';
            trackBtn.disabled = true;
            
            // Add loading spinner to results area
            const resultDiv = document.getElementById('trackingResult');
            resultDiv.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p style="margin-top: 10px; color: #6b7280;">
                        ${currentLang === 'zh' ? '正在查询包裹信息...' : 'Searching for parcel information...'}
                    </p>
                </div>
            `;
            
            // Scroll to results
            setTimeout(() => {
                resultDiv.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
        });
        
        // Enter key support for tracking input
        document.getElementById('trackingInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('trackBtn').click();
            }
        });
        
        // Navigation links
        document.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const pageKey = this.getAttribute('data-key');
                
                // Handle different navigation items
                switch(pageKey) {
                    case 'track':
                        // Already on tracking page, just scroll to top
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        document.getElementById('trackingInput').focus();
                        break;
                    case 'shipping':
                        // In a real application, this would navigate to shipping page
                        // For now, show message and stay on same page
                        if (currentLang === 'zh') {
                            alert('寄件服务页面正在建设中...');
                        } else {
                            alert('Shipping services page is under construction...');
                        }
                        break;
                    case 'solutions':
                        if (currentLang === 'zh') {
                            alert('解决方案与服务页面正在建设中...');
                        } else {
                            alert('Solutions & Services page is under construction...');
                        }
                        break;
                    case 'about':
                        if (currentLang === 'zh') {
                            alert('关于我们页面正在建设中...');
                        } else {
                            alert('About Us page is under construction...');
                        }
                        break;
                    default:
                        // For home, just scroll to top
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
        
        // Language switcher buttons
        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const lang = this.getAttribute('data-lang');
                switchLanguage(lang);
            });
        });

        // Initialize with Chinese language
        applyTranslations('zh');
        
        // Auto-focus on tracking input if it's empty
        window.addEventListener('DOMContentLoaded', function() {
            const trackingInput = document.getElementById('trackingInput');
            if (!trackingInput.value) {
                trackingInput.focus();
            }
            
            // If there's a tracking result, scroll to it
            <?php if (isset($tracking_result) && $tracking_result['success']): ?>
                setTimeout(() => {
                    document.getElementById('trackingResult').scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 300);
            <?php endif; ?>
        });
        
        // Clear tracking input on page refresh (unless there's a POST result)
        window.addEventListener('pageshow', function(event) {
            // If the page is loaded from cache (back/forward navigation)
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                const trackingInput = document.getElementById('trackingInput');
                if (trackingInput && !trackingInput.value.includes('SC')) {
                    trackingInput.value = '';
                }
            }
        });
    </script>
</body>
</html>