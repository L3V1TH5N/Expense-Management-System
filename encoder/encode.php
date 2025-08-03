<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$success = '';
$error = '';

// Get all offices with their relationships
$stmt = $conn->prepare("SELECT id, name, parent_id FROM offices ORDER BY parent_id, name");
$stmt->execute();
$all_offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group offices by parent_id
$offices_by_parent = [];
foreach ($all_offices as $office) {
    $parent_id = $office['parent_id'] ?? 'main';
    if (!isset($offices_by_parent[$parent_id])) {
        $offices_by_parent[$parent_id] = [];
    }
    $offices_by_parent[$parent_id][] = $office;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Clean and validate input data
        $data = [];
        $data['fund_type'] = sanitizeInput($_POST['fund_type'] ?? '');
        $data['bank'] = sanitizeInput($_POST['bank'] ?? '');
        $data['date'] = sanitizeInput($_POST['date'] ?? '');
        $data['check_number'] = sanitizeInput($_POST['check_number'] ?? '');
        $data['payee'] = sanitizeInput($_POST['payee'] ?? '');
        $data['expense_type'] = sanitizeInput($_POST['expense_type'] ?? '');
        $data['amount'] = cleanNumericInput($_POST['amount'] ?? '');
        $data['tax'] = cleanNumericInput($_POST['tax'] ?? '0');

        // Validate the expense data
        $validation_errors = validateExpenseData($data);
        if (!empty($validation_errors)) {
            throw new Exception(implode("<br>", $validation_errors));
        }

        // For all expense types, allow office selection but only require it for specific types
        $data['office_id'] = !empty($_POST['office_id']) ? (int)$_POST['office_id'] : null;
        $data['sub_office_id'] = !empty($_POST['sub_office_id']) ? (int)$_POST['sub_office_id'] : null;

        // Validate office hierarchy if sub-office is selected
        if ($data['sub_office_id'] && !validateOfficeHierarchy($conn, $data['office_id'], $data['sub_office_id'])) {
            throw new Exception("Selected sub-office doesn't belong to the selected office");
        }

        // Only validate required office for specific expense types
        $showOfficeTypes = ['Personal Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay'];
        if (in_array($data['expense_type'], $showOfficeTypes) && empty($data['office_id'])) {
            throw new Exception("Office selection is required for this expense type");
        }

        // Convert amounts to float
        $amount = (float)$data['amount'];
        $tax = (float)$data['tax'];

        // Calculate total based on expense type
        if ($data['expense_type'] === 'Cash Advance') {
            $tax = 0; // Cash advance doesn't have tax
        }
        $total = $amount + $tax;

        // Validate final amounts
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero");
        }

        if ($tax < 0) {
            throw new Exception("Tax cannot be negative");
        }

        // Begin database transaction
        $conn->beginTransaction();

        try {
            // Insert the expense record
            $stmt = $conn->prepare("
                INSERT INTO expenses (
                    fund_type, bank, date, check_number, payee,
                    office_id, sub_office_id, expense_type,
                    amount, tax, total, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $data['fund_type'],
                $data['bank'],
                $data['date'],
                $data['check_number'],
                $data['payee'],
                $data['office_id'],
                $data['sub_office_id'],
                $data['expense_type'],
                $amount,
                $tax,
                $total,
                $_SESSION['user_id']
            ]);

            if (!$result) {
                throw new Exception("Failed to insert expense record");
            }

            $expense_id = $conn->lastInsertId();

            // Log the activity using the global $pdo connection
            $activity_data = [
                'fund_type' => $data['fund_type'],
                'bank' => $data['bank'],
                'date' => $data['date'],
                'check_number' => $data['check_number'],
                'payee' => $data['payee'],
                'office_id' => $data['office_id'],
                'sub_office_id' => $data['sub_office_id'],
                'expense_type' => $data['expense_type'],
                'amount' => $amount,
                'tax' => $tax,
                'total' => $total
            ];

            logExpenseActivity($pdo, 'CREATE', $expense_id, $activity_data);

            // Commit the transaction
            $conn->commit();
            
            $success = 'Expense encoded successfully! (ID: ' . $expense_id . ')';
            
            // Clear form data on success
            $_POST = [];

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in expense creation: " . $e->getMessage());
            throw new Exception("Database error occurred while saving the expense");
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error encoding expense: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Get main offices (parent_id IS NULL)
$stmt = $conn->prepare("SELECT id, name FROM offices WHERE parent_id IS NULL ORDER BY name");
$stmt->execute();
$main_offices = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Encode Expense - Expense Management System</title>
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

        .form-card {
            background: var(--light);
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .form-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
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

        .form-control.error {
            border-color: #dc3545;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--grey);
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

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .form-actions {
                flex-direction: column;
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
            <li class="active">
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
                                <option value="Personal Services" <?php echo ($_POST['expense_type'] ?? '') === 'Personal Services' ? 'selected' : ''; ?>>Personal Services</option>
                                <option value="Maintenance and Other Operating Expenses" <?php echo ($_POST['expense_type'] ?? '') === 'Maintenance and Other Operating Expenses' ? 'selected' : ''; ?>>Maintenance and Other Operating Expenses</option>
                                <option value="Capital Outlay" <?php echo ($_POST['expense_type'] ?? '') === 'Capital Outlay' ? 'selected' : ''; ?>>Capital Outlay</option>
                                <option value="Cash Advance" <?php echo ($_POST['expense_type'] ?? '') === 'Cash Advance' ? 'selected' : ''; ?>>Cash Advance</option>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('expenseForm');
            const expenseTypeSelect = document.getElementById('expense_type');
            const officeGroup = document.getElementById('office_group');
            const officeSelect = document.getElementById('office_id');
            const subOfficeGroup = document.getElementById('sub_office_group');
            const subOfficeSelect = document.getElementById('sub_office_id');
            const taxGroup = document.getElementById('tax_group');
            const amountInput = document.getElementById('amount');
            const taxInput = document.getElementById('tax');
            const totalInput = document.getElementById('total');
            const submitBtn = document.getElementById('submitBtn');

            // Form submission handler
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let hasErrors = false;

                // Clear previous error states
                document.querySelectorAll('.form-control.error').forEach(field => {
                    field.classList.remove('error');
                });

                // Validate required fields
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('error');
                        hasErrors = true;
                    }
                });

                // Validate amount
                const amount = parseFloat(amountInput.value);
                if (isNaN(amount) || amount <= 0) {
                    amountInput.classList.add('error');
                    hasErrors = true;
                }

                // Validate tax for non-cash advance
                if (expenseTypeSelect.value !== 'Cash Advance') {
                    const tax = parseFloat(taxInput.value);
                    if (isNaN(tax) || tax < 0) {
                        taxInput.classList.add('error');
                        hasErrors = true;
                    }
                }

                // Validate office for specific expense types
                const requiredOfficeTypes = ['Personal Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay'];
                if (requiredOfficeTypes.includes(expenseTypeSelect.value) && !officeSelect.value) {
                    officeSelect.classList.add('error');
                    hasErrors = true;
                }

                if (hasErrors) {
                    e.preventDefault();
                    alert('Please fill in all required fields correctly.');
                    return false;
                }

                // Show loading state
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
                submitBtn.disabled = true;
                form.classList.add('loading');
            });

            // Expense Type Change Handler
            expenseTypeSelect.addEventListener('change', function() {
                const requiredOfficeTypes = [
                    'Personal Services', 
                    'Maintenance and Other Operating Expenses', 
                    'Capital Outlay'
                ];
                
                // Set office requirement based on expense type
                officeSelect.required = requiredOfficeTypes.includes(this.value);
                
                // Update the visual indicator for required fields
                if (officeSelect.required) {
                    officeSelect.classList.add('required-field');
                } else {
                    officeSelect.classList.remove('required-field');
                }

                // Handle tax field for Cash Advance
                if (this.value === 'Cash Advance') {
                    taxInput.value = '0';
                    taxInput.readOnly = true;
                    taxInput.style.backgroundColor = '#f8f9fa';
                    taxGroup.style.opacity = '0.7';
                } else {
                    taxInput.readOnly = false;
                    taxInput.style.backgroundColor = '';
                    taxGroup.style.opacity = '1';
                }
                
                calculateTotal();
            });

            // Office Change Handler (Load Sub-offices)
            officeSelect.addEventListener('change', function() {
                const officeId = this.value;
                subOfficeSelect.innerHTML = '<option value="">Loading...</option>';
                
                if (officeId) {
                    // Show sub-office group
                    subOfficeGroup.style.display = 'block';
                    
                    fetch('../api/get_sub_offices.php?office_id=' + encodeURIComponent(officeId))
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(subOffices => {
                            subOfficeSelect.innerHTML = '<option value="">Select Sub Office (Optional)</option>';
                            
                            if (Array.isArray(subOffices) && subOffices.length > 0) {
                                subOffices.forEach(subOffice => {
                                    const option = document.createElement('option');
                                    option.value = subOffice.id;
                                    option.textContent = subOffice.name;
                                    subOfficeSelect.appendChild(option);
                                });
                                
                                // Restore selected sub-office if exists
                                const postSubOfficeId = "<?php echo $_POST['sub_office_id'] ?? ''; ?>";
                                if (postSubOfficeId) {
                                    subOfficeSelect.value = postSubOfficeId;
                                }
                            } else {
                                subOfficeGroup.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching sub-offices:', error);
                            subOfficeSelect.innerHTML = '<option value="">Error loading sub-offices</option>';
                            setTimeout(() => {
                                subOfficeGroup.style.display = 'none';
                            }, 2000);
                        });
                } else {
                    subOfficeSelect.innerHTML = '<option value="">Select Sub Office (Optional)</option>';
                    subOfficeGroup.style.display = 'none';
                }
            });

            // Calculate total function
            function calculateTotal() {
                const amount = parseFloat(amountInput.value) || 0;
                let tax = 0;
                
                if (expenseTypeSelect.value !== 'Cash Advance') {
                    tax = parseFloat(taxInput.value) || 0;
                }
                
                const total = amount + tax;
                totalInput.value = total.toFixed(2);
            }

            // Event listeners for calculation
            amountInput.addEventListener('input', calculateTotal);
            taxInput.addEventListener('input', calculateTotal);

            // Format currency inputs on blur
            function formatCurrencyInput(input) {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    input.value = value.toFixed(2);
                }
            }

            amountInput.addEventListener('blur', () => formatCurrencyInput(amountInput));
            taxInput.addEventListener('blur', () => formatCurrencyInput(taxInput));

            // Remove error class on input
            document.querySelectorAll('.form-control').forEach(field => {
                field.addEventListener('input', function() {
                    this.classList.remove('error');
                });
            });

            // Initialize form state on page load
            function initializeForm() {
                const requiredOfficeTypes = [
                    'Personal Services', 
                    'Maintenance and Other Operating Expenses', 
                    'Capital Outlay'
                ];
                
                // Set office requirement based on current expense type
                officeSelect.required = requiredOfficeTypes.includes(expenseTypeSelect.value);
                
                // Update the visual indicator for required fields
                if (officeSelect.required) {
                    officeSelect.classList.add('required-field');
                } else {
                    officeSelect.classList.remove('required-field');
                }
                
                // Load sub-offices if office is selected
                if (officeSelect.value) {
                    officeSelect.dispatchEvent(new Event('change'));
                }

                // Handle cash advance tax field
                if (expenseTypeSelect.value === 'Cash Advance') {
                    taxInput.value = '0';
                    taxInput.readOnly = true;
                    taxInput.style.backgroundColor = '#f8f9fa';
                    taxGroup.style.opacity = '0.7';
                }

                // Calculate initial total
                calculateTotal();
            }

            // Initialize the form
            initializeForm();

            // Auto-save functionality (optional)
            let autoSaveTimeout;
            function autoSave() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    const formData = new FormData(form);
                    localStorage.setItem('expense_form_backup', JSON.stringify(Object.fromEntries(formData)));
                }, 1000);
            }

            // Add auto-save listeners to all form fields
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('input', autoSave);
                field.addEventListener('change', autoSave);
            });

            // Restore form data on page load if available
            const savedData = localStorage.getItem('expense_form_backup');
            if (savedData && !form.querySelector('.alert-success')) {
                try {
                    const data = JSON.parse(savedData);
                    Object.keys(data).forEach(key => {
                        const field = form.querySelector(`[name="${key}"]`);
                        if (field && !field.value) {
                            field.value = data[key];
                        }
                    });
                    initializeForm(); // Re-initialize after restoring data
                } catch (e) {
                    console.log('Could not restore form data');
                }
            }

            // Clear backup on successful submission
            if (form.querySelector('.alert-success')) {
                localStorage.removeItem('expense_form_backup');
            }
        });
    </script>
</body>
</html>