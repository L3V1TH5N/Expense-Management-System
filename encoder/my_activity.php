<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Get recent expense activities (last 20)
$expense_activities = getRecentActivities($pdo, 20, $user_id);

// Get login/logout activities (last 20)
$stmt = $pdo->prepare("
    SELECT * FROM activity_logs 
    WHERE user_id = ? AND action IN ('LOGIN', 'LOGOUT')
    ORDER BY action_time DESC 
    LIMIT 20
");
$stmt->execute([$user_id]);
$auth_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent expenses (last 10)
$stmt = $conn->prepare("
    SELECT e.*, o.name as office_name, so.name as sub_office_name 
    FROM expenses e 
    LEFT JOIN offices o ON e.office_id = o.id 
    LEFT JOIN offices so ON e.sub_office_id = so.id 
    WHERE e.created_by = ?
    ORDER BY e.created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Activity - Expense Management System</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .activity-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            grid-gap: 24px;
            margin-top: 24px;
        }
        
        .activity-card {
            background: var(--light);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .activity-card h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 24px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .activity-item {
            padding: 16px;
            background: var(--grey);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .activity-message {
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--dark-grey);
        }
        
        .activity-login {
            color: var(--success);
        }
        
        .activity-logout {
            color: var(--danger);
        }
        
        .activity-create {
            color: var(--primary);
        }
        
        .activity-update {
            color: var(--warning);
        }
        
        .activity-delete {
            color: var(--danger);
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--dark-grey);
            background: var(--grey);
            border-radius: 10px;
        }
        
        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: var(--dark-grey);
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            padding-bottom: 12px;
            font-size: 13px;
            text-align: left;
            border-bottom: 1px solid var(--grey);
        }
        
        table td {
            padding: 16px 0;
            border-bottom: 1px solid var(--grey);
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        table tr:hover {
            background: var(--grey);
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-smile'></i>
            <span class="text"><?php echo $_SESSION['full_name']; ?></span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="encode.php">
                    <i class='bx bxs-plus-circle'></i>
                    <span class="text">Encode Expense</span>
                </a>
            </li>
            <li>
                <a href="my_expenses.php">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">Expenses</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class='bx bxs-report'></i>
                    <span class="text">Reports</span>
                </a>
            </li>
            <li>
                <a href="export.php">
                    <i class='bx bxs-download'></i>
                    <span class="text">Export Reports</span>
                </a>
            </li>
            <li class="active">
                <a href="my_activity.php">
                    <i class='bx bxs-time'></i>
                    <span class="text">My Activity</span>
                </a>
            </li>
            <br><br>
            <li>
                <a href="profile.php">
                    <i class='bx bxs-user'></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="../logout.php" class="logout">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <span class="nav-text">My Activity</span>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>My Activity</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Dashboard</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">My Activity</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="activity-container">
                <div class="activity-card">
                    <h2><i class='bx bx-receipt'></i> Recent Expense Activities</h2>
                    
                    <?php if (!empty($expense_activities)): ?>
                        <div class="activity-list">
                            <?php foreach ($expense_activities as $activity): ?>
                                <?php $formatted = formatActivityDisplay($activity); ?>
                                <div class="activity-item">
                                    <div class="activity-message activity-<?php echo strtolower($activity['action']); ?>">
                                        <i class='bx bx-<?php 
                                            switch($activity['action']) {
                                                case 'CREATE': echo 'plus'; break;
                                                case 'UPDATE': echo 'edit'; break;
                                                case 'DELETE': echo 'trash'; break;
                                                default: echo 'info-circle';
                                            }
                                        ?>'></i>
                                        <?php echo $formatted['message']; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo $formatted['time']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-time'></i>
                            <h3>No activity yet</h3>
                            <p>Your expense activities will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="activity-card">
                    <h2><i class='bx bx-shield'></i> Login/Logout History</h2>
                    
                    <?php if (!empty($auth_activities)): ?>
                        <div class="activity-list">
                            <?php foreach ($auth_activities as $activity): ?>
                                <?php $formatted = formatActivityDisplay($activity); ?>
                                <div class="activity-item">
                                    <div class="activity-message activity-<?php echo strtolower($activity['action']); ?>">
                                        <i class='bx bx-<?php echo strtolower($activity['action']) === 'login' ? 'log-in' : 'log-out' ?>'></i>
                                        <?php echo $formatted['message']; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo $formatted['time']; ?>
                                    </div>
                                    <?php if ($activity['action'] === 'LOGIN'): ?>
                                        <div class="activity-details" style="font-size: 12px; color: var(--dark-grey); margin-top: 5px;">
                                            <i class='bx bx-globe'></i> IP: <?php echo json_decode($activity['new_values'], true)['ip_address'] ?? 'Unknown'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-shield'></i>
                            <h3>No login/logout history</h3>
                            <p>Your authentication activities will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
</body>
</html>