<?php include 'functions/expense.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>My Expenses - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/expenses.css">
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
            <li class="active">
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
                                <option value="Personnel Services" <?php echo $expense_type_filter === 'Personnel Services' ? 'selected' : ''; ?>>Personnel Services</option>
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
                                                case 'Personnel Services':
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
                                                <button class="btn-action btn-delete" onclick="confirmDelete(<?= $expense['id'] ?>, '<?= addslashes(htmlspecialchars($expense['payee'])) ?>', '<?= htmlspecialchars($expense['check_number']) ?>', <?= $expense['total'] ?>)">
                                                    <i class='bx bx-trash'></i>
                                                    Delete
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
                                <option value="Personnel Services">Personnel Services</option>
                                <option value="Maintenance and Other Operating Expenses">Maintenance and Other Operating Expenses</option>
                                <option value="Capital Outlay">Capital Outlay</option>
                                <option value="Cash Advance">Cash Advance</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>

                    <!-- Office and Sub-office Row -->
                    <div class="form-row" id="edit_office_group">
                        <div class="form-group">
                            <label for="edit_office_id">
                                <i class='bx bx-building'></i>
                                Office <span style="color: red;">*</span>
                            </label>
                            <select id="edit_office_id" name="office_id" class="form-control" required>
                                <option value="">Select Office</option>
                                <!-- Office options will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div class="form-group" id="edit_sub_office_group" style="display: none;">
                            <label for="edit_sub_office_id">
                                <i class='bx bx-buildings'></i>
                                Sub Office
                            </label>
                            <select id="edit_sub_office_id" name="sub_office_id" class="form-control">
                                <option value="">Select Sub Office (Optional)</option>
                                <!-- Sub-office options will be populated by JavaScript -->
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content delete-modal-content">
            <div class="modal-header delete-header">
                <h3>
                    <i class='bx bx-trash'></i>
                    Delete Expense
                </h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            
            <div class="modal-body">
                <div class="delete-info">
                    <h4>
                        <i class='bx bx-info-circle'></i>
                        Expense Details
                    </h4>
                    <div class="delete-details">
                        <div class="delete-label">Payee:</div>
                        <div class="delete-value" id="delete-payee"></div>
                        
                        <div class="delete-label">Check Number:</div>
                        <div class="delete-value" id="delete-check-number"></div>
                        
                        <div class="delete-label">Amount:</div>
                        <div class="delete-value" id="delete-amount"></div>
                    </div>
                    
                    <div class="warning-text">
                        <i class='bx bx-error-circle'></i>
                        <strong>Warning:</strong> This action cannot be undone. The expense record will be permanently deleted from the system.
                    </div>
                </div>
                
                <p style="text-align: center; font-weight: 500; color: var(--dark); margin-bottom: 0;">
                    Are you sure you want to delete this expense record?
                </p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmDeleteBtn">
                    <i class='bx bx-check'></i>
                    Yes, Delete
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class='bx bx-x'></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        const officesData = <?php echo json_encode($main_offices); ?>;
        console.log('Available offices loaded:', officesData);
    </script>

    <script src="../assets/js/script.js"></script>
    <script src="functions/js/expense.js"></script>
</body>
</html>
