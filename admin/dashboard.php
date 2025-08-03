<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Get admin user information
$user_id = $_SESSION['user_id'];
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';

// Get current month and year for filtering
$current_month = date('Y-m');
$current_month_name = date('F Y');

// ==============================================
// SYSTEM-WIDE STATISTICS
// ==============================================

// Total expenses in the system this month
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_expenses 
    FROM expenses 
    WHERE DATE_FORMAT(date, '%Y-%m') = ?
");
$stmt->execute([$current_month]);
$total_expenses = $stmt->fetch()['total_expenses'];

// Total amount by fund type this month
$stmt = $conn->prepare("
    SELECT 
        fund_type,
        SUM(total) as fund_total,
        COUNT(*) as fund_count
    FROM expenses 
    WHERE DATE_FORMAT(date, '%Y-%m') = ?
    GROUP BY fund_type
");
$stmt->execute([$current_month]);
$fund_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize fund amounts
$general_fund = 0;
$sef_fund = 0;
$trust_fund = 0;
$total_monthly_amount = 0;

// Process fund totals
foreach ($fund_totals as $fund) {
    switch ($fund['fund_type']) {
        case 'General Fund':
            $general_fund = $fund['fund_total'];
            break;
        case 'Special Education Fund':
            $sef_fund = $fund['fund_total'];
            break;
        case 'Trust Fund':
            $trust_fund = $fund['fund_total'];
            break;
    }
    $total_monthly_amount += $fund['fund_total'];
}

// Total users in the system
$stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$total_users = $stmt->fetch()['total_users'];

// Active encoders (users who created expenses this month)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT created_by) as active_encoders 
    FROM expenses 
    WHERE DATE_FORMAT(date, '%Y-%m') = ?
");
$stmt->execute([$current_month]);
$active_encoders = $stmt->fetch()['active_encoders'];

// Cash advances this month
$stmt = $conn->prepare("
    SELECT COUNT(*) as cash_advances 
    FROM expenses 
    WHERE expense_type = 'Cash Advance' 
    AND DATE_FORMAT(date, '%Y-%m') = ?
");
$stmt->execute([$current_month]);
$cash_advances = $stmt->fetch()['cash_advances'];

// ==============================================
// RECENT ACTIVITIES AND DATA
// ==============================================

// Recent expenses (all users)
$stmt = $conn->prepare("
    SELECT 
        e.*, 
        o.name as office_name, 
        so.name as sub_office_name,
        u.full_name as encoder_name
    FROM expenses e 
    LEFT JOIN offices o ON e.office_id = o.id 
    LEFT JOIN offices so ON e.sub_office_id = so.id 
    LEFT JOIN users u ON e.created_by = u.id
    ORDER BY e.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_expenses = $stmt->fetchAll();

// Recent activity logs (system-wide)
$stmt = $pdo->prepare("
    SELECT al.*, u.full_name, u.username
    FROM activity_logs al 
    JOIN users u ON al.user_id = u.id 
    ORDER BY al.action_time DESC 
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top encoders this month
$stmt = $conn->prepare("
    SELECT 
        u.full_name,
        u.username,
        COUNT(e.id) as expense_count,
        SUM(e.total) as total_amount
    FROM users u
    LEFT JOIN expenses e ON u.id = e.created_by 
        AND DATE_FORMAT(e.date, '%Y-%m') = ?
    WHERE u.role = 'encoder'
    GROUP BY u.id, u.full_name, u.username
    ORDER BY expense_count DESC, total_amount DESC
    LIMIT 5
");
$stmt->execute([$current_month]);
$top_encoders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Expense type breakdown this month
$stmt = $conn->prepare("
    SELECT 
        expense_type,
        COUNT(*) as count,
        SUM(total) as total_amount
    FROM expenses 
    WHERE DATE_FORMAT(date, '%Y-%m') = ?
    GROUP BY expense_type
    ORDER BY total_amount DESC
");
$stmt->execute([$current_month]);
$expense_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==============================================
// SYSTEM HEALTH METRICS
// ==============================================

// Check for potential issues
$pending_reviews = 0; // You can implement a review system later
$system_alerts = [];

// Check for large amounts (potential flag for review)
$stmt = $conn->prepare("
    SELECT COUNT(*) as large_amounts 
    FROM expenses 
    WHERE total > 100000 
    AND DATE_FORMAT(date, '%Y-%m') = ?
");
$stmt->execute([$current_month]);
$large_amounts = $stmt->fetch()['large_amounts'];

if ($large_amounts > 0) {
    $system_alerts[] = "There are {$large_amounts} expenses over ₱100,000 this month";
}

// Check for users without recent activity
$stmt = $conn->prepare("
    SELECT COUNT(*) as inactive_users 
    FROM users u 
    WHERE u.role = 'encoder' 
    AND u.id NOT IN (
        SELECT DISTINCT created_by 
        FROM expenses 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    )
");
$stmt->execute();
$inactive_users = $stmt->fetch()['inactive_users'];

if ($inactive_users > 0) {
    $system_alerts[] = "{$inactive_users} encoders haven't created any expenses in the last 30 days";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Admin Dashboard - Expense Management System</title>
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-danger: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --gradient-general: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            --gradient-sef: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            --gradient-trust: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            --gradient-total: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            --gradient-users: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            --gradient-encoders: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            --shadow-light: 0 4px 20px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
            --border-radius: 16px;
            --animation-speed: 0.3s;
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
        }

        /* Admin-specific styling */
        .admin-welcome-banner {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .admin-welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.08'%3E%3Ccircle cx='30' cy='30' r='3'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            animation: float 25s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 10px;
        }

        /* Enhanced Stats Cards for Admin */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .admin-stats .stat-card {
            background: var(--light);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .admin-stats .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }

        .admin-stats .stat-card.total-expenses::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .admin-stats .stat-card.total-users::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-users);
        }

        .admin-stats .stat-card.active-encoders::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-encoders);
        }

        .admin-stats .stat-card.cash-advances::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-warning);
        }

        /* Fund totals styling (reuse from encoder dashboard) */
        .fund-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .fund-stats .stat-card.general-fund::before {
            background: var(--gradient-general);
        }

        .fund-stats .stat-card.sef-fund::before {
            background: var(--gradient-sef);
        }

        .fund-stats .stat-card.trust-fund::before {
            background: var(--gradient-trust);
        }

        .fund-stats .stat-card.total-amount::before {
            background: var(--gradient-total);
        }

        .stat-card i {
            font-size: 32px;
            flex-shrink: 0;
        }

        .stat-card.total-expenses i { color: #667eea; }
        .stat-card.total-users i { color: #9b59b6; }
        .stat-card.active-encoders i { color: #e67e22; }
        .stat-card.cash-advances i { color: #f093fb; }
        .stat-card.general-fund i { color: #3498db; }
        .stat-card.sef-fund i { color: #e74c3c; }
        .stat-card.trust-fund i { color: #f39c12; }
        .stat-card.total-amount i { color: #27ae60; }

        .stat-card .text h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .stat-card .text p {
            color: var(--dark-grey);
            font-size: 13px;
            margin: 0;
            line-height: 1.3;
        }

        /* Admin Action Cards */
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .admin-action-card {
            background: var(--light);
            border-radius: 20px;
            padding: 24px;
            transition: all 0.3s ease;
            border: 1px solid var(--grey);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        .admin-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border-color: var(--admin-primary);
        }

        .admin-action-card a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: var(--dark);
            height: 100%;
        }

        .admin-action-card i {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--admin-primary);
            transition: all 0.3s ease;
        }

        .admin-action-card:hover i {
            transform: scale(1.1);
        }

        .admin-action-card h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .admin-action-card p {
            color: var(--dark-grey);
            font-size: 14px;
            margin: 0;
        }

        /* System Alerts */
        .system-alerts {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ffc107;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .system-alerts h4 {
            color: #856404;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .system-alerts ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }

        /* Top Encoders Table */
        .top-encoders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .top-encoders-table th,
        .top-encoders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--grey);
        }

        .top-encoders-table th {
            background: var(--grey);
            font-weight: 600;
            color: var(--dark);
        }

        .encoder-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 700;
            color: white;
            margin-right: 8px;
        }

        .encoder-rank.rank-1 { background: #f39c12; }
        .encoder-rank.rank-2 { background: #95a5a6; }
        .encoder-rank.rank-3 { background: #e67e22; }
        .encoder-rank.rank-other { background: var(--dark-grey); }

        /* Activity Log Styling */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--grey);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }

        .activity-icon.login { background: var(--blue); }
        .activity-icon.logout { background: var(--dark-grey); }
        .activity-icon.create { background: var(--blue); }
        .activity-icon.update { background: var(--yellow); }
        .activity-icon.delete { background: var(--red); }
        .activity-icon.password { background: var(--orange); }

        .activity-content {
            flex-grow: 1;
        }

        .activity-content .activity-title {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .activity-content .activity-time {
            font-size: 12px;
            color: var(--dark-grey);
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .admin-actions {
                grid-template-columns: 1fr;
            }
            
            .admin-action-card {
                padding: 20px;
            }

            .admin-stats, .fund-stats {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
                gap: 12px;
            }

            .stat-card i {
                font-size: 28px;
            }

            .stat-card .text h3 {
                font-size: 18px;
            }
        }

        /* Monthly Stats Header */
        .monthly-stats-header {
            text-align: center;
            margin: 2rem 0 1rem 0;
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
        }

        .monthly-stats-header h3 {
            color: var(--dark);
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .monthly-stats-header p {
            color: var(--dark-grey);
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }

        /* Brand styling for admin */
        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }

        .brand {
            display: flex;
            align-items: center;
            font-size: 30px; 
            width: 100%; 
            margin-bottom: 20px; 
            color: #fff;
            text-decoration: none;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .admin-stats .stat-card, .fund-stats .stat-card, .table-data > div {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-shield-alt-2'></i>
            <span class="text">Admin Panel</span>
        </a>
        <ul class="side-menu top">
            <li class="active">
                <a href="dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage_users.php">
                    <i class='bx bxs-user-account'></i>
                    <span class="text">Manage Users</span>
                </a>
            </li>
            <li>
                <a href="all_expenses.php">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">All Expenses</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class='bx bxs-report'></i>
                    <span class="text">Reports</span>
                </a>
            </li>
            <li>
                <a href="activity_logs.php">
                    <i class='bx bxs-time'></i>
                    <span class="text">Activity Logs</span>
                </a>
            </li>
            <li>
                <a href="system_settings.php">
                    <i class='bx bxs-cog'></i>
                    <span class="text">System Settings</span>
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
            <span class="nav-text">Expense Management System</span>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Admin Dashboard</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Dashboard</a>
                        </li>
                    </ul>
                </div>
            </div>

			<br>

            <!-- Welcome Banner -->
            <div class="admin-welcome-banner fade-in">
                <div class="welcome-content">
                    <h2>
                        Welcome back, <?php echo htmlspecialchars($full_name); ?>! 
                        <span class="admin-badge">
                            <i class='bx bxs-shield-alt-2'></i>
                            Administrator
                        </span>
                    </h2>
                    <p>Monitor system performance and manage the expense management system</p>
                </div>
            </div>

            <!-- System Alerts -->
            <?php if (!empty($system_alerts)): ?>
            <div class="system-alerts">
                <h4><i class='bx bxs-error-circle'></i> System Alerts</h4>
                <ul>
                    <?php foreach ($system_alerts as $alert): ?>
                        <li><?php echo htmlspecialchars($alert); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

			<!-- Fund Distribution Stats -->
            <ul class="box-info">
                <!-- General Fund -->
                <li class="general-fund">
                    <i class='bx bxs-bank'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($general_fund, 2); ?></h3>
                        <p>General Fund</p>
                    </span>
                </li>

                <!-- Special Education Fund -->
                <li class="sef-fund">
                    <i class='bx bxs-graduation'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($sef_fund, 2); ?></h3>
                        <p>Special Education Fund (SEF)</p>
                    </span>
                </li>

                <!-- Trust Fund -->
                <li class="trust-fund">
                    <i class='bx bxs-shield-alt-2'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($trust_fund, 2); ?></h3>
                        <p>Trust Fund</p>
                    </span>
                </li>

                <!-- Total Monthly Amount -->
                <li class="total-amount">
                    <i class='bx bxs-dollar-circle'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($total_monthly_amount, 2); ?></h3>
                        <p>Total Monthly</p>
                    </span>
                </li>
            </ul>

            <!-- System Overview Stats -->
            <div class="admin-stats">
                <div class="stat-card total-expenses">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_expenses); ?></h3>
                        <p>Total Expenses This Month</p>
                    </span>
                </div>

                <div class="stat-card total-users">
                    <i class='bx bxs-user-account'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>System Users</p>
                    </span>
                </div>

                <div class="stat-card active-encoders">
                    <i class='bx bxs-user-check'></i>
                    <span class="text">
                        <h3><?php echo number_format($active_encoders); ?></h3>
                        <p>Active Encoders This Month</p>
                    </span>
                </div>
            </div>

            <!-- Monthly Stats Header -->
            <div class="monthly-stats-header">
                <h3>💰 Fund Allocation for <?php echo $current_month_name; ?></h3>
                <p>System-wide fund distribution and totals</p>
            </div>

            <!-- Admin Actions & Data Tables -->
            <div class="table-data">

                <!-- Top Encoders This Month -->
                <div class="order">
                    <div class="head">
                        <h3>Top Encoders - <?php echo $current_month_name; ?></h3>
                        <a href="manage_users.php" class="btn-link">View All Users</a>
                    </div>
                    <?php if (!empty($top_encoders)): ?>
                    <table class="top-encoders-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Encoder</th>
                                <th>Expenses</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_encoders as $index => $encoder): ?>
                            <tr>
                                <td>
                                    <span class="encoder-rank rank-<?php echo $index < 3 ? ($index + 1) : 'other'; ?>">
                                        <?php echo $index + 1; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($encoder['full_name']); ?></strong><br>
                                    <small>@<?php echo htmlspecialchars($encoder['username']); ?></small>
                                </td>
                                <td><?php echo number_format($encoder['expense_count']); ?></td>
                                <td>₱<?php echo number_format($encoder['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                        No encoder activity found for this month.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Recent System Activity -->
                <div class="order">
                    <div class="head">
                        <h3>Recent System Activity</h3>
                        <a href="activity_logs.php" class="btn-link">View All Activities</a>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo strtolower($activity['action']); ?>">
                                    <?php
                                    $icons = [
                                        'LOGIN' => 'bx-log-in',
                                        'LOGOUT' => 'bx-log-out',
                                        'CREATE' => 'bx-plus-circle',
                                        'UPDATE' => 'bx-edit',
                                        'DELETE' => 'bx-trash',
                                        'PASSWORD_CHANGE' => 'bx-key'
                                    ];
                                    $icon = $icons[$activity['action']] ?? 'bx-info-circle';
                                    ?>
                                    <i class='bx <?php echo $icon; ?>'></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php
                                        $action = strtolower($activity['action']);
                                        $table = $activity['table_name'];
                                        $userName = htmlspecialchars($activity['full_name']);
                                        
                                        switch ($activity['action']) {
                                            case 'LOGIN':
                                                echo "{$userName} logged into the system";
                                                break;
                                            case 'LOGOUT':
                                                echo "{$userName} logged out of the system";
                                                break;
                                            case 'CREATE':
                                                if ($table === 'expenses') {
                                                    echo "{$userName} created a new expense record";
                                                } elseif ($table === 'users') {
                                                    echo "{$userName} created a new user account";
                                                } else {
                                                    echo "{$userName} created a new {$table} record";
                                                }
                                                break;
                                            case 'UPDATE':
                                                if ($table === 'expenses') {
                                                    echo "{$userName} updated an expense record";
                                                } elseif ($table === 'users') {
                                                    echo "{$userName} updated a user profile";
                                                } else {
                                                    echo "{$userName} updated a {$table} record";
                                                }
                                                break;
                                            case 'DELETE':
                                                if ($table === 'expenses') {
                                                    echo "{$userName} deleted an expense record";
                                                } elseif ($table === 'users') {
                                                    echo "{$userName} deleted a user account";
                                                } else {
                                                    echo "{$userName} deleted a {$table} record";
                                                }
                                                break;
                                            case 'PASSWORD_CHANGE':
                                                echo "{$userName} changed their password";
                                                break;
                                            default:
                                                echo "{$userName} performed {$action} on {$table}";
                                        }
                                        ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['action_time'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                            No recent system activity found.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="table-data">
                <!-- Recent Expenses (All Users) -->
                <div class="order">
                    <div class="head">
                        <h3>Recent Expenses (All Users)</h3>
                        <a href="all_expenses.php" class="btn-link">View All Expenses</a>
                    </div>
                    <?php if (!empty($recent_expenses)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payee</th>
                                <th>Amount</th>
                                <th>Fund Type</th>
                                <th>Encoder</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_expenses as $expense): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($expense['date'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($expense['payee']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($expense['office_name']); ?></small>
                                </td>
                                <td>₱<?php echo number_format($expense['total'], 2); ?></td>
                                <td>
                                    <span class="status 
                                        <?php 
                                        echo $expense['fund_type'] === 'General Fund' ? 'completed' : 
                                            ($expense['fund_type'] === 'Special Education Fund' ? 'process' : 'pending'); 
                                        ?>">
                                        <?php echo htmlspecialchars($expense['fund_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($expense['encoder_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                        No recent expenses found.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Expense Type Breakdown -->
                <div class="order">
                    <div class="head">
                        <h3>Expense Type Breakdown - <?php echo $current_month_name; ?></h3>
                        <a href="reports.php" class="btn-link">Detailed Reports</a>
                    </div>
                    <?php if (!empty($expense_types)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Expense Type</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expense_types as $type): ?>
                            <?php 
                            $percentage = $total_monthly_amount > 0 ? ($type['total_amount'] / $total_monthly_amount) * 100 : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($type['expense_type']); ?></strong>
                                </td>
                                <td><?php echo number_format($type['count']); ?></td>
                                <td>₱<?php echo number_format($type['total_amount'], 2); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex-grow: 1; height: 6px; background: var(--grey); border-radius: 3px; overflow: hidden;">
                                            <div style="height: 100%; background: var(--blue); width: <?php echo $percentage; ?>%; transition: width 0.3s ease;"></div>
                                        </div>
                                        <span style="font-size: 12px; color: var(--dark-grey); min-width: 40px;">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                        No expense data found for this month.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Add some interactive features for the admin dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Add click tracking for admin actions
            const actionCards = document.querySelectorAll('.admin-action-card');
            actionCards.forEach(card => {
                card.addEventListener('click', function() {
                    const actionName = this.querySelector('h4').textContent;
                    console.log(`Admin action clicked: ${actionName}`);
                    // You can add analytics tracking here
                });
            });

            // Auto-refresh activity feed every 5 minutes
            setInterval(function() {
                // You can implement AJAX refresh here
                console.log('Activity feed refresh would happen here');
            }, 5 * 60 * 1000);

            // Add hover effects to activity items
            const activityItems = document.querySelectorAll('.activity-item');
            activityItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.background = 'var(--grey)';
                    this.style.borderRadius = '8px';
                    this.style.transition = 'all 0.2s ease';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.background = 'transparent';
                });
            });

            // Add system health check (you can expand this)
            function checkSystemHealth() {
                // This could make AJAX calls to check various system metrics
                console.log('System health check performed');
            }

            // Run system health check every 10 minutes
            setInterval(checkSystemHealth, 10 * 60 * 1000);
            checkSystemHealth(); // Run immediately on load
        });
    </script>
</body>
</html>