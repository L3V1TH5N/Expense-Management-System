<?php include 'functions/encode.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Encode Expense - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/encode.css">
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
            <li class="active">
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
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Encode Expense</a>
                        </li>
                    </ul>
                </div>
                <div class="right">
                    <a href="dashboard.php" class="btn-download">
                        <i class='bx bx-arrow-back'></i>
                        <span class="text">Back to Dashboard</span>
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
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="form-card">
                <div class="form-header">
                    <i class='bx bx-plus-circle'></i>
                    <span class="form-title">Encode New Expense</span>
                </div>
                
                <form method="POST" class="expense-form" id="expenseForm">
                    <!-- Fund Type and Bank Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fund_type">
                                <i class='bx bx-wallet'></i>
                                Fund Type
                            </label>
                            <select id="fund_type" name="fund_type" class="form-control" required>
                                <option value="">Select Fund Type</option>
                                <option value="General Fund" <?php echo ($_POST['fund_type'] ?? '') === 'General Fund' ? 'selected' : ''; ?>>General Fund</option>
                                <option value="Special Education Fund" <?php echo ($_POST['fund_type'] ?? '') === 'Special Education Fund' ? 'selected' : ''; ?>>Special Education Fund (SEF)</option>
                                <option value="Trust Fund" <?php echo ($_POST['fund_type'] ?? '') === 'Trust Fund' ? 'selected' : ''; ?>>Trust Fund</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="bank">
                                <i class='bx bx-bank'></i>
                                Bank
                            </label>
                            <select id="bank" name="bank" class="form-control" required>
                                <option value="">Select Bank</option>
                                <option value="LBP - Cavite City" <?php echo ($_POST['bank'] ?? '') === 'LBP - Cavite City' ? 'selected' : ''; ?>>LBP - Cavite City</option>
                                <option value="LBP - Trece Martires City" <?php echo ($_POST['bank'] ?? '') === 'LBP - Trece Martires City' ? 'selected' : ''; ?>>LBP - Trece Martires City</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date, Check Number, Payee Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">
                                <i class='bx bx-calendar'></i>
                                Date
                            </label>
                            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="check_number">
                                <i class='bx bx-receipt'></i>
                                Check Number
                            </label>
                            <input type="text" id="check_number" name="check_number" class="form-control" value="<?php echo htmlspecialchars($_POST['check_number'] ?? ''); ?>" placeholder="Enter check number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="payee">
                                <i class='bx bx-user'></i>
                                Payee
                            </label>
                            <input type="text" id="payee" name="payee" class="form-control" value="<?php echo htmlspecialchars($_POST['payee'] ?? ''); ?>" placeholder="Enter payee name" required>
                        </div>
                    </div>

                    <!-- Expense Type Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expense_type">
                                <i class='bx bx-category'></i>
                                Expense Type
                            </label>
                            <select id="expense_type" name="expense_type" class="form-control" required>
                                <option value="">Select Expense Type</option>
                                <option value="Personnel Services" <?php echo ($_POST['expense_type'] ?? '') === 'Personnel Services' ? 'selected' : ''; ?>>Personnel Services</option>
                                <option value="Maintenance and Other Operating Expenses" <?php echo ($_POST['expense_type'] ?? '') === 'Maintenance and Other Operating Expenses' ? 'selected' : ''; ?>>Maintenance and Other Operating Expenses</option>
                                <option value="Capital Outlay" <?php echo ($_POST['expense_type'] ?? '') === 'Capital Outlay' ? 'selected' : ''; ?>>Capital Outlay</option>
                                <option value="Cash Advance" <?php echo ($_POST['expense_type'] ?? '') === 'Cash Advance' ? 'selected' : ''; ?>>Cash Advance</option>
                                <option value="Others" <?php echo ($_POST['expense_type'] ?? '') === 'Others' ? 'selected' : ''; ?>>Others</option>
                            </select>
                        </div>
                    </div>

                    <!-- Office and Sub-office Row -->
                    <div class="form-row" id="office_group">
                        <div class="form-group">
                            <label for="office_id">
                                <i class='bx bx-building'></i>
                                Office
                            </label>
                            <select id="office_id" name="office_id" class="form-control">
                                <option value="">Select Office</option>
                                <?php foreach ($main_offices as $office): ?>
                                <option value="<?php echo $office['id']; ?>" <?php echo ($_POST['office_id'] ?? '') == $office['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($office['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="sub_office_group" style="display: none;">
                            <label for="sub_office_id">
                                <i class='bx bx-buildings'></i>
                                Sub Office
                            </label>
                            <select id="sub_office_id" name="sub_office_id" class="form-control">
                                <option value="">Select Sub Office (Optional)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Amount, Tax, Total Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">
                                <i class='bx bx-money'></i>
                                Amount
                            </label>
                            <input type="number" step="0.01" min="0" id="amount" name="amount" class="form-control" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group" id="tax_group">
                            <label for="tax">
                                <i class='bx bx-calculator'></i>
                                Tax
                            </label>
                            <input type="number" step="0.01" min="0" id="tax" name="tax" class="form-control" value="<?php echo htmlspecialchars($_POST['tax'] ?? '0'); ?>" placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="total">
                                <i class='bx bx-wallet'></i>
                                Total
                            </label>
                            <input type="number" step="0.01" id="total" name="total" class="form-control" readonly placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class='bx bx-save'></i>
                            Encode Expense
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class='bx bx-x'></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </section>

    <script src="../assets/js/script.js"></script>
    
    <script src="functions/js/encode.js"></script>
</body>
</html>
