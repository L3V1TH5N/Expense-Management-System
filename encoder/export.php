<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle GET parameters for success/error messages
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get statistics for display
$stats = [];

// Total expenses by current user
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM expenses WHERE created_by = ?");
$stmt->execute([$user_id]);
$stats['total_expenses'] = $stmt->fetch()['total'];

// Issued checks count (excluding Cash Advance)
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM expenses 
    WHERE created_by = ? AND expense_type IN ('Personal Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay')
");
$stmt->execute([$user_id]);
$stats['issued_checks'] = $stmt->fetch()['count'];

// Cash advances count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM expenses WHERE created_by = ? AND expense_type = 'Cash Advance'");
$stmt->execute([$user_id]);
$stats['cash_advances'] = $stmt->fetch()['count'];

// Get statistics by fund type
$fund_stats = [];
$stmt = $conn->prepare("
    SELECT fund_type, COUNT(*) as count, SUM(total) as amount 
    FROM expenses 
    WHERE created_by = ? 
    GROUP BY fund_type
");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch()) {
    $fund_stats[$row['fund_type']] = [
        'count' => $row['count'],
        'amount' => $row['amount']
    ];
}

// Date range of available data
$stmt = $conn->prepare("
    SELECT MIN(date) as min_date, MAX(date) as max_date 
    FROM expenses WHERE created_by = ?
");
$stmt->execute([$user_id]);
$date_range = $stmt->fetch();

// Get available banks for the user
$stmt = $conn->prepare("SELECT DISTINCT bank FROM expenses WHERE created_by = ? ORDER BY bank");
$stmt->execute([$user_id]);
$available_banks = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Export Reports - Expense Management System</title>
    <style>
        /* Enhanced Modern Styles - Matching my_expenses.php design */
        .main-content {
            padding: 24px;
            background: var(--grey);
            min-height: calc(100vh - 56px);
        }

        .page-header {
            background: var(--light);
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-subtitle {
            color: var(--dark-grey);
            font-size: 16px;
            margin-bottom: 24px;
        }

        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }

        /* Statistics Cards */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--light);
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--blue), #2980b9);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .stat-card.general::before {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .stat-card.sef::before {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .stat-card.trust::before {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }

        .stat-icon {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--blue);
            opacity: 0.8;
        }

        .stat-card.general .stat-icon {
            color: #28a745;
        }

        .stat-card.sef .stat-icon {
            color: #ffc107;
        }

        .stat-card.trust .stat-icon {
            color: #dc3545;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--dark-grey);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-amount {
            font-size: 16px;
            font-weight: 600;
            color: var(--blue);
            margin-top: 8px;
        }

        /* Export Sections */
        .export-section {
            background: var(--light);
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
            overflow: hidden;
        }

        .export-header {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .export-header.cash {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .export-icon {
            font-size: 36px;
            opacity: 0.9;
        }

        .export-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .export-description {
            font-size: 14px;
            opacity: 0.9;
        }

        .export-body {
            padding: 32px;
        }

        /* Form Styles */
        .filter-section {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 2px solid #e9ecef;
            position: relative;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, var(--blue), #2980b9);
            border-radius: 16px 16px 0 0;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-title i {
            color: var(--blue);
            font-size: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-row.full-width {
            grid-template-columns: 1fr;
        }

        .form-row.half-width {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--blue);
            font-size: 16px;
        }

        .form-control {
            padding: 12px 16px;
            border: 2px solid var(--grey);
            border-radius: 12px;
            font-size: 14px;
            font-family: var(--poppins);
            transition: all 0.3s ease;
            background: var(--light);
            color: var(--dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(60, 145, 230, 0.1);
        }

        .form-control:required {
            border-left: 4px solid var(--blue);
        }

        /* Period Options */
        .period-section {
            margin-bottom: 24px;
        }

        .period-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .period-label i {
            color: var(--blue);
        }

        .period-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .period-option {
            position: relative;
        }

        .period-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .period-option label {
            display: block;
            padding: 16px 20px;
            border: 2px solid var(--grey);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 14px;
            background: var(--light);
            color: var(--dark);
            position: relative;
            overflow: hidden;
        }

        .period-option label::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(60, 145, 230, 0.1), transparent);
            transition: left 0.5s;
        }

        .period-option input[type="radio"]:checked + label {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            border-color: var(--blue);
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.3);
        }

        .period-option label:hover::before {
            left: 100%;
        }

        .period-option label:hover {
            border-color: var(--blue);
            transform: translateY(-2px);
        }

        /* Period Fields */
        .period-fields {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            margin-bottom: 20px;
        }

        /* Preview Section */
        .preview-section {
            background: linear-gradient(135deg, rgba(60, 145, 230, 0.05), rgba(60, 145, 230, 0.02));
            padding: 20px;
            border-radius: 12px;
            border: 2px dashed var(--blue);
            margin-bottom: 24px;
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .preview-section.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .preview-info {
            text-align: center;
            color: var(--blue);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .preview-info i {
            font-size: 20px;
        }

        /* Export Button */
        .btn-export {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            font-family: var(--poppins);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            min-width: 200px;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.3);
        }

        .btn-export::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-export:hover::before {
            left: 100%;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(60, 145, 230, 0.4);
        }

        .btn-export:disabled,
        .btn-export.loading {
            background: var(--grey);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-export.cash {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-export.cash:hover {
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        /* Alert Messages */
        .alert {
            padding: 20px 24px;
            margin-bottom: 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert-info {
            background: linear-gradient(135deg, #cce7ff, #b3d9ff);
            color: #004085;
            border-left-color: #007bff;
        }

        .alert i {
            font-size: 20px;
        }

        /* Date Info */
        .date-info-card {
            background: linear-gradient(135deg, rgba(60, 145, 230, 0.05), rgba(60, 145, 230, 0.02));
            border: 2px solid rgba(60, 145, 230, 0.1);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            text-align: center;
        }

        .date-info-icon {
            font-size: 32px;
            color: var(--blue);
            margin-bottom: 12px;
        }

        .date-info-text {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .date-range {
            color: var(--blue);
            font-weight: 700;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: var(--light);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .empty-state-icon {
            font-size: 80px;
            color: var(--blue);
            opacity: 0.3;
            margin-bottom: 24px;
        }

        .empty-state h3 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 16px;
            color: var(--dark-grey);
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(60, 145, 230, 0.4);
        }

        /* Responsive Design */
        @media screen and (max-width: 1024px) {
            .stats-overview {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
            
            .form-row {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .period-options {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }
        }

        @media screen and (max-width: 768px) {
            .main-content {
                padding: 16px;
            }

            .page-header {
                padding: 24px;
            }

            .page-title {
                font-size: 24px;
            }

            .export-body {
                padding: 24px;
            }

            .filter-section {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .period-options {
                grid-template-columns: 1fr;
            }

            .stats-overview {
                grid-template-columns: 1fr;
            }

            .btn-export {
                padding: 14px 24px;
                font-size: 14px;
            }
        }

        @media screen and (max-width: 576px) {
            .export-header {
                padding: 20px;
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }

            .export-icon {
                font-size: 48px;
            }

            .period-option label {
                padding: 12px 16px;
                font-size: 13px;
            }

            .form-control {
                padding: 10px 14px;
            }
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
            <li class="active">
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

    <!-- CONTENT -->
    <section id="content">
        <nav>
            <i class='bx bx-menu'></i>
            <span class="nav-text">Expense Management System</span>
        </nav>

        <main class="main-content">
            <!-- Page Header -->
            <div class="head-title">
                <div class="left">
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Export Reports</a>
                        </li>
                    </ul>
                </div>
                <div class="right">
                    <a href="my_expenses.php" class="btn-download">
                        <i class='bx bx-receipt'></i>
                        <span class="text">View Expenses</span>
                    </a>
                </div>
            </div>
            <br>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class='bx bx-check-circle'></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class='bx bx-error'></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Overview -->
            <div class="stats-overview">
                <div class="stat-card general">
                    <i class='bx bxs-building-house stat-icon'></i>
                    <div class="stat-number"><?php echo number_format($fund_stats['General Fund']['count'] ?? 0); ?></div>
                    <div class="stat-label">General Fund</div>
                    <?php if (isset($fund_stats['General Fund']['amount'])): ?>
                        <div class="stat-amount">₱<?php echo number_format($fund_stats['General Fund']['amount'], 2); ?></div>
                    <?php endif; ?>
                </div>
                <div class="stat-card sef">
                    <i class='bx bxs-graduation stat-icon'></i>
                    <div class="stat-number"><?php echo number_format($fund_stats['Special Education Fund']['count'] ?? 0); ?></div>
                    <div class="stat-label">Special Education Fund</div>
                    <?php if (isset($fund_stats['Special Education Fund']['amount'])): ?>
                        <div class="stat-amount">₱<?php echo number_format($fund_stats['Special Education Fund']['amount'], 2); ?></div>
                    <?php endif; ?>
                </div>
                <div class="stat-card trust">
                    <i class='bx bxs-hand stat-icon'></i>
                    <div class="stat-number"><?php echo number_format($fund_stats['Trust Fund']['count'] ?? 0); ?></div>
                    <div class="stat-label">Trust Fund</div>
                    <?php if (isset($fund_stats['Trust Fund']['amount'])): ?>
                        <div class="stat-amount">₱<?php echo number_format($fund_stats['Trust Fund']['amount'], 2); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($stats['total_expenses'] == 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class='bx bx-receipt'></i>
                    </div>
                    <h3>No Expenses Found</h3>
                    <p>You haven't encoded any expenses yet. Create your first expense to generate reports.</p>
                    <a href="encode.php" class="btn-primary">
                        <i class='bx bx-plus'></i>
                        Encode Your First Expense
                    </a>
                </div>
            <?php else: ?>
                <?php if ($date_range['min_date'] && $date_range['max_date']): ?>
                    <div class="date-info-card">
                        <div class="date-info-icon">
                            <i class='bx bx-calendar-check'></i>
                        </div>
                        <div class="date-info-text">
                            <strong>Available Data Range:</strong><br>
                            <span class="date-range">
                                <?php echo date('M j, Y', strtotime($date_range['min_date'])); ?> to 
                                <?php echo date('M j, Y', strtotime($date_range['max_date'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Export Issued Checks -->
                <div class="export-section">
                    <div class="export-header">
                        <i class='bx bxs-check-circle export-icon'></i>
                        <div>
                            <div class="export-title">Export Issued Checks</div>
                            <div class="export-description">Personal Services, Maintenance and Other Operating Expenses, Capital Outlay</div>
                        </div>
                    </div>
                    
                    <div class="export-body">
                        <form id="issuedChecksForm" method="POST" action="generate_report.php">
                            <input type="hidden" name="report_type" value="issued_checks">
                            
                            <!-- Filter Section -->
                            <div class="filter-section">
                                <div class="filter-title">
                                    <i class='bx bx-filter-alt'></i>
                                    Report Filters
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="issued_fund_type">
                                            <i class='bx bx-wallet'></i>
                                            Fund Type
                                        </label>
                                        <select id="issued_fund_type" name="fund_type" class="form-control" required>
                                            <option value="">Select Fund Type</option>
                                            <option value="General Fund">General Fund</option>
                                            <option value="Special Education Fund">Special Education Fund (SEF)</option>
                                            <option value="Trust Fund">Trust Fund</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="issued_bank">
                                            <i class='bx bx-bank'></i>
                                            Bank
                                        </label>
                                        <select id="issued_bank" name="bank" class="form-control" required>
                                            <option value="">Select Bank</option>
                                            <?php foreach ($available_banks as $bank): ?>
                                            <option value="<?php echo htmlspecialchars($bank); ?>">
                                                <?php echo htmlspecialchars($bank); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="issued_expense_type">
                                            <i class='bx bx-category'></i>
                                            Expense Type
                                        </label>
                                        <select id="issued_expense_type" name="expense_type" class="form-control">
                                            <option value="">All Issued Checks Types</option>
                                            <option value="Personal Services">Personal Services</option>
                                            <option value="Maintenance and Other Operating Expenses">Maintenance and Other Operating Expenses</option>
                                            <option value="Capital Outlay">Capital Outlay</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Period Selection -->
                            <div class="period-section">
                                <div class="period-label">
                                    <i class='bx bx-time'></i>
                                    Time Period
                                </div>
                                <div class="period-options">
                                    <div class="period-option">
                                        <input type="radio" id="issued_monthly" name="period_type" value="monthly" checked>
                                        <label for="issued_monthly">Monthly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="issued_quarterly" name="period_type" value="quarterly">
                                        <label for="issued_quarterly">Quarterly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="issued_yearly" name="period_type" value="yearly">
                                        <label for="issued_yearly">Yearly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="issued_custom" name="period_type" value="custom">
                                        <label for="issued_custom">Custom Range</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="issued_monthly_fields">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="issued_month">
                                            <i class='bx bx-calendar'></i>
                                            Month
                                        </label>
                                        <select id="issued_month" name="month" class="form-control">
                                            <option value="">Select Month</option>
                                            <option value="1">January</option>
                                            <option value="2">February</option>
                                            <option value="3">March</option>
                                            <option value="4">April</option>
                                            <option value="5">May</option>
                                            <option value="6">June</option>
                                            <option value="7">July</option>
                                            <option value="8">August</option>
                                            <option value="9">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="issued_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="issued_year" name="year" class="form-control">
                                            <option value="">Select Year</option>
                                            <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="issued_quarterly_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="issued_quarter">
                                            <i class='bx bx-calendar'></i>
                                            Quarter
                                        </label>
                                        <select id="issued_quarter" name="quarter" class="form-control">
                                            <option value="">Select Quarter</option>
                                            <option value="1">Q1 (Jan-Mar)</option>
                                            <option value="2">Q2 (Apr-Jun)</option>
                                            <option value="3">Q3 (Jul-Sep)</option>
                                            <option value="4">Q4 (Oct-Dec)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="issued_quarter_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="issued_quarter_year" name="quarter_year" class="form-control">
                                            <option value="">Select Year</option>
                                            <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="issued_yearly_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="issued_yearly_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="issued_yearly_year" name="yearly_year" class="form-control">
                                            <option value="">Select Year</option>
                                            <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="issued_custom_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="issued_date_from">
                                            <i class='bx bx-calendar'></i>
                                            From Date
                                        </label>
                                        <input type="date" id="issued_date_from" name="date_from" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="issued_date_to">
                                            <i class='bx bx-calendar-check'></i>
                                            To Date
                                        </label>
                                        <input type="date" id="issued_date_to" name="date_to" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preview Section -->
                            <div class="preview-section" id="issued_preview">
                                <div class="preview-info">
                                    <i class='bx bx-info-circle'></i>
                                    <span id="issued_preview_text">Select filters to preview export criteria</span>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <button type="submit" class="btn-export" id="exportIssuedBtn">
                                        <i class='bx bxs-download'></i>
                                        Export Issued Checks
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Export Cash Advances -->
                <div class="export-section">
                    <div class="export-header cash">
                        <i class='bx bxs-credit-card export-icon'></i>
                        <div>
                            <div class="export-title">Export Cash Advances</div>
                            <div class="export-description">Cash advance transactions for liquidation tracking</div>
                        </div>
                    </div>
                    
                    <div class="export-body">
                        <form id="cashAdvancesForm" method="POST" action="generate_report.php">
                            <input type="hidden" name="report_type" value="cash_advances">
                            
                            <!-- Filter Section -->
                            <div class="filter-section">
                                <div class="filter-title">
                                    <i class='bx bx-filter-alt'></i>
                                    Report Filters
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="cash_fund_type">
                                            <i class='bx bx-wallet'></i>
                                            Fund Type
                                        </label>
                                        <select id="cash_fund_type" name="fund_type" class="form-control" required>
                                            <option value="">Select Fund Type</option>
                                            <option value="General Fund">General Fund</option>
                                            <option value="Special Education Fund">Special Education Fund (SEF)</option>
                                            <option value="Trust Fund">Trust Fund</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="cash_bank">
                                            <i class='bx bx-bank'></i>
                                            Bank
                                        </label>
                                        <select id="cash_bank" name="bank" class="form-control" required>
                                            <option value="">Select Bank</option>
                                            <?php foreach ($available_banks as $bank): ?>
                                            <option value="<?php echo htmlspecialchars($bank); ?>">
                                                <?php echo htmlspecialchars($bank); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Period Selection -->
                            <div class="period-section">
                                <div class="period-label">
                                    <i class='bx bx-time'></i>
                                    Time Period
                                </div>
                                <div class="period-options">
                                    <div class="period-option">
                                        <input type="radio" id="cash_monthly" name="period_type" value="monthly" checked>
                                        <label for="cash_monthly">Monthly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="cash_quarterly" name="period_type" value="quarterly">
                                        <label for="cash_quarterly">Quarterly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="cash_yearly" name="period_type" value="yearly">
                                        <label for="cash_yearly">Yearly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="cash_custom" name="period_type" value="custom">
                                        <label for="cash_custom">Custom Range</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="cash_monthly_fields">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="cash_month">
                                            <i class='bx bx-calendar'></i>
                                            Month
                                        </label>
                                        <select id="cash_month" name="month" class="form-control">
                                            <option value="">Select Month</option>
                                            <option value="1">January</option>
                                            <option value="2">February</option>
                                            <option value="3">March</option>
                                            <option value="4">April</option>
                                            <option value="5">May</option>
                                            <option value="6">June</option>
                                            <option value="7">July</option>
                                            <option value="8">August</option>
                                            <option value="9">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="cash_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="cash_year" name="year" class="form-control">
                                            <option value="">Select Year</option>
                                            <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="cash_quarterly_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="cash_quarter">
                                            <i class='bx bx-calendar'></i>
                                            Quarter
                                        </label>
                                        <select id="cash_quarter" name="quarter" class="form-control">
                                            <option value="">Select Quarter</option>
                                            <option value="1">Q1 (Jan-Mar)</option>
                                            <option value="2">Q2 (Apr-Jun)</option>
                                            <option value="3">Q3 (Jul-Sep)</option>
                                            <option value="4">Q4 (Oct-Dec)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="cash_quarter_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="cash_quarter_year" name="quarter_year" class="form-control">
                                            <option value="">Select Year</option>
                                            <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="cash_yearly_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="cash_yearly_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="cash_yearly_year" name="yearly_year" class="form-control">
                                            <option value="">Select Year</option>
                                            <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="cash_custom_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="cash_date_from">
                                            <i class='bx bx-calendar'></i>
                                            From Date
                                        </label>
                                        <input type="date" id="cash_date_from" name="date_from" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="cash_date_to">
                                            <i class='bx bx-calendar-check'></i>
                                            To Date
                                        </label>
                                        <input type="date" id="cash_date_to" name="date_to" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preview Section -->
                            <div class="preview-section" id="cash_preview">
                                <div class="preview-info">
                                    <i class='bx bx-info-circle'></i>
                                    <span id="cash_preview_text">Select filters to preview export criteria</span>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <button type="submit" class="btn-export cash" id="exportCashBtn">
                                        <i class='bx bxs-download'></i>
                                        Export Cash Advances
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </section>

    <script src="../assets/js/script.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle period type changes for issued checks
            const issuedPeriodTypes = document.querySelectorAll('#issuedChecksForm input[name="period_type"]');
            const issuedMonthlyFields = document.getElementById('issued_monthly_fields');
            const issuedQuarterlyFields = document.getElementById('issued_quarterly_fields');
            const issuedYearlyFields = document.getElementById('issued_yearly_fields');
            const issuedCustomFields = document.getElementById('issued_custom_fields');
            
            function toggleIssuedFields() {
                const selectedType = document.querySelector('#issuedChecksForm input[name="period_type"]:checked').value;
                
                // Hide all fields first
                issuedMonthlyFields.style.display = 'none';
                issuedQuarterlyFields.style.display = 'none';
                issuedYearlyFields.style.display = 'none';
                issuedCustomFields.style.display = 'none';
                
                // Show relevant fields
                switch(selectedType) {
                    case 'monthly':
                        issuedMonthlyFields.style.display = 'block';
                        break;
                    case 'quarterly':
                        issuedQuarterlyFields.style.display = 'block';
                        break;
                    case 'yearly':
                        issuedYearlyFields.style.display = 'block';
                        break;
                    case 'custom':
                        issuedCustomFields.style.display = 'block';
                        break;
                }
                
                updateIssuedPreview();
            }
            
            issuedPeriodTypes.forEach(radio => {
                radio.addEventListener('change', toggleIssuedFields);
            });
            
            // Handle period type changes for cash advances
            const cashPeriodTypes = document.querySelectorAll('#cashAdvancesForm input[name="period_type"]');
            const cashMonthlyFields = document.getElementById('cash_monthly_fields');
            const cashQuarterlyFields = document.getElementById('cash_quarterly_fields');
            const cashYearlyFields = document.getElementById('cash_yearly_fields');
            const cashCustomFields = document.getElementById('cash_custom_fields');
            
            function toggleCashFields() {
                const selectedType = document.querySelector('#cashAdvancesForm input[name="period_type"]:checked').value;
                
                // Hide all fields first
                cashMonthlyFields.style.display = 'none';
                cashQuarterlyFields.style.display = 'none';
                cashYearlyFields.style.display = 'none';
                cashCustomFields.style.display = 'none';
                
                // Show relevant fields
                switch(selectedType) {
                    case 'monthly':
                        cashMonthlyFields.style.display = 'block';
                        break;
                    case 'quarterly':
                        cashQuarterlyFields.style.display = 'block';
                        break;
                    case 'yearly':
                        cashYearlyFields.style.display = 'block';
                        break;
                    case 'custom':
                        cashCustomFields.style.display = 'block';
                        break;
                }
                
                updateCashPreview();
            }
            
            cashPeriodTypes.forEach(radio => {
                radio.addEventListener('change', toggleCashFields);
            });
            
            // Preview update functions
            function updateIssuedPreview() {
                const fundType = document.getElementById('issued_fund_type').value;
                const bank = document.getElementById('issued_bank').value;
                const expenseType = document.getElementById('issued_expense_type').value;
                const periodType = document.querySelector('#issuedChecksForm input[name="period_type"]:checked').value;
                
                let preview = [];
                
                if (fundType) preview.push(`Fund: ${fundType}`);
                if (bank) preview.push(`Bank: ${bank}`);
                if (expenseType) preview.push(`Type: ${expenseType}`);
                else preview.push('Type: All Issued Checks');
                
                let periodText = '';
                switch(periodType) {
                    case 'monthly':
                        const month = document.getElementById('issued_month').value;
                        const year = document.getElementById('issued_year').value;
                        if (month && year) {
                            const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                                             'July', 'August', 'September', 'October', 'November', 'December'];
                            periodText = `${monthNames[parseInt(month)]} ${year}`;
                        }
                        break;
                    case 'quarterly':
                        const quarter = document.getElementById('issued_quarter').value;
                        const qYear = document.getElementById('issued_quarter_year').value;
                        if (quarter && qYear) {
                            periodText = `Q${quarter} ${qYear}`;
                        }
                        break;
                    case 'yearly':
                        const yYear = document.getElementById('issued_yearly_year').value;
                        if (yYear) {
                            periodText = `Year ${yYear}`;
                        }
                        break;
                    case 'custom':
                        const fromDate = document.getElementById('issued_date_from').value;
                        const toDate = document.getElementById('issued_date_to').value;
                        if (fromDate && toDate) {
                            periodText = `${fromDate} to ${toDate}`;
                        }
                        break;
                }
                
                if (periodText) preview.push(`Period: ${periodText}`);
                
                const previewElement = document.getElementById('issued_preview');
                const previewText = document.getElementById('issued_preview_text');
                
                if (preview.length > 0) {
                    previewText.textContent = `Export criteria: ${preview.join(' | ')}`;
                    previewElement.classList.add('show');
                } else {
                    previewText.textContent = 'Select filters to preview export criteria';
                    previewElement.classList.remove('show');
                }
            }
            
            function updateCashPreview() {
                const fundType = document.getElementById('cash_fund_type').value;
                const bank = document.getElementById('cash_bank').value;
                const periodType = document.querySelector('#cashAdvancesForm input[name="period_type"]:checked').value;
                
                let preview = [];
                
                if (fundType) preview.push(`Fund: ${fundType}`);
                if (bank) preview.push(`Bank: ${bank}`);
                preview.push('Type: Cash Advances');
                
                let periodText = '';
                switch(periodType) {
                    case 'monthly':
                        const month = document.getElementById('cash_month').value;
                        const year = document.getElementById('cash_year').value;
                        if (month && year) {
                            const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                                             'July', 'August', 'September', 'October', 'November', 'December'];
                            periodText = `${monthNames[parseInt(month)]} ${year}`;
                        }
                        break;
                    case 'quarterly':
                        const quarter = document.getElementById('cash_quarter').value;
                        const qYear = document.getElementById('cash_quarter_year').value;
                        if (quarter && qYear) {
                            periodText = `Q${quarter} ${qYear}`;
                        }
                        break;
                    case 'yearly':
                        const yYear = document.getElementById('cash_yearly_year').value;
                        if (yYear) {
                            periodText = `Year ${yYear}`;
                        }
                        break;
                    case 'custom':
                        const fromDate = document.getElementById('cash_date_from').value;
                        const toDate = document.getElementById('cash_date_to').value;
                        if (fromDate && toDate) {
                            periodText = `${fromDate} to ${toDate}`;
                        }
                        break;
                }
                
                if (periodText) preview.push(`Period: ${periodText}`);
                
                const previewElement = document.getElementById('cash_preview');
                const previewText = document.getElementById('cash_preview_text');
                
                if (preview.length > 0) {
                    previewText.textContent = `Export criteria: ${preview.join(' | ')}`;
                    previewElement.classList.add('show');
                } else {
                    previewText.textContent = 'Select filters to preview export criteria';
                    previewElement.classList.remove('show');
                }
            }
            
            // Add event listeners for preview updates
            document.querySelectorAll('#issuedChecksForm select, #issuedChecksForm input').forEach(element => {
                element.addEventListener('change', updateIssuedPreview);
            });
            
            document.querySelectorAll('#cashAdvancesForm select, #cashAdvancesForm input').forEach(element => {
                element.addEventListener('change', updateCashPreview);
            });
            
            // Form validation and submission for issued checks
            document.getElementById('issuedChecksForm').addEventListener('submit', function(e) {
                const fundType = document.getElementById('issued_fund_type').value;
                const bank = document.getElementById('issued_bank').value;
                const periodType = document.querySelector('#issuedChecksForm input[name="period_type"]:checked').value;
                
                let isValid = true;
                let errorMessage = '';
                
                // Validate required filters
                if (!fundType) {
                    isValid = false;
                    errorMessage = 'Please select a fund type.';
                } else if (!bank) {
                    isValid = false;
                    errorMessage = 'Please select a bank.';
                } else {
                    // Validate period fields
                    switch(periodType) {
                        case 'monthly':
                            if (!document.getElementById('issued_month').value || !document.getElementById('issued_year').value) {
                                isValid = false;
                                errorMessage = 'Please select both month and year.';
                            }
                            break;
                        case 'quarterly':
                            if (!document.getElementById('issued_quarter').value || !document.getElementById('issued_quarter_year').value) {
                                isValid = false;
                                errorMessage = 'Please select both quarter and year.';
                            }
                            break;
                        case 'yearly':
                            if (!document.getElementById('cash_yearly_year').value) {
                                isValid = false;
                                errorMessage = 'Please select a year.';
                            }
                            break;
                        case 'custom':
                            const fromDate = document.getElementById('cash_date_from').value;
                            const toDate = document.getElementById('cash_date_to').value;
                            
                            if (!fromDate || !toDate) {
                                isValid = false;
                                errorMessage = 'Please select both from and to dates.';
                            } else if (new Date(fromDate) > new Date(toDate)) {
                                isValid = false;
                                errorMessage = 'From date cannot be later than to date.';
                            }
                            break;
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                    return false;
                }
                
                // Show loading state
                const submitBtn = document.getElementById('exportCashBtn');
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Generating Report...';
                submitBtn.disabled = true;
            });
            
            // Set default values
            const currentMonth = new Date().getMonth() + 1;
            document.getElementById('issued_month').value = currentMonth;
            document.getElementById('cash_month').value = currentMonth;
            
            const currentQuarter = Math.ceil(currentMonth / 3);
            document.getElementById('issued_quarter').value = currentQuarter;
            document.getElementById('cash_quarter').value = currentQuarter;
            
            // Initialize the forms
            toggleIssuedFields();
            toggleCashFields();

            // Add smooth animations and enhanced interactions
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-in');
            });

            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn-export, .period-option label');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255,255,255,0.5);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add CSS for animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
                @keyframes slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .animate-in {
                    animation: slideInUp 0.6s ease-out forwards;
                }
                
                .export-section {
                    animation: slideInUp 0.6s ease-out forwards;
                }
                
                .export-section:nth-child(even) {
                    animation-delay: 0.2s;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>