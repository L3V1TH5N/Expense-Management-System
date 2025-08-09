<?php include 'functions/export.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Export Reports - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/export.css">
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-wallet'></i>
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
                    <span class="text">Expenses Records</span>
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

                <!-- Export Statement of Expenses -->
                <div class="export-section">
                    <div class="export-header statement">
                        <i class='bx bxs-report export-icon'></i>
                        <div>
                            <div class="export-title">Statement of Expenses</div>
                            <div class="export-description">Office-wise expense breakdown by fund type</div>
                        </div>
                    </div>
                    
                    <div class="export-body">
                        <div class="statement-info">
                            <div class="statement-note">
                                <i class='bx bx-info-circle'></i>
                                <div>
                                    <strong>About Statement of Expenses:</strong><br>
                                    This report shows all offices with their expenditures for Personnel Services, 
                                    Maintenance and Other Operating Expenses, and Capital Outlay, organized by fund type 
                                    for the selected period. Perfect for financial reporting.
                                </div>
                            </div>
                        </div>

                        <form id="statementForm" method="POST" action="generate_report.php">
                            <input type="hidden" name="report_type" value="statement_of_expenses">
                            
                            <!-- Filter Section -->
                            <div class="filter-section">
                                <div class="filter-title">
                                    <i class='bx bx-filter-alt'></i>
                                    Report Filters
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="statement_fund_type">
                                            <i class='bx bx-wallet'></i>
                                            Fund Type (Optional)
                                        </label>
                                        <select id="statement_fund_type" name="fund_type" class="form-control">
                                            <option value="">All Fund Types</option>
                                            <option value="General Fund">General Fund</option>
                                            <option value="Special Education Fund">Special Education Fund (SEF)</option>
                                            <option value="Trust Fund">Trust Fund</option>
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
                                        <input type="radio" id="statement_monthly" name="period_type" value="monthly" checked>
                                        <label for="statement_monthly">Monthly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="statement_quarterly" name="period_type" value="quarterly">
                                        <label for="statement_quarterly">Quarterly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="statement_yearly" name="period_type" value="yearly">
                                        <label for="statement_yearly">Yearly</label>
                                    </div>
                                    <div class="period-option">
                                        <input type="radio" id="statement_custom" name="period_type" value="custom">
                                        <label for="statement_custom">Custom Range</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="period-fields" id="statement_monthly_fields">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="statement_month">
                                            <i class='bx bx-calendar'></i>
                                            Month
                                        </label>
                                        <select id="statement_month" name="month" class="form-control">
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
                                        <label for="statement_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="statement_year" name="year" class="form-control">
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
                            
                            <div class="period-fields" id="statement_quarterly_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="statement_quarter">
                                            <i class='bx bx-calendar'></i>
                                            Quarter
                                        </label>
                                        <select id="statement_quarter" name="quarter" class="form-control">
                                            <option value="">Select Quarter</option>
                                            <option value="1">Q1 (Jan-Mar)</option>
                                            <option value="2">Q2 (Apr-Jun)</option>
                                            <option value="3">Q3 (Jul-Sep)</option>
                                            <option value="4">Q4 (Oct-Dec)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="statement_quarter_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="statement_quarter_year" name="quarter_year" class="form-control">
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
                            
                            <div class="period-fields" id="statement_yearly_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="statement_yearly_year">
                                            <i class='bx bx-calendar-alt'></i>
                                            Year
                                        </label>
                                        <select id="statement_yearly_year" name="yearly_year" class="form-control">
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
                            
                            <div class="period-fields" id="statement_custom_fields" style="display: none;">
                                <div class="form-row half-width">
                                    <div class="form-group">
                                        <label for="statement_date_from">
                                            <i class='bx bx-calendar'></i>
                                            From Date
                                        </label>
                                        <input type="date" id="statement_date_from" name="date_from" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="statement_date_to">
                                            <i class='bx bx-calendar-check'></i>
                                            To Date
                                        </label>
                                        <input type="date" id="statement_date_to" name="date_to" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preview Section -->
                            <div class="preview-section" id="statement_preview">
                                <div class="preview-info">
                                    <i class='bx bx-info-circle'></i>
                                    <span id="statement_preview_text">Select period to preview export criteria</span>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <button type="submit" class="btn-export statement" id="exportStatementBtn">
                                        <i class='bx bxs-download'></i>
                                        Export Statement of Expenses
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Export Issued Checks -->
                <!-- Export Issued Checks - Updated Section -->
<div class="export-section">
    <div class="export-header">
        <i class='bx bxs-check-circle export-icon'></i>
        <div>
            <div class="export-title">Export All Issued Checks</div>
            <div class="export-description">All checks including Personnel Services, MOOE, Capital Outlay, and Cash Advances</div>
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
                            Expense Type (Optional)
                        </label>
                        <select id="issued_expense_type" name="expense_type" class="form-control">
                            <option value="">All Check Types</option>
                            <option value="Personnel Services">Personnel Services</option>
                            <option value="Maintenance and Other Operating Expenses">Maintenance and Other Operating Expenses</option>
                            <option value="Capital Outlay">Capital Outlay</option>
                            <option value="Cash Advance">Cash Advance</option>
                            <option value="Others">Others</option>
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
                        Export All Issued Checks
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
    <script src="functions/js/export.js"></script>
    
</body>
</html>
