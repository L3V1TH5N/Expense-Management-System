<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Get user ID from session
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

// Get filter parameters
$search = $_GET['search'] ?? '';
$expense_type_filter = $_GET['expense_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE conditions
$where_conditions = ['created_by = ?'];
$params = [$user_id];

if (!empty($search)) {
    $where_conditions[] = "(payee LIKE ? OR check_number LIKE ? OR expense_type LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($expense_type_filter)) {
    $where_conditions[] = "expense_type = ?";
    $params[] = $expense_type_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Validate sort parameters
$valid_sort_columns = ['date', 'payee', 'expense_type', 'total', 'created_at'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'created_at';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM expenses WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get expenses with pagination
$sql = "
    SELECT e.*, o.name as office_name, so.name as sub_office_name 
    FROM expenses e 
    LEFT JOIN offices o ON e.office_id = o.id 
    LEFT JOIN offices so ON e.sub_office_id = so.id 
    WHERE {$where_clause}
    ORDER BY e.{$sort_by} {$sort_order}
    LIMIT {$per_page} OFFSET {$offset}
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all offices for filter dropdown
$stmt = $conn->prepare("SELECT id, name, parent_id FROM offices ORDER BY parent_id, name");
$stmt->execute();
$all_offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group offices by parent_id for editing
$offices_by_parent = [];
foreach ($all_offices as $office) {
    $parent_id = $office['parent_id'] ?? 'main';
    if (!isset($offices_by_parent[$parent_id])) {
        $offices_by_parent[$parent_id] = [];
    }
    $offices_by_parent[$parent_id][] = $office;
}

// Get main offices
$main_offices = $offices_by_parent['main'] ?? [];

// Helper function for pagination URLs
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>My Expenses - Expense Management System</title>
    <style>
        /* Enhanced Modern Styles */
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

        .filters-card {
            background: var(--light);
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .filter-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
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
            gap: 6px;
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

        .filter-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: var(--poppins);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(60, 145, 230, 0.4);
        }
        
        .btn-secondary {
            background: var(--grey);
            color: var(--dark);
            border: 2px solid var(--dark-grey);
        }
        
        .btn-secondary:hover {
            background: var(--dark-grey);
            color: var(--light);
        }

        .results-card {
            background: var(--light);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
        }

        .results-header {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .results-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .results-count {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .table-container {
            padding: 0;
            overflow-x: auto;
        }
        
        .expense-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--poppins);
        }
        
        .expense-table th {
            background: var(--grey);
            color: var(--dark);
            padding: 20px 24px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            border-bottom: 2px solid var(--blue);
        }
        
        .expense-table th.sortable {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
        }
        
        .expense-table th.sortable:hover {
            background: var(--dark-grey);
            color: var(--light);
        }
        
        .expense-table th.sortable::after {
            content: '⇅';
            position: absolute;
            right: 12px;
            top: 50%;
            opacity: 0.5;
            font-size: 16px;
        }
        
        .expense-table th.sortable.asc::after {
            content: '↑';
            opacity: 1;
            color: var(--blue);
        }
        
        .expense-table th.sortable.desc::after {
            content: '↓';
            opacity: 1;
            color: var(--blue);
        }
        
        .expense-table td {
            padding: 20px 24px;
            border-bottom: 1px solid var(--grey);
            vertical-align: middle;
            font-size: 14px;
        }
        
        .expense-table tr {
            transition: all 0.3s ease;
        }

        .expense-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(60, 145, 230, 0.05), rgba(60, 145, 230, 0.02));
        }

        .expense-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .expense-primary {
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }

        .expense-secondary {
            color: var(--dark-grey);
            font-size: 13px;
        }

        .check-number {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, var(--light-blue), rgba(60, 145, 230, 0.1));
            color: var(--blue);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
        }

        .expense-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .badge-personal {
            background: var(--light-blue);
            color: var(--blue);
        }

        .badge-maintenance {
            background: var(--light-yellow);
            color: var(--yellow);
        }

        .badge-capital {
            background: var(--light-orange);
            color: var(--orange);
        }

        .badge-cash {
            background: rgba(219, 80, 74, 0.1);
            color: var(--red);
        }

        .amount-display {
            text-align: right;
            font-weight: 700;
            color: var(--blue);
            font-size: 16px;
        }

        .tax-info {
            color: var(--dark-grey);
            font-size: 12px;
            font-weight: 500;
        }

        .office-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .office-main {
            font-weight: 600;
            color: var(--dark);
        }

        .office-sub {
            color: var(--dark-grey);
            font-size: 13px;
        }

        .date-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-primary {
            font-weight: 600;
            color: var(--dark);
        }

        .date-time {
            color: var(--dark-grey);
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            min-width: 80px;
            justify-content: center;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        
        .btn-edit:hover {
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }

        .pagination-container {
            padding: 32px;
            background: var(--light);
            border-top: 1px solid var(--grey);
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .pagination a, .pagination span {
            padding: 12px 16px;
            border: 2px solid var(--grey);
            text-decoration: none;
            color: var(--dark);
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            min-width: 44px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pagination a:hover {
            background: var(--blue);
            color: white;
            border-color: var(--blue);
            box-shadow: 0 4px 12px rgba(60, 145, 230, 0.3);
        }
        
        .pagination .current {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            border-color: var(--blue);
            box-shadow: 0 4px 12px rgba(60, 145, 230, 0.3);
        }
        
        .pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
            background: var(--grey);
        }

        .no-data {
            text-align: center;
            padding: 80px 40px;
            color: var(--dark-grey);
        }
        
        .no-data-icon {
            font-size: 80px;
            margin-bottom: 24px;
            opacity: 0.3;
            color: var(--blue);
        }

        .no-data h3 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .no-data p {
            font-size: 16px;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .alert {
            padding: 20px 24px;
            margin-bottom: 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert i {
            font-size: 20px;
        }

        /* Modal Enhancement */
        .modal {
            display: none;
            font-family: var(--poppins);
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: var(--light);
            margin: 3% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--blue), #2980b9);
            color: white;
            padding: 24px 32px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            padding: 4px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 32px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .modal-footer {
            padding: 24px 32px;
            border-top: 2px solid var(--grey);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: var(--grey);
            border-radius: 0 0 20px 20px;
        }


        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }

        /* Responsive Design */
        @media screen and (max-width: 1024px) {
            .filter-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
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

            .filters-card {
                padding: 24px;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .filter-actions {
                justify-content: center;
            }

            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .expense-table {
                font-size: 12px;
            }
            
            .expense-table th,
            .expense-table td {
                padding: 12px 8px;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .modal-header, .modal-body, .modal-footer {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        @media screen and (max-width: 576px) {
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }

            .pagination a, .pagination span {
                padding: 8px 12px;
                font-size: 12px;
                min-width: 36px;
            }

            .actions {
                flex-direction: column;
                gap: 4px;
            }

            .btn-action {
                min-width: 70px;
                padding: 6px 12px;
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
            <li class="active">
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
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="#">Expenses</a>
						</li>
					</ul>
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

            <!-- Filters Card -->
            <div class="filters-card">
                <div class="filter-header">
                    <i class='bx bx-filter-alt'></i>
                    <span class="filter-title">Filter Expenses</span>
                </div>
                
                <form method="GET" id="filterForm">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="search">
                                <i class='bx bx-search'></i>
                                Search
                            </label>
                            <input type="text" id="search" name="search" class="form-control" 
                                   placeholder="Search payee, check number, or type..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="expense_type">
                                <i class='bx bx-category'></i>
                                Expense Type
                            </label>
                            <select id="expense_type" name="expense_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="Personal Services" <?php echo $expense_type_filter === 'Personal Services' ? 'selected' : ''; ?>>Personal Services</option>
                                <option value="Maintenance and Other Operating Expenses" <?php echo $expense_type_filter === 'Maintenance and Other Operating Expenses' ? 'selected' : ''; ?>>Maintenance & Operating</option>
                                <option value="Capital Outlay" <?php echo $expense_type_filter === 'Capital Outlay' ? 'selected' : ''; ?>>Capital Outlay</option>
                                <option value="Cash Advance" <?php echo $expense_type_filter === 'Cash Advance' ? 'selected' : ''; ?>>Cash Advance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_from">
                                <i class='bx bx-calendar'></i>
                                Date From
                            </label>
                            <input type="date" id="date_from" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">
                                <i class='bx bx-calendar-check'></i>
                                Date To
                            </label>
                            <input type="date" id="date_to" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-search'></i>
                            Apply Filters
                        </button>
                        
                        <a href="my_expenses.php" class="btn btn-secondary">
                            <i class='bx bx-x'></i>
                            Clear Filters
                        </a>
                        
                        <a href="encode.php" class="btn btn-primary">
                            <i class='bx bx-plus'></i>
                            Add New Expense
                        </a>
                    </div>
                    
                    <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
                    <input type="hidden" name="page" value="1">
                </form>
            </div>

            <!-- Results Card -->
            <div class="results-card">
                <div class="results-header">
                    <div class="results-title">
                        <i class='bx bxs-data'></i>
                        Expense Records
                    </div>
                    <div class="results-count">
                        <?php if ($total_records > 0): ?>
                            Showing <?php echo min(($page - 1) * $per_page + 1, $total_records); ?> - 
                            <?php echo min($page * $per_page, $total_records); ?> of 
                            <?php echo number_format($total_records); ?> expenses
                        <?php else: ?>
                            No expenses found
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="table-container">
                    <?php if (count($expenses) > 0): ?>
                        <table class="expense-table">
                            <thead>
                                <tr>
                                    <th class="sortable <?php echo $sort_by === 'date' ? strtolower($sort_order) : ''; ?>" 
                                        onclick="sortTable('date')">
                                        <i class='bx bx-calendar'></i> Date
                                    </th>
                                    <th>
                                        <i class='bx bx-receipt'></i> Check #
                                    </th>
                                    <th class="sortable <?php echo $sort_by === 'payee' ? strtolower($sort_order) : ''; ?>" 
                                        onclick="sortTable('payee')">
                                        <i class='bx bx-user'></i> Payee
                                    </th>
                                    <th class="sortable <?php echo $sort_by === 'expense_type' ? strtolower($sort_order) : ''; ?>" 
                                        onclick="sortTable('expense_type')">
                                        <i class='bx bx-category'></i> Type
                                    </th>
                                    <th>
                                        <i class='bx bx-building'></i> Office
                                    </th>
                                    <th class="sortable <?php echo $sort_by === 'total' ? strtolower($sort_order) : ''; ?>" 
                                        onclick="sortTable('total')">
                                        <i class='bx bx-money'></i> Amount
                                    </th>
                                    <th>
                                        <i class='bx bx-cog'></i> Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td>
                                            <div class="date-info">
                                                <div class="date-primary">
                                                    <?= date('M j, Y', strtotime($expense['date'])) ?>
                                                </div>
                                                <div class="date-time">
                                                    <?= date('g:i A', strtotime($expense['created_at'])) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="check-number">
                                                <?= htmlspecialchars($expense['check_number']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="expense-item">
                                                <div class="expense-primary">
                                                    <?= htmlspecialchars($expense['payee']) ?>
                                                </div>
                                                <div class="expense-secondary">
                                                    <?= htmlspecialchars($expense['fund_type']) ?> • <?= htmlspecialchars($expense['bank']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = 'badge-personal';
                                            switch($expense['expense_type']) {
                                                case 'Personal Services':
                                                    $badgeClass = 'badge-personal';
                                                    break;
                                                case 'Maintenance and Other Operating Expenses':
                                                    $badgeClass = 'badge-maintenance';
                                                    break;
                                                case 'Capital Outlay':
                                                    $badgeClass = 'badge-capital';
                                                    break;
                                                case 'Cash Advance':
                                                    $badgeClass = 'badge-cash';
                                                    break;
                                            }
                                            ?>
                                            <span class="expense-badge <?= $badgeClass ?>">
                                                <?php
                                                $shortType = $expense['expense_type'];
                                                if ($shortType === 'Maintenance and Other Operating Expenses') {
                                                    $shortType = 'Maintenance & Operating';
                                                }
                                                echo htmlspecialchars($shortType);
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($expense['office_name']): ?>
                                                <div class="office-info">
                                                    <div class="office-main">
                                                        <?= htmlspecialchars($expense['office_name']) ?>
                                                    </div>
                                                    <?php if ($expense['sub_office_name']): ?>
                                                        <div class="office-sub">
                                                            <?= htmlspecialchars($expense['sub_office_name']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="expense-secondary">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="amount-display">
                                                ₱<?= number_format($expense['total'], 2) ?>
                                                <?php if ($expense['tax'] > 0): ?>
                                                    <div class="tax-info">
                                                        Tax: ₱<?= number_format($expense['tax'], 2) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button class="btn-action btn-edit" onclick="editExpense(<?= $expense['id'] ?>)">
                                                    <i class='bx bx-edit'></i>
                                                    Edit
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-container">
                                <div class="pagination">
                                    <!-- Previous Page -->
                                    <?php if ($page > 1): ?>
                                        <a href="<?= buildPaginationUrl($page - 1) ?>">
                                            <i class='bx bx-chevron-left'></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="disabled">
                                            <i class='bx bx-chevron-left'></i>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Page Numbers -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <a href="<?= buildPaginationUrl(1) ?>">1</a>
                                        <?php if ($start_page > 2): ?>
                                            <span>...</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span class="current"><?= $i ?></span>
                                        <?php else: ?>
                                            <a href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <span>...</span>
                                        <?php endif; ?>
                                        <a href="<?= buildPaginationUrl($total_pages) ?>"><?= $total_pages ?></a>
                                    <?php endif; ?>
                                    
                                    <!-- Next Page -->
                                    <?php if ($page < $total_pages): ?>
                                        <a href="<?= buildPaginationUrl($page + 1) ?>">
                                            <i class='bx bx-chevron-right'></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="disabled">
                                            <i class='bx bx-chevron-right'></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-icon">
                                <i class='bx bx-receipt'></i>
                            </div>
                            <h3>No Expenses Found</h3>
                            <p>You haven't encoded any expenses yet or no expenses match your current search criteria.</p>
                            <a href="encode.php" class="btn btn-primary">
                                <i class='bx bx-plus'></i>
                                Create Your First Expense
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </section>

    <!-- Edit Expense Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class='bx bx-edit'></i>
                    Edit Expense
                </h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm" method="POST" action="update_expense.php">
                <div class="modal-body">
                    <input type="hidden" id="edit_expense_id" name="expense_id">
                    
                    <!-- Fund Type and Bank Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_fund_type">
                                <i class='bx bx-wallet'></i>
                                Fund Type
                            </label>
                            <select id="edit_fund_type" name="fund_type" class="form-control" required>
                                <option value="">Select Fund Type</option>
                                <option value="General Fund">General Fund</option>
                                <option value="Special Education Fund">Special Education Fund (SEF)</option>
                                <option value="Trust Fund">Trust Fund</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_bank">
                                <i class='bx bx-bank'></i>
                                Bank
                            </label>
                            <select id="edit_bank" name="bank" class="form-control" required>
                                <option value="">Select Bank</option>
                                <option value="LBP - Cavite City">LBP - Cavite City</option>
                                <option value="LBP - Trece Martires City">LBP - Trece Martires City</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date, Check Number, Payee Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_date">
                                <i class='bx bx-calendar'></i>
                                Date
                            </label>
                            <input type="date" id="edit_date" name="date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_check_number">
                                <i class='bx bx-receipt'></i>
                                Check Number
                            </label>
                            <input type="text" id="edit_check_number" name="check_number" class="form-control" 
                                   placeholder="Enter check number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_payee">
                                <i class='bx bx-user'></i>
                                Payee
                            </label>
                            <input type="text" id="edit_payee" name="payee" class="form-control" 
                                   placeholder="Enter payee name" required>
                        </div>
                    </div>

                    <!-- Expense Type Row -->
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="edit_expense_type">
                                <i class='bx bx-category'></i>
                                Expense Type
                            </label>
                            <select id="edit_expense_type" name="expense_type" class="form-control" required>
                                <option value="">Select Expense Type</option>
                                <option value="Personal Services">Personal Services</option>
                                <option value="Maintenance and Other Operating Expenses">Maintenance and Other Operating Expenses</option>
                                <option value="Capital Outlay">Capital Outlay</option>
                                <option value="Cash Advance">Cash Advance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Office and Sub-office Row -->
                    <div class="form-row" id="edit_office_group" style="display: none;">
                        <div class="form-group">
                            <label for="edit_office_id">
                                <i class='bx bx-building'></i>
                                Office
                            </label>
                            <select id="edit_office_id" name="office_id" class="form-control">
                                <option value="">Select Office</option>
                                <?php foreach ($main_offices as $office): ?>
                                <option value="<?php echo $office['id']; ?>">
                                    <?php echo htmlspecialchars($office['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="edit_sub_office_group" style="display: none;">
                            <label for="edit_sub_office_id">
                                <i class='bx bx-buildings'></i>
                                Sub Office
                            </label>
                            <select id="edit_sub_office_id" name="sub_office_id" class="form-control">
                                <option value="">Select Sub Office (Optional)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Amount, Tax, Total Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_amount">
                                <i class='bx bx-money'></i>
                                Amount
                            </label>
                            <input type="number" step="0.01" min="0" id="edit_amount" name="amount" 
                                   class="form-control" placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group" id="edit_tax_group">
                            <label for="edit_tax">
                                <i class='bx bx-calculator'></i>
                                Tax
                            </label>
                            <input type="number" step="0.01" min="0" id="edit_tax" name="tax" 
                                   class="form-control" placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_total">
                                <i class='bx bx-wallet'></i>
                                Total
                            </label>
                            <input type="number" step="0.01" id="edit_total" name="total" 
                                   class="form-control" readonly placeholder="0.00">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save'></i>
                        Update Expense
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                        <i class='bx bx-x'></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    
    <script>
        // Close edit modal function
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.style.display = 'none';
            
            // Reset form
            document.getElementById('editForm').reset();
            
            // Hide office fields
            document.getElementById('edit_office_group').style.display = 'none';
            document.getElementById('edit_sub_office_group').style.display = 'none';
            
            // Reset office field requirements
            document.getElementById('edit_office_id').required = false;
            
            // Reset tax field properties (in case it was disabled for Cash Advance)
            const taxField = document.getElementById('edit_tax');
            taxField.readOnly = false;
            taxField.style.backgroundColor = '';
            document.getElementById('edit_tax_group').style.opacity = '';
        }

        // Calculate total in edit modal
        function calculateEditTotal() {
            const amount = parseFloat(document.getElementById('edit_amount').value) || 0;
            const tax = parseFloat(document.getElementById('edit_tax').value) || 0;
            const total = amount + tax;
            document.getElementById('edit_total').value = total.toFixed(2);
        }

        // FIXED: Load sub-offices function with proper error handling and debugging
        function loadSubOfficesForEdit(officeId, selectedSubOfficeId = null) {
            console.log('=== LOADING SUB-OFFICES FOR EDIT ===');
            console.log('Office ID:', officeId);
            console.log('Selected Sub-office ID:', selectedSubOfficeId);
            
            const subOfficeSelect = document.getElementById('edit_sub_office_id');
            const subOfficeGroup = document.getElementById('edit_sub_office_group');
            
            // If no office ID, hide the sub-office group
            if (!officeId || officeId === '') {
                console.log('No office ID provided, hiding sub-office group');
                subOfficeGroup.style.display = 'none';
                subOfficeSelect.innerHTML = '<option value="">Select Sub Office (Optional)</option>';
                return;
            }
            
            // Show loading state
            console.log('Setting loading state...');
            subOfficeSelect.innerHTML = '<option value="">Loading sub-offices...</option>';
            subOfficeSelect.disabled = true;
            subOfficeGroup.style.display = 'block';
            
            // Construct URL - using correct path
            const url = `../api/get_sub_offices.php?office_id=${encodeURIComponent(officeId)}`;
            console.log('Fetching from URL:', url);
            
            // Make the request
            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response received:');
                console.log('- Status:', response.status);
                console.log('- Status Text:', response.statusText);
                console.log('- Content-Type:', response.headers.get('content-type'));
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                }
                
                return response.text(); // Get as text first to see what we're receiving
            })
            .then(textData => {
                console.log('Raw response data:', textData);
                
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(textData);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response: ' + textData);
                }
                
                console.log('Parsed data:', data);
                
                // Reset the select
                subOfficeSelect.innerHTML = '<option value="">Select Sub Office (Optional)</option>';
                subOfficeSelect.disabled = false;
                
                // Check if we have an error in the response
                if (data.error) {
                    console.error('API returned error:', data.error);
                    throw new Error(data.error);
                }
                
                // Handle the response data - should be an array
                let subOffices = [];
                if (Array.isArray(data)) {
                    subOffices = data;
                } else {
                    console.error('Expected array, got:', typeof data, data);
                    throw new Error('Expected array of sub-offices');
                }
                
                console.log(`Processing ${subOffices.length} sub-offices:`, subOffices);
                
                if (subOffices.length > 0) {
                    // Add each sub-office to the dropdown
                    subOffices.forEach((subOffice, index) => {
                        console.log(`Adding sub-office ${index + 1}:`, subOffice);
                        
                        if (!subOffice.id || !subOffice.name) {
                            console.warn('Invalid sub-office data:', subOffice);
                            return;
                        }
                        
                        const option = document.createElement('option');
                        option.value = subOffice.id;
                        option.textContent = subOffice.name;
                        
                        // Select the previously selected sub-office if specified
                        if (selectedSubOfficeId && subOffice.id == selectedSubOfficeId) {
                            option.selected = true;
                            console.log('✓ Selected sub-office:', subOffice.name);
                        }
                        
                        subOfficeSelect.appendChild(option);
                    });
                    
                    // Show the sub-office group
                    subOfficeGroup.style.display = 'block';
                    console.log('✓ Sub-office dropdown populated and shown');
                } else {
                    // No sub-offices found, hide the group
                    subOfficeGroup.style.display = 'none';
                    console.log('No sub-offices found, hiding group');
                }
            })
            .catch(error => {
                console.error('ERROR loading sub-offices:', error);
                
                // Reset the select and show error
                subOfficeSelect.innerHTML = '<option value="">Error loading sub-offices</option>';
                subOfficeSelect.disabled = false;
                
                // Show error temporarily, then hide
                setTimeout(() => {
                    subOfficeSelect.innerHTML = '<option value="">Select Sub Office (Optional)</option>';
                    subOfficeGroup.style.display = 'none';
                }, 3000);
                
                // Log full error details
                console.error('Full error details:', {
                    message: error.message,
                    stack: error.stack,
                    officeId: officeId,
                    url: url
                });
            });
        }

        // FIXED: Edit expense functionality with better initialization
        function editExpense(expenseId) {
            console.log('=== EDITING EXPENSE ===');
            console.log('Expense ID:', expenseId);
            
            // Show loading state
            document.getElementById('editModal').style.display = 'block';
            const modalBody = document.querySelector('#editModal .modal-body');
            const originalContent = modalBody.innerHTML;
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="bx bx-loader-alt bx-spin" style="font-size: 48px; color: var(--blue);"></i><p style="margin-top: 16px; color: var(--dark-grey);">Loading expense data...</p></div>';
            
            // Fetch expense data with correct path
            fetch(`../api/get_expense.php?id=${expenseId}`)
                .then(response => {
                    console.log('Get expense response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(expense => {
                    console.log('Loaded expense data:', expense);
                    
                    // Restore modal content
                    modalBody.innerHTML = originalContent;
                    
                    // Populate the form fields
                    document.getElementById('edit_expense_id').value = expense.id;
                    document.getElementById('edit_fund_type').value = expense.fund_type;
                    document.getElementById('edit_bank').value = expense.bank;
                    document.getElementById('edit_date').value = expense.date;
                    document.getElementById('edit_check_number').value = expense.check_number;
                    document.getElementById('edit_payee').value = expense.payee;
                    document.getElementById('edit_expense_type').value = expense.expense_type;
                    document.getElementById('edit_amount').value = expense.amount;
                    document.getElementById('edit_tax').value = expense.tax;
                    
                    // FIXED: Handle office fields setup with proper sequencing
                    const expenseType = expense.expense_type;
                    const showOfficeTypes = [
                        'Personal Services', 
                        'Maintenance and Other Operating Expenses', 
                        'Capital Outlay'
                    ];
                    
                    console.log('Setting up office fields for expense type:', expenseType);
                    
                    if (showOfficeTypes.includes(expenseType)) {
                        // Show office fields
                        document.getElementById('edit_office_group').style.display = 'grid';
                        document.getElementById('edit_office_id').required = true;
                        
                        // Set the office value first
                        if (expense.office_id) {
                            console.log('Setting office to:', expense.office_id);
                            document.getElementById('edit_office_id').value = expense.office_id;
                            
                            // CRITICAL: Load sub-offices with proper delay and selected value
                            setTimeout(() => {
                                console.log('Loading sub-offices with selected value:', expense.sub_office_id);
                                loadSubOfficesForEdit(expense.office_id, expense.sub_office_id);
                            }, 500); // Increased delay to ensure office is properly set
                        }
                    } else {
                        // Hide office fields for Cash Advance
                        document.getElementById('edit_office_group').style.display = 'none';
                        document.getElementById('edit_sub_office_group').style.display = 'none';
                        document.getElementById('edit_office_id').required = false;
                    }
                    
                    // Handle tax field for Cash Advance
                    if (expenseType === 'Cash Advance') {
                        document.getElementById('edit_tax').value = '0';
                        document.getElementById('edit_tax').readOnly = true;
                        document.getElementById('edit_tax').style.backgroundColor = '#f8f9fa';
                        document.getElementById('edit_tax_group').style.opacity = '0.7';
                    } else {
                        document.getElementById('edit_tax').readOnly = false;
                        document.getElementById('edit_tax').style.backgroundColor = '';
                        document.getElementById('edit_tax_group').style.opacity = '1';
                    }
                    
                    // Calculate total
                    calculateEditTotal();
                    
                    console.log('✓ Form populated successfully');
                })
                .catch(error => {
                    console.error('Error fetching expense:', error);
                    modalBody.innerHTML = originalContent;
                    alert('Failed to load expense data: ' + error.message);
                    closeEditModal();
                });
        }

        // Event listeners for edit modal
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== INITIALIZING EDIT MODAL EVENT LISTENERS ===');
            
            // Close modal when clicking outside of it
            window.onclick = function(event) {
                const modal = document.getElementById('editModal');
                if (event.target === modal) {
                    closeEditModal();
                }
            };
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const modal = document.getElementById('editModal');
                    if (modal.style.display === 'block') {
                        closeEditModal();
                    }
                }
            });
            
            // Handle expense type change in edit modal
            document.getElementById('edit_expense_type').addEventListener('change', function() {
                console.log('=== EXPENSE TYPE CHANGED IN EDIT ===');
                const expenseType = this.value;
                console.log('New expense type:', expenseType);
                
                const officeGroup = document.getElementById('edit_office_group');
                const officeSelect = document.getElementById('edit_office_id');
                const subOfficeGroup = document.getElementById('edit_sub_office_group');
                const subOfficeSelect = document.getElementById('edit_sub_office_id');
                const taxField = document.getElementById('edit_tax');
                const taxGroup = document.getElementById('edit_tax_group');
                
                // Show/hide office fields based on expense type
                const showOfficeTypes = ['Personal Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay'];
                
                if (showOfficeTypes.includes(expenseType)) {
                    console.log('Showing office fields');
                    officeGroup.style.display = 'grid';
                    officeSelect.required = true;
                } else {
                    console.log('Hiding office fields');
                    officeGroup.style.display = 'none';
                    officeSelect.required = false;
                    officeSelect.value = '';
                    // Also hide and clear sub-office
                    subOfficeGroup.style.display = 'none';
                    subOfficeSelect.value = '';
                }
                
                // Handle tax field for Cash Advance
                if (expenseType === 'Cash Advance') {
                    console.log('Setting up Cash Advance (no tax)');
                    taxField.value = '0';
                    taxField.readOnly = true;
                    taxField.style.backgroundColor = '#f8f9fa';
                    taxGroup.style.opacity = '0.7';
                } else {
                    console.log('Setting up regular expense (with tax)');
                    taxField.readOnly = false;
                    taxField.style.backgroundColor = '';
                    taxGroup.style.opacity = '1';
                }
                
                calculateEditTotal();
            });
            
            // CRITICAL: Handle office change in edit modal
            document.getElementById('edit_office_id').addEventListener('change', function() {
                console.log('=== OFFICE SELECTION CHANGED IN EDIT ===');
                const officeId = this.value;
                console.log('New office ID:', officeId);
                
                const subOfficeGroup = document.getElementById('edit_sub_office_group');
                const subOfficeSelect = document.getElementById('edit_sub_office_id');
                
                // Always clear the current sub-office selection first
                console.log('Clearing current sub-office selection');
                subOfficeSelect.value = '';
                subOfficeSelect.innerHTML = '<option value="">Select Sub Office (Optional)</option>';
                
                if (officeId && officeId !== '') {
                    console.log('Loading sub-offices for office:', officeId);
                    // Load fresh sub-offices for the newly selected office
                    // Don't pass selectedSubOfficeId here - we want a fresh start for manual changes
                    loadSubOfficesForEdit(officeId, null);
                } else {
                    console.log('No office selected, hiding sub-office group');
                    // Hide sub-office group if no office selected
                    subOfficeGroup.style.display = 'none';
                }
            });
            
            // Handle amount and tax changes in edit modal
            document.getElementById('edit_amount').addEventListener('input', calculateEditTotal);
            document.getElementById('edit_tax').addEventListener('input', calculateEditTotal);
            
            // Handle form submission
            document.getElementById('editForm').addEventListener('submit', function(e) {
                console.log('=== FORM SUBMISSION ===');
                
                // Basic validation
                const amount = parseFloat(document.getElementById('edit_amount').value);
                const tax = parseFloat(document.getElementById('edit_tax').value);
                
                console.log('Validating - Amount:', amount, 'Tax:', tax);
                
                if (amount <= 0) {
                    e.preventDefault();
                    alert('Amount must be greater than zero');
                    return;
                }
                
                if (tax < 0) {
                    e.preventDefault();
                    alert('Tax cannot be negative');
                    return;
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Updating...';
                submitBtn.disabled = true;
                
                console.log('Form submitted, showing loading state');
                
                // Re-enable button after a delay in case of errors
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            });
            
            console.log('✓ All event listeners initialized');
        });

        // Helper functions for pagination and sorting
        function buildPaginationUrl(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            return '?' + params.toString();
        }

        function sortTable(column) {
            const params = new URLSearchParams(window.location.search);
            const currentSort = params.get('sort_by');
            const currentOrder = params.get('sort_order');
            
            let newOrder = 'ASC';
            if (currentSort === column && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }
            
            params.set('sort_by', column);
            params.set('sort_order', newOrder);
            params.set('page', '1'); // Reset to first page when sorting
            
            window.location.href = '?' + params.toString();
        }

        // Add smooth scrolling and enhanced interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to filter form
            const filterForm = document.getElementById('filterForm');
            if (filterForm) {
                filterForm.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Applying...';
                    submitBtn.disabled = true;
                });
            }

            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.expense-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(4px)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Add click effect to buttons
            const buttons = document.querySelectorAll('.btn, .btn-action');
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

            // Add CSS for ripple animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>