<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Default report period - current month
$current_month = date('Y-m');
$current_month_name = date('F Y');
$month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get available years for dropdown
$stmt = $conn->prepare("SELECT DISTINCT YEAR(date) as year FROM expenses ORDER BY year DESC");
$stmt->execute();
$years = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Get fund types
$fund_types = ['General Fund', 'Special Education Fund', 'Trust Fund'];

// Get expense types
$expense_types = [
    'Personal Services',
    'Maintenance and Other Operating Expenses',
    'Capital Outlay',
    'Cash Advance'
];

// Generate monthly report data
function generateMonthlyReport($conn, $month, $year) {
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Total expenses for the month
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as count,
            SUM(total) as total_amount
        FROM expenses
        WHERE date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $monthly_totals = $stmt->fetch();
    
    // By fund type
    $stmt = $conn->prepare("
        SELECT 
            fund_type,
            COUNT(*) as count,
            SUM(total) as total_amount
        FROM expenses
        WHERE date BETWEEN ? AND ?
        GROUP BY fund_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $by_fund_type = $stmt->fetchAll();
    
    // By expense type
    $stmt = $conn->prepare("
        SELECT 
            expense_type,
            COUNT(*) as count,
            SUM(total) as total_amount
        FROM expenses
        WHERE date BETWEEN ? AND ?
        GROUP BY expense_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $by_expense_type = $stmt->fetchAll();
    
    // By office
    $stmt = $conn->prepare("
        SELECT 
            o.name as office_name,
            COUNT(*) as count,
            SUM(e.total) as total_amount
        FROM expenses e
        LEFT JOIN offices o ON e.office_id = o.id
        WHERE e.date BETWEEN ? AND ?
        GROUP BY o.name
        ORDER BY total_amount DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $by_office = $stmt->fetchAll();
    
    // By encoder
    $stmt = $conn->prepare("
        SELECT 
            u.full_name as encoder_name,
            COUNT(*) as count,
            SUM(e.total) as total_amount
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.date BETWEEN ? AND ?
        GROUP BY u.full_name
        ORDER BY total_amount DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $by_encoder = $stmt->fetchAll();
    
    return [
        'monthly_totals' => $monthly_totals,
        'by_fund_type' => $by_fund_type,
        'by_expense_type' => $by_expense_type,
        'by_office' => $by_office,
        'by_encoder' => $by_encoder,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

// Generate annual report data
function generateAnnualReport($conn, $year) {
    $start_date = "$year-01-01";
    $end_date = "$year-12-31";
    
    // Monthly breakdown
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(total) as total_amount
        FROM expenses
        WHERE date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$start_date, $end_date]);
    $monthly_breakdown = $stmt->fetchAll();
    
    // By fund type
    $stmt = $conn->prepare("
        SELECT 
            fund_type,
            COUNT(*) as count,
            SUM(total) as total_amount
        FROM expenses
        WHERE date BETWEEN ? AND ?
        GROUP BY fund_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $by_fund_type = $stmt->fetchAll();
    
    // By expense type
    $stmt = $conn->prepare("
        SELECT 
            expense_type,
            COUNT(*) as count,
            SUM(total) as total_amount
        FROM expenses
        WHERE date BETWEEN ? AND ?
        GROUP BY expense_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $by_expense_type = $stmt->fetchAll();
    
    return [
        'monthly_breakdown' => $monthly_breakdown,
        'by_fund_type' => $by_fund_type,
        'by_expense_type' => $by_expense_type,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

// Determine which report to show
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';

if ($report_type === 'monthly') {
    $report_data = generateMonthlyReport($conn, $month, $year);
    $report_title = "Monthly Report for " . date('F Y', strtotime($report_data['start_date']));
} else {
    $report_data = generateAnnualReport($conn, $year);
    $report_title = "Annual Report for $year";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Reports - Expense Management System</title>
    <style>
        /* Enhanced Report Form Design */
        .report-form {
            background: var(--light);
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
        }
        
        .report-tabs {
            display: flex;
            background: var(--grey);
            border-radius: 36px;
            padding: 4px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .report-tab {
            flex: 1;
            padding: 12px 24px;
            text-align: center;
            cursor: pointer;
            border-radius: 32px;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--dark-grey);
            position: relative;
        }
        
        .report-tab.active {
            background: var(--blue);
            color: var(--light);
            box-shadow: 0 2px 10px rgba(60, 145, 230, 0.3);
        }
        
        .report-tab:hover:not(.active) {
            color: var(--blue);
            background: rgba(60, 145, 230, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-group select, .form-group input {
            padding: 12px 16px;
            border: 2px solid var(--grey);
            border-radius: 10px;
            background: var(--light);
            color: var(--dark);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(60, 145, 230, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--blue);
            color: var(--light);
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.2);
        }
        
        .btn-primary:hover {
            background: #2980d9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(60, 145, 230, 0.3);
        }
        
        /* Enhanced Report Sections */
        .report-header {
            background: linear-gradient(135deg, var(--blue), #2980d9);
            color: var(--light);
            padding: 30px 24px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 8px 30px rgba(60, 145, 230, 0.2);
        }
        
        .report-header h2 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        
        .report-header .report-meta {
            opacity: 0.9;
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .report-header .report-summary {
            background: rgba(255, 255, 255, 0.1);
            padding: 16px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .report-section {
            background: var(--light);
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
        }
        
        .report-section h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .report-section h3 i {
            color: var(--blue);
            font-size: 24px;
        }
        
        /* Enhanced Tables */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 0 1px var(--grey);
        }
        
        .report-table th {
            background: var(--blue);
            color: var(--light);
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .report-table td {
            padding: 16px 12px;
            border-bottom: 1px solid var(--grey);
            color: var(--dark);
            vertical-align: middle;
        }
        
        .report-table tbody tr:hover {
            background: rgba(60, 145, 230, 0.05);
        }
        
        .report-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Enhanced Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-general-fund {
            background: var(--light-blue);
            color: var(--blue);
        }
        
        .badge-special-education-fund {
            background: var(--light-orange);
            color: var(--orange);
        }
        
        .badge-trust-fund {
            background: var(--light-yellow);
            color: var(--yellow);
        }
        
        /* Chart Containers */
        .chart-container {
            background: var(--grey);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            height: 400px;
            position: relative;
        }
        
        .chart-wrapper {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--light);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--blue);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--blue);
            margin-bottom: 4px;
        }
        
        .stat-card .stat-label {
            font-size: 14px;
            color: var(--dark-grey);
            font-weight: 500;
        }
        
        /* Progress Bars for Percentages */
        .progress-bar {
            background: var(--grey);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--blue);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .report-form {
                padding: 16px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .report-tabs {
                flex-direction: column;
                gap: 4px;
            }
            
            .report-header {
                padding: 20px 16px;
            }
            
            .report-header h2 {
                font-size: 24px;
            }
            
            .chart-container {
                height: 300px;
            }
        }
        
        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }
        
        /* Loading States */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: var(--light);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark-grey);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-shield-alt-2'></i>
            <span class="text">Admin Panel</span>
        </a>
        <ul class="side-menu top">
            <li>
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
            <li class="active">
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
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Reports</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>
            
            <!-- Report Generator Form -->
            <div class="report-form">
                <form method="GET" action="reports.php" id="reportForm">
                    <div class="report-tabs">
                        <div class="report-tab <?php echo $report_type === 'monthly' ? 'active' : ''; ?>" 
                             onclick="switchReportType('monthly')">
                            <i class='bx bx-calendar'></i> Monthly Report
                        </div>
                        <div class="report-tab <?php echo $report_type === 'annual' ? 'active' : ''; ?>" 
                             onclick="switchReportType('annual')">
                            <i class='bx bx-calendar-alt'></i> Annual Report
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <?php if ($report_type === 'monthly'): ?>
                        <div class="form-group">
                            <label for="month"><i class='bx bx-calendar-week'></i> Select Month</label>
                            <select id="month" name="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                        <?php echo $month === str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="year"><i class='bx bx-calendar-event'></i> Select Year</label>
                            <select id="year" name="year">
                                <?php foreach ($years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-refresh'></i> Generate Report
                    </button>
                </form>
            </div>

            <!-- Report Header -->
            <div class="report-header">
                <h2><i class='bx bxs-report'></i> <?php echo $report_title; ?></h2>
                <div class="report-meta">
                    <i class='bx bx-calendar'></i> Report period: <?php echo date('M j, Y', strtotime($report_data['start_date'])); ?> to <?php echo date('M j, Y', strtotime($report_data['end_date'])); ?>
                </div>
                
                <?php if ($report_type === 'monthly'): ?>
                    <div class="report-summary">
                        <i class='bx bx-money'></i> Total: <?php echo $report_data['monthly_totals']['count']; ?> expenses • ₱<?php echo number_format($report_data['monthly_totals']['total_amount'], 2); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($report_type === 'monthly'): ?>
                <!-- Monthly Report Content -->
                
                <!-- Fund Type Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-wallet'></i> Expenses by Fund Type</h3>
                    <?php if (!empty($report_data['by_fund_type'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="fundTypeChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-category'></i> Fund Type</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_fund_type'] as $fund): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $fund['fund_type'])); ?>">
                                            <?php echo $fund['fund_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($fund['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($fund['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $report_data['monthly_totals']['total_amount'] > 0 ? 
                                            ($fund['total_amount'] / $report_data['monthly_totals']['total_amount']) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-buildings'></i>
                            <p>No office data available for this period</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Top Encoders -->
                <div class="report-section">
                    <h3><i class='bx bxs-user-badge'></i> Top Encoders</h3>
                    <?php if (!empty($report_data['by_encoder'])): ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-user'></i> Encoder</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_encoder'] as $encoder): ?>
                                <tr>
                                    <td><strong><?php echo $encoder['encoder_name']; ?></strong></td>
                                    <td><?php echo number_format($encoder['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($encoder['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $report_data['monthly_totals']['total_amount'] > 0 ? 
                                            ($encoder['total_amount'] / $report_data['monthly_totals']['total_amount']) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-user'></i>
                            <p>No encoder data available for this period</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Annual Report Content -->
                
                <!-- Monthly Breakdown -->
                <div class="report-section">
                    <h3><i class='bx bxs-calendar'></i> Monthly Breakdown</h3>
                    <?php if (!empty($report_data['monthly_breakdown'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="monthlyBreakdownChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-calendar'></i> Month</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $annual_total = array_reduce($report_data['monthly_breakdown'], function($carry, $item) {
                                    return $carry + $item['total_amount'];
                                }, 0);
                                
                                foreach ($report_data['monthly_breakdown'] as $month_data): ?>
                                <tr>
                                    <td><strong><?php echo date('F Y', strtotime($month_data['month'] . '-01')); ?></strong></td>
                                    <td><?php echo number_format($month_data['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($month_data['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $annual_total > 0 ? 
                                            ($month_data['total_amount'] / $annual_total) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-calendar'></i>
                            <p>No monthly data available for this year</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Annual Fund Type Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-wallet'></i> Expenses by Fund Type</h3>
                    <?php if (!empty($report_data['by_fund_type'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="annualFundTypeChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-category'></i> Fund Type</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_fund_type'] as $fund): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $fund['fund_type'])); ?>">
                                            <?php echo $fund['fund_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($fund['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($fund['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $annual_total > 0 ? 
                                            ($fund['total_amount'] / $annual_total) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-wallet'></i>
                            <p>No fund type data available for this year</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Annual Expense Type Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-category'></i> Expenses by Type</h3>
                    <?php if (!empty($report_data['by_expense_type'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="annualExpenseTypeChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-list-ul'></i> Expense Type</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_expense_type'] as $type): ?>
                                <tr>
                                    <td><?php echo $type['expense_type']; ?></td>
                                    <td><?php echo number_format($type['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($type['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $annual_total > 0 ? 
                                            ($type['total_amount'] / $annual_total) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-category'></i>
                            <p>No expense type data available for this year</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script>
        // Report Type Switching Function
        function switchReportType(type) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('report_type', type);
            if (type === 'annual') {
                currentUrl.searchParams.delete('month');
            }
            window.location.href = currentUrl.toString();
        }

        // Chart.js Configuration
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.color = '#342E37';

        // Monthly Report Charts
        <?php if ($report_type === 'monthly'): ?>
            <?php if (!empty($report_data['by_fund_type'])): ?>
            // Fund Type Chart
            const fundTypeCtx = document.getElementById('fundTypeChart').getContext('2d');
            const fundTypeChart = new Chart(fundTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['fund_type'] . "'"; }, $report_data['by_fund_type'])); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_fund_type'])); ?>],
                        backgroundColor: [
                            '#3C91E6', // General Fund
                            '#FD7238', // SEF  
                            '#FFCE26'  // Trust Fund
                        ],
                        borderWidth: 3,
                        borderColor: '#FFFFFF',
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 14,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            <?php if (!empty($report_data['by_expense_type'])): ?>
            // Expense Type Chart
            const expenseTypeCtx = document.getElementById('expenseTypeChart').getContext('2d');
            const expenseTypeChart = new Chart(expenseTypeCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['expense_type'] . "'"; }, $report_data['by_expense_type'])); ?>],
                    datasets: [{
                        label: 'Amount (₱)',
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_expense_type'])); ?>],
                        backgroundColor: '#3C91E6',
                        borderColor: '#2980d9',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ₱${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

        <?php else: ?>
            // Annual Report Charts
            <?php if (!empty($report_data['monthly_breakdown'])): ?>
            // Monthly Breakdown Chart
            const monthlyBreakdownCtx = document.getElementById('monthlyBreakdownChart').getContext('2d');
            const monthlyBreakdownChart = new Chart(monthlyBreakdownCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M', strtotime($item['month'] . '-01')) . "'"; }, $report_data['monthly_breakdown'])); ?>],
                    datasets: [{
                        label: 'Amount (₱)',
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['monthly_breakdown'])); ?>],
                        borderColor: '#3C91E6',
                        backgroundColor: 'rgba(60, 145, 230, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#3C91E6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ₱${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            <?php if (!empty($report_data['by_fund_type'])): ?>
            // Annual Fund Type Chart
            const annualFundTypeCtx = document.getElementById('annualFundTypeChart').getContext('2d');
            const annualFundTypeChart = new Chart(annualFundTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['fund_type'] . "'"; }, $report_data['by_fund_type'])); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_fund_type'])); ?>],
                        backgroundColor: [
                            '#3C91E6', // General Fund
                            '#FD7238', // SEF
                            '#FFCE26'  // Trust Fund
                        ],
                        borderWidth: 3,
                        borderColor: '#FFFFFF',
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 14,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            <?php if (!empty($report_data['by_expense_type'])): ?>
            // Annual Expense Type Chart
            const annualExpenseTypeCtx = document.getElementById('annualExpenseTypeChart').getContext('2d');
            const annualExpenseTypeChart = new Chart(annualExpenseTypeCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['expense_type'] . "'"; }, $report_data['by_expense_type'])); ?>],
                    datasets: [{
                        label: 'Amount (₱)',
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_expense_type'])); ?>],
                        backgroundColor: '#3C91E6',
                        borderColor: '#2980d9',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ₱${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        <?php endif; ?>

        // Form Enhancement
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading"></div> Generating...';
            submitBtn.disabled = true;
            
            // Re-enable after a delay (in case of errors)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, index * 100);
            });
        });
    </script>
</body>
</html>
                        
