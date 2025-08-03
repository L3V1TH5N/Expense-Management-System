<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Get encoder-specific statistics
$user_id = $_SESSION['user_id'];
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';

// Get current month and year for filtering
$current_month = date('Y-m');

// Total expenses created by this encoder this month
$stmt = $conn->prepare("
    SELECT COUNT(*) as my_expenses 
    FROM expenses 
    WHERE created_by = ? 
    AND DATE_FORMAT(date, '%Y-%m') = ?
");
$stmt->execute([$user_id, $current_month]);
$my_expenses = $stmt->fetch()['my_expenses'];

// Monthly fund type totals for this encoder
$stmt = $conn->prepare("
    SELECT 
        fund_type,
        SUM(total) as fund_total,
        COUNT(*) as fund_count
    FROM expenses 
    WHERE created_by = ? 
    AND DATE_FORMAT(date, '%Y-%m') = ?
    GROUP BY fund_type
");
$stmt->execute([$user_id, $current_month]);
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

// Cash advances created by this encoder this month
$stmt = $conn->prepare("
    SELECT COUNT(*) as my_cash_advances 
    FROM expenses 
    WHERE created_by = ? 
    AND expense_type = 'Cash Advance'
    AND DATE_FORMAT(date, '%Y-%m') = ?
");
$stmt->execute([$user_id, $current_month]);
$my_cash_advances = $stmt->fetch()['my_cash_advances'];

// Recent expenses created by this encoder
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
$recent_expenses = $stmt->fetchAll();

// Recent activity logs for this encoder
$stmt = $pdo->prepare("
    SELECT al.*, u.full_name 
    FROM activity_logs al 
    JOIN users u ON al.user_id = u.id 
    WHERE al.user_id = ?
    ORDER BY al.action_time DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$my_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get month name for display
$current_month_name = date('F Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Encoder Dashboard - Expense Management System</title>
    <style>
		:root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-general: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            --gradient-sef: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            --gradient-trust: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            --gradient-total: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            --shadow-light: 0 4px 20px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
            --border-radius: 16px;
            --animation-speed: 0.3s;
        }
		/* Welcome Banner */
        .welcome-banner {
            background: var(--gradient-primary);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-content h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .welcome-content p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        /* Monthly Stats Header */
        .monthly-stats-header {
            text-align: center;
            margin-bottom: 1rem;
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

        /* Dashboard Specific Styles */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .action-card {
            background: var(--light);
            border-radius: 20px;
            padding: 24px;
            transition: all 0.3s ease;
            border: 1px solid var(--grey);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border-color: var(--blue);
        }

        .action-card a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: var(--dark);
            height: 100%;
        }

        .action-card i {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--blue);
            transition: all 0.3s ease;
        }

        .action-card:hover i {
            transform: scale(1.1);
        }

        .action-card h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .action-card p {
            color: var(--dark-grey);
            font-size: 14px;
            margin: 0;
        }

        .view-all {
            color: var(--blue);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            text-decoration: underline;
            color: var(--blue);
        }

        .btn-link {
            color: var(--blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .table-data .order table {
            min-width: 100%;
        }

        .table-data .order table td {
            padding: 12px 0;
        }

        .table-data .order table th {
            padding: 12px 0;
        }

        .status {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            background: var(--light-blue);
            color: var(--blue);
        }

        .status.completed {
            background: var(--light-blue);
            color: var(--blue);
        }

        .status.process {
            background: var(--light-yellow);
            color: var(--yellow);
        }

        .status.pending {
            background: var(--light-orange);
            color: var(--orange);
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .box-info li, .table-data > div {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .box-info li:nth-child(1) { animation-delay: 0.1s; }
        .box-info li:nth-child(2) { animation-delay: 0.2s; }
        .box-info li:nth-child(3) { animation-delay: 0.3s; }
        .box-info li:nth-child(4) { animation-delay: 0.4s; }
        .box-info li:nth-child(5) { animation-delay: 0.5s; }
        .box-info li:nth-child(6) { animation-delay: 0.6s; }
        .table-data > div:nth-child(1) { animation-delay: 0.7s; }
        .table-data > div:nth-child(2) { animation-delay: 0.8s; }

        /* Enhanced Stats Cards */
        .box-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .box-info li {
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

        .box-info li:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }

        /* Fund-specific styling */
        .box-info li.general-fund::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-general);
        }

        .box-info li.sef-fund::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-sef);
        }

        .box-info li.trust-fund::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-trust);
        }

        .box-info li.total-amount::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-total);
        }

        .box-info li.my-expenses::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--blue), #2980b9);
        }

        .box-info li.cash-advances::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .box-info li i {
            font-size: 32px;
            color: var(--blue);
            flex-shrink: 0;
        }

        .box-info li.general-fund i { color: #3498db; }
        .box-info li.sef-fund i { color: #e74c3c; }
        .box-info li.trust-fund i { color: #f39c12; }
        .box-info li.total-amount i { color: #27ae60; }
        .box-info li.cash-advances i { color: #ffc107; }

        .box-info li .text h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .box-info li .text p {
            color: var(--dark-grey);
            font-size: 13px;
            margin: 0;
            line-height: 1.3;
        }

        @media screen and (max-width: 768px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .action-card {
                padding: 20px;
            }

            .box-info {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            }

            .box-info li {
                padding: 16px;
                gap: 12px;
            }

            .box-info li i {
                font-size: 28px;
            }

            .box-info li .text h3 {
                font-size: 18px;
            }
        }

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
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-smile'></i>
            <span class="text"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        </a>
        <ul class="side-menu top">
            <li class="active">
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
                    <h1>Dashboard</h1>
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
            </div> <br>

			<!-- Welcome Banner -->
            <div class="welcome-banner fade-in">
                <div class="welcome-content">
                    <h2>Welcome back, <?php echo htmlspecialchars($full_name); ?>! 👋</h2>
                    <p>Here's what's happening with your expenses today</p>
                </div>
            </div>

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

			<br>

			<!-- Monthly Stats Header -->
            <div class="monthly-stats-header">
                <h3>📊 Monthly Statistics for <?php echo $current_month_name; ?></h3>
                <p>All amounts shown are for the current month and will reset at the beginning of each month</p>
            </div>

            <!-- Quick Actions -->
            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="quick-actions">
                        <div class="action-card">
                            <a href="encode.php">
                                <i class='bx bx-plus-circle'></i>
                                <h4>Encode New Expense</h4>
                                <p>Add a new expense entry</p>
                            </a>
                        </div>
                        <div class="action-card">
                            <a href="my_expenses.php">
                                <i class='bx bx-list-ul'></i>
                                <h4>View My Expenses</h4>
                                <p>See all expenses you've encoded</p>
                            </a>
                        </div>
                        <div class="action-card">
                            <a href="export.php">
                                <i class='bx bx-download'></i>
                                <h4>Export Reports</h4>
                                <p>Download expense reports</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
    
    <script src="../assets/js/script.js"></script>
</body>
</html>

