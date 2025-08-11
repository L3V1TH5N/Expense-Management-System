<?php include 'functions/edit.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Edit Expense - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/edit.css">
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
                            <a href="all_expenses.php">All Expenses</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a href="view_expenses.php">View Expenses</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Edit Expense</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <i class='bx bx-error-circle'></i>
                    <div>
                        <strong>Error!</strong> Please fix the following issues:
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Expense Form Container -->
            <div class="expense-form-container">
                <div class="form-header">
                    <h2>
                        <i class='bx bx-edit'></i>
                        Edit Expense
                    </h2>
                    <span class="expense-id">ID: <?php echo $expense['id']; ?></span>
                </div>

                <form method="POST" id="expenseForm">
                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h3><i class='bx bx-info-circle'></i> Basic Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="date">Date <span class="text-danger">*</span></label>
                                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($expense['date']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="payee">Payee <span class="text-danger">*</span></label>
                                <input type="text" id="payee" name="payee" value="<?php echo htmlspecialchars($expense['payee']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="check_number">Check Number <span class="text-danger">*</span></label>
                                <input type="text" id="check_number" name="check_number" value="<?php echo htmlspecialchars($expense['check_number']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="bank">Bank <span class="text-danger">*</span></label>
                                <input type="text" id="bank" name="bank" value="<?php echo htmlspecialchars($expense['bank']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information Section -->
                    <div class="form-section">
                        <h3><i class='bx bx-money'></i> Financial Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="fund_type">Fund Type <span class="text-danger">*</span></label>
                                <select id="fund_type" name="fund_type" required>
                                    <option value="General Fund" <?php echo $expense['fund_type'] === 'General Fund' ? 'selected' : ''; ?>>General Fund</option>
                                    <option value="Special Education Fund" <?php echo $expense['fund_type'] === 'Special Education Fund' ? 'selected' : ''; ?>>Special Education Fund</option>
                                    <option value="Trust Fund" <?php echo $expense['fund_type'] === 'Trust Fund' ? 'selected' : ''; ?>>Trust Fund</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="expense_type">Expense Type <span class="text-danger">*</span></label>
                                <select id="expense_type" name="expense_type" required>
                                    <option value="Personnel Services" <?php echo $expense['expense_type'] === 'Personnel Services' ? 'selected' : ''; ?>>Personnel Services</option>
                                    <option value="Maintenance and Other Operating Expenses" <?php echo $expense['expense_type'] === 'Maintenance and Other Operating Expenses' ? 'selected' : ''; ?>>Maintenance and Other Operating Expenses</option>
                                    <option value="Capital Outlay" <?php echo $expense['expense_type'] === 'Capital Outlay' ? 'selected' : ''; ?>>Capital Outlay</option>
                                    <option value="Cash Advance" <?php echo $expense['expense_type'] === 'Cash Advance' ? 'selected' : ''; ?>>Cash Advance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0" value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="tax">Tax</label>
                                <input type="number" id="tax" name="tax" step="0.01" min="0" value="<?php echo htmlspecialchars($expense['tax']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Office Information Section -->
                    <div class="form-section">
                        <h3><i class='bx bx-building'></i> Office Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="office_id">Office</label>
                                <select id="office_id" name="office_id">
                                    <option value="">-- Select Office --</option>
                                    <?php foreach ($main_offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo $expense['office_id'] == $office['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="sub_office_id">Sub Office</label>
                                <select id="sub_office_id" name="sub_office_id" <?php echo empty($sub_offices) ? 'disabled' : ''; ?>>
                                    <option value="">-- Select Sub Office --</option>
                                    <?php foreach ($sub_offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo $expense['sub_office_id'] == $office['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save'></i>
                            Save Changes
                        </button>
                        <a href="view_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-secondary">
                            <i class='bx bx-x'></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script src="functions/js/edit.js"></script>
</body>
</html>
