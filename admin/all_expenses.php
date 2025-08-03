<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search and filter variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$fund_type = isset($_GET['fund_type']) ? sanitizeInput($_GET['fund_type']) : '';
$expense_type = isset($_GET['expense_type']) ? sanitizeInput($_GET['expense_type']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

// Build the base query
$query = "
    SELECT 
        e.*, 
        o.name as office_name, 
        so.name as sub_office_name,
        u.full_name as encoder_name
    FROM expenses e 
    LEFT JOIN offices o ON e.office_id = o.id 
    LEFT JOIN offices so ON e.sub_office_id = so.id 
    LEFT JOIN users u ON e.created_by = u.id
";

// Build the where conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(e.payee LIKE ? OR e.check_number LIKE ? OR e.bank LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($fund_type)) {
    $conditions[] = "e.fund_type = ?";
    $params[] = $fund_type;
}

if (!empty($expense_type)) {
    $conditions[] = "e.expense_type = ?";
    $params[] = $expense_type;
}

if (!empty($date_from) && validateDate($date_from)) {
    $conditions[] = "e.date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to) && validateDate($date_to)) {
    $conditions[] = "e.date <= ?";
    $params[] = $date_to;
}

// Add conditions to query if any
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Add sorting and pagination
$query .= " ORDER BY e.date DESC, e.created_at DESC LIMIT $offset, $per_page";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM expenses e";
if (!empty($conditions)) {
    $count_query .= " WHERE " . implode(" AND ", $conditions);
}
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_expenses = $count_stmt->fetch()['total'];
$total_pages = ceil($total_expenses / $per_page);

// Get all fund types for filter dropdown
$fund_types = ['General Fund', 'Special Education Fund', 'Trust Fund'];

// Get all expense types for filter dropdown
$expense_types = [
    'Personal Services',
    'Maintenance and Other Operating Expenses',
    'Capital Outlay',
    'Cash Advance'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>All Expenses - Expense Management System</title>
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
            --shadow-light: 0 4px 20px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
            --border-radius: 16px;
            --animation-speed: 0.3s;
        }

        /* Enhanced Filter Form */
        .filter-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--grey);
            position: relative;
            overflow: hidden;
        }

        .filter-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            color: var(--dark);
        }

        .filter-header i {
            font-size: 24px;
            color: var(--blue);
        }

        .filter-header h3 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .filter-form {
            display: grid;
            gap: 24px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-group label i {
            font-size: 16px;
            color: var(--blue);
        }
        
        .filter-group input, 
        .filter-group select {
            padding: 12px 16px;
            border: 2px solid var(--grey);
            border-radius: 12px;
            font-size: 14px;
            transition: all var(--animation-speed) ease;
            background: white;
            color: var(--dark);
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(60, 145, 230, 0.1);
            transform: translateY(-1px);
        }

        .filter-group input:hover,
        .filter-group select:hover {
            border-color: var(--blue);
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-start;
            margin-top: 8px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all var(--animation-speed) ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-light);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: var(--shadow-light);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* Enhanced Stats Summary */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--light);
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--grey);
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
            transition: all var(--animation-speed) ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-info);
        }

        .stat-card i {
            font-size: 36px;
            color: var(--blue);
            flex-shrink: 0;
        }

        .stat-card .text h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .stat-card .text p {
            color: var(--dark-grey);
            font-size: 14px;
            margin: 0;
        }
        
        /* Enhanced Table Styling */
        .table-container {
            background: var(--light);
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            overflow: hidden;
            border: 1px solid var(--grey);
        }

        .table-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 24px;
            border-bottom: 1px solid var(--grey);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .table-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-header h3 i {
            color: var(--blue);
            font-size: 24px;
        }

        .record-count {
            background: var(--blue);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        
        .expense-table th {
            background: var(--grey);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            border-bottom: 2px solid #dee2e6;
        }

        .expense-table td {
            padding: 16px;
            border-bottom: 1px solid var(--grey);
            vertical-align: middle;
        }

        .expense-table tbody tr {
            transition: all var(--animation-speed) ease;
        }

        .expense-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.001);
        }
        
        /* Enhanced Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge-general-fund {
            background: var(--gradient-general);
            color: white;
        }
        
        .badge-special-education-fund {
            background: var(--gradient-sef);
            color: white;
        }
        
        .badge-trust-fund {
            background: var(--gradient-trust);
            color: white;
        }

        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .view-expense {
            color: var(--blue);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all var(--animation-speed) ease;
            border: 1px solid var(--blue);
        }
        
        .view-expense:hover {
            background: var(--blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(60, 145, 230, 0.3);
        }

        /* Enhanced Pagination */
        .pagination-container {
            padding: 24px;
            background: #f8f9fa;
            border-top: 1px solid var(--grey);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        
        .pagination a, 
        .pagination span {
            padding: 10px 16px;
            border: 1px solid var(--grey);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all var(--animation-speed) ease;
            min-width: 44px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .pagination a {
            color: var(--blue);
            background: white;
        }
        
        .pagination a:hover {
            background: var(--blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-light);
        }
        
        .pagination .current {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--blue);
            box-shadow: var(--shadow-light);
        }

        /* Enhanced Export Options */
        .export-container {
            padding: 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-top: 1px solid var(--grey);
            border-radius: 0 0 20px 20px;
        }

        .export-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .export-header i {
            font-size: 20px;
            color: var(--blue);
        }

        .export-header h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .export-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-export {
            background: var(--gradient-success);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all var(--animation-speed) ease;
            box-shadow: var(--shadow-light);
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 64px 24px;
            color: var(--dark-grey);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .empty-state p {
            font-size: 14px;
            margin: 0;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }

            .stats-summary {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .expense-table {
                font-size: 13px;
            }

            .expense-table th,
            .expense-table td {
                padding: 12px 8px;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 4px;
            }

            .export-options {
                flex-direction: column;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .filter-container,
        .stats-summary .stat-card,
        .table-container {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .stats-summary .stat-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .stats-summary .stat-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        .table-container {
            animation-delay: 0.3s;
        }

        /* Brand styling consistency */
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
            <li class="active">
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
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">All Expenses</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <!-- Enhanced Filter Container -->
            <div class="filter-container">
                <div class="filter-header">
                    <i class='bx bx-filter-alt'></i>
                    <h3>Filter & Search Expenses</h3>
                </div>
                <form method="GET" action="all_expenses.php" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">
                                <i class='bx bx-search'></i>
                                Search
                            </label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Payee, Check Number, Bank...">
                        </div>
                        <div class="filter-group">
                            <label for="fund_type">
                                <i class='bx bx-wallet'></i>
                                Fund Type
                            </label>
                            <select id="fund_type" name="fund_type">
                                <option value="">All Fund Types</option>
                                <?php foreach ($fund_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $fund_type === $type ? 'selected' : ''; ?>>
                                        <?php echo $type; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="expense_type">
                                <i class='bx bx-category'></i>
                                Expense Type
                            </label>
                            <select id="expense_type" name="expense_type">
                                <option value="">All Expense Types</option>
                                <?php foreach ($expense_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $expense_type === $type ? 'selected' : ''; ?>>
                                        <?php echo $type; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="date_from">
                                <i class='bx bx-calendar'></i>
                                Date From
                            </label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="date_to">
                                <i class='bx bx-calendar'></i>
                                Date To
                            </label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-search'></i>
                            Apply Filters
                        </button>
                        <a href="all_expenses.php" class="btn btn-secondary">
                            <i class='bx bx-refresh'></i>
                            Reset Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Stats Summary -->
            <div class="stats-summary">
                <div class="stat-card">
                    <i class='bx bx-receipt'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_expenses); ?></h3>
                        <p>Total Records Found</p>
                    </span>
                </div>
                <div class="stat-card">
                    <i class='bx bx-file'></i>
                    <span class="text">
                        <h3><?php echo count($expenses); ?></h3>
                        <p>Records on This Page</p>
                    </span>
                </div>
                <div class="stat-card">
                    <i class='bx bx-collection'></i>
                    <span class="text">
                        <h3><?php echo $total_pages; ?></h3>
                        <p>Total Pages</p>
                    </span>
                </div>
            </div>

            <!-- Enhanced Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class='bx bx-list-ul'></i>
                        Expense Records
                    </h3>
                    <div class="record-count">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </div>
                </div>
                
                <?php if (!empty($expenses)): ?>
                <div style="overflow-x: auto;">
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th><i class='bx bx-calendar'></i> Date</th>
                                <th><i class='bx bx-user'></i> Payee</th>
                                <th><i class='bx bx-check'></i> Check #</th>
                                <th><i class='bx bx-money'></i> Amount</th>
                                <th><i class='bx bx-wallet'></i> Fund Type</th>
                                <th><i class='bx bx-user-check'></i> Encoder</th>
                                <th><i class='bx bx-cog'></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M j, Y', strtotime($expense['date'])); ?></strong><br>
                                    <small style="color: var(--dark-grey);"><?php echo date('l', strtotime($expense['date'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($expense['payee']); ?></strong>
                                    <?php if (!empty($expense['office_name'])): ?>
                                        <br><small style="color: var(--dark-grey);"><?php echo htmlspecialchars($expense['office_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="background: var(--grey); padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo htmlspecialchars($expense['check_number']); ?>
                                    </code>
                                </td>
                                <td>
                                    <strong style="color: var(--blue); font-size: 16px;">
                                        ₱<?php echo number_format($expense['total'], 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $expense['fund_type'])); ?>">
                                        <i class='bx bx-wallet'></i>
                                        <?php echo htmlspecialchars($expense['fund_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 32px; height: 32px; background: var(--blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px;">
                                            <?php echo strtoupper(substr($expense['encoder_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($expense['encoder_name']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_expense.php?id=<?php echo $expense['id']; ?>" class="view-expense">
                                            <i class='bx bx-show'></i>
                                            View Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Enhanced Pagination -->
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" title="First Page">
                                <i class='bx bx-chevrons-left'></i>
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" title="Previous Page">
                                <i class='bx bx-chevron-left'></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" title="Next Page">
                                <i class='bx bx-chevron-right'></i>
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" title="Last Page">
                                <i class='bx bx-chevrons-right'></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Export Options 
                <div class="export-container">
                    <div class="export-header">
                        <i class='bx bx-download'></i>
                        <h4>Export Data</h4>
                    </div>
                    <div class="export-options">
                        <a href="export_expenses.php?<?php echo http_build_query($_GET); ?>&format=csv" class="btn-export">
                            <i class='bx bx-file'></i>
                            Export to CSV
                        </a>
                        <a href="export_expenses.php?<?php echo http_build_query($_GET); ?>&format=excel" class="btn-export">
                            <i class='bx bx-file-blank'></i>
                            Export to Excel
                        </a>
                        <a href="export_expenses.php?<?php echo http_build_query($_GET); ?>&format=pdf" class="btn-export" style="background: var(--gradient-danger);">
                            <i class='bx bx-file-pdf'></i>
                            Export to PDF
                        </a>
                    </div>
                </div>  -->


                
                <?php else: ?>
                <!-- Enhanced Empty State -->
                <div class="empty-state">
                    <i class='bx bx-search-alt'></i>
                    <h4>No Expenses Found</h4>
                    <p>No expenses match your current search criteria. Try adjusting your filters or search terms.</p>
                    <div style="margin-top: 20px;">
                        <a href="all_expenses.php" class="btn btn-primary">
                            <i class='bx bx-refresh'></i>
                            Clear All Filters
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script>
        // Enhanced JavaScript for better user experience
        document.addEventListener('DOMContentLoaded', function() {
            // Animate table rows on load
            const tableRows = document.querySelectorAll('.expense-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });

            // Auto-submit form on date change for better UX
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Optional: Auto-submit after date selection
                    // this.form.submit();
                });
            });

            // Add loading state to filter form
            const filterForm = document.querySelector('.filter-form');
            if (filterForm) {
                filterForm.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Searching...';
                        submitBtn.style.opacity = '0.7';
                    }
                });
            }

            // Enhanced hover effects for action buttons
            const actionButtons = document.querySelectorAll('.view-expense');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.05)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add smooth scrolling to pagination
            const paginationLinks = document.querySelectorAll('.pagination a');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Add loading state to pagination
                    this.style.opacity = '0.7';
                    this.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>';
                });
            });

            // Enhanced search functionality with debounce
            const searchInput = document.getElementById('search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const searchTerm = this.value;
                    
                    // Visual feedback
                    if (searchTerm.length > 0) {
                        this.style.borderColor = 'var(--blue)';
                        this.style.boxShadow = '0 0 0 3px rgba(60, 145, 230, 0.1)';
                    } else {
                        this.style.borderColor = 'var(--grey)';
                        this.style.boxShadow = 'none';
                    }
                    
                    // Optional: Implement live search with debounce
                    // searchTimeout = setTimeout(() => {
                    //     if (searchTerm.length >= 3 || searchTerm.length === 0) {
                    //         // Trigger search
                    //     }
                    // }, 500);
                });
            }

            // Add tooltips to action buttons
            const viewButtons = document.querySelectorAll('.view-expense');
            viewButtons.forEach(button => {
                button.setAttribute('title', 'Click to view full expense details');
            });

            // Enhanced export button interactions
            const exportButtons = document.querySelectorAll('.btn-export');
            exportButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Add download animation
                    const icon = this.querySelector('i');
                    const originalClass = icon.className;
                    icon.className = 'bx bx-loader-alt bx-spin';
                    
                    setTimeout(() => {
                        icon.className = 'bx bx-check';
                        setTimeout(() => {
                            icon.className = originalClass;
                        }, 2000);
                    }, 1000);
                });
            });

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + F to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    const searchInput = document.getElementById('search');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
                
                // Enter key on filter inputs to submit
                if (e.key === 'Enter' && e.target.closest('.filter-form')) {
                    const form = e.target.closest('.filter-form');
                    if (form && e.target.tagName !== 'BUTTON') {
                        e.preventDefault();
                        form.submit();
                    }
                }
            });

            // Add progress indicator for long operations
            function showProgress() {
                const progressBar = document.createElement('div');
                progressBar.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 4px;
                    background: linear-gradient(90deg, var(--blue) 0%, var(--light-blue) 100%);
                    z-index: 9999;
                    animation: progress 2s ease-in-out infinite;
                `;
                
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes progress {
                        0% { transform: translateX(-100%); }
                        100% { transform: translateX(100vw); }
                    }
                `;
                document.head.appendChild(style);
                document.body.appendChild(progressBar);
                
                return progressBar;
            }

            // Monitor form submissions and show progress
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const progressBar = showProgress();
                    
                    // Remove progress bar after 3 seconds (fallback)
                    setTimeout(() => {
                        if (progressBar.parentNode) {
                            progressBar.remove();
                        }
                    }, 3000);
                });
            });

            // Add smooth reveal animation for stats cards
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe stat cards for animation
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `all 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });

            // Add real-time clock in the header (optional enhancement)
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: true,
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // You could add this to the nav if desired
                // const clockElement = document.querySelector('.nav-clock');
                // if (clockElement) {
                //     clockElement.textContent = timeString;
                // }
            }

            // Update clock every minute
            updateClock();
            setInterval(updateClock, 60000);

            console.log('All Expenses page enhanced with improved UX features loaded successfully!');
        });
    </script>
</body>
</html>