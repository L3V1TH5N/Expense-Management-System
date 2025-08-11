<?php include 'functions/expenses.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>All Expenses - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/expenses.css">
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
    <script src="functions/js/expenses.js"></script>
</body>
</html>
