<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Check if expense ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: all_expenses.php');
    exit();
}

$expense_id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $errors = validateExpenseData($_POST);
    
    if (empty($errors)) {
        try {
            // Get current expense data for logging
            $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
            $old_expense = $stmt->fetch();
            
            // Prepare update data
            $update_data = [
                'fund_type' => sanitizeInput($_POST['fund_type']),
                'bank' => sanitizeInput($_POST['bank']),
                'date' => sanitizeInput($_POST['date']),
                'check_number' => sanitizeInput($_POST['check_number']),
                'payee' => sanitizeInput($_POST['payee']),
                'office_id' => !empty($_POST['office_id']) ? (int)$_POST['office_id'] : null,
                'sub_office_id' => !empty($_POST['sub_office_id']) ? (int)$_POST['sub_office_id'] : null,
                'expense_type' => sanitizeInput($_POST['expense_type']),
                'amount' => (float)cleanNumericInput($_POST['amount']),
                'tax' => !empty($_POST['tax']) ? (float)cleanNumericInput($_POST['tax']) : 0,
                'total' => calculateTotal($_POST['amount'], $_POST['tax'] ?? 0),
                'id' => $expense_id
            ];
            
            // Validate office hierarchy
            if ($update_data['sub_office_id'] && !validateOfficeHierarchy($conn, $update_data['office_id'], $update_data['sub_office_id'])) {
                $errors[] = "Selected sub-office does not belong to the selected office";
            }
            
            if (empty($errors)) {
                // Update expense in database
                $stmt = $conn->prepare("
                    UPDATE expenses SET 
                        fund_type = :fund_type,
                        bank = :bank,
                        date = :date,
                        check_number = :check_number,
                        payee = :payee,
                        office_id = :office_id,
                        sub_office_id = :sub_office_id,
                        expense_type = :expense_type,
                        amount = :amount,
                        tax = :tax,
                        total = :total,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                
                $stmt->execute($update_data);
                
                // Log the activity
                logExpenseActivity($pdo, 'UPDATE', $expense_id, $update_data, $old_expense);
                
                // Redirect to view page with success message
                $_SESSION['success_message'] = "Expense updated successfully!";
                header("Location: view_expense.php?id=$expense_id");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch expense details for form
$query = "
    SELECT 
        e.*, 
        o.name as office_name, 
        so.name as sub_office_name
    FROM expenses e 
    LEFT JOIN offices o ON e.office_id = o.id 
    LEFT JOIN offices so ON e.sub_office_id = so.id 
    WHERE e.id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$expense_id]);
$expense = $stmt->fetch();

if (!$expense) {
    header('Location: all_expenses.php');
    exit();
}

// Get all offices for dropdowns
$main_offices = getOffices($conn);
$sub_offices = $expense['office_id'] ? getSubOffices($conn, $expense['office_id']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Edit Expense - Expense Management System</title>
    <style>
        /* Reuse styles from view_expense.php */
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

        .expense-form-container {
            background: var(--light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid var(--grey);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out forwards;
        }

        .expense-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .form-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-header h2 i {
            color: var(--blue);
            font-size: 28px;
        }

        .expense-id {
            background: var(--blue);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .form-section {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--grey);
        }

        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .form-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section h3 i {
            color: var(--blue);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: var(--dark-grey);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--grey);
            border-radius: 8px;
            font-size: 14px;
            transition: all var(--animation-speed) ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(60, 145, 230, 0.1);
        }

        .form-group .error-message {
            color: var(--red);
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }

        .form-group.has-error .error-message {
            display: block;
        }

        .form-group.has-error input,
        .form-group.has-error select,
        .form-group.has-error textarea {
            border-color: var(--red);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
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

        .btn-danger {
            background: var(--gradient-danger);
            color: white;
            box-shadow: var(--shadow-light);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* Error alert */
        .alert-error {
            background: var(--gradient-danger);
            color: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error i {
            font-size: 20px;
        }

        /* Success alert */
        .alert-success {
            background: var(--gradient-success);
            color: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success i {
            font-size: 20px;
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

        @media screen and (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
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
                            <a href="all_expenses.php">All Expenses</a>
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
                                    <option value="Personal Services" <?php echo $expense['expense_type'] === 'Personal Services' ? 'selected' : ''; ?>>Personal Services</option>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Office hierarchy handling
            const officeSelect = document.getElementById('office_id');
            const subOfficeSelect = document.getElementById('sub_office_id');
            
            officeSelect.addEventListener('change', function() {
                const officeId = this.value;
                
                if (officeId) {
                    // Fetch sub-offices via AJAX
                    fetch(`../api/get_sub_offices.php?parent_id=${officeId}`)
                        .then(response => response.json())
                        .then(data => {
                            subOfficeSelect.innerHTML = '<option value="">-- Select Sub Office --</option>';
                            
                            if (data.length > 0) {
                                subOfficeSelect.disabled = false;
                                data.forEach(office => {
                                    const option = document.createElement('option');
                                    option.value = office.id;
                                    option.textContent = office.name;
                                    subOfficeSelect.appendChild(option);
                                });
                            } else {
                                subOfficeSelect.disabled = true;
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching sub-offices:', error);
                        });
                } else {
                    subOfficeSelect.innerHTML = '<option value="">-- Select Sub Office --</option>';
                    subOfficeSelect.disabled = true;
                }
            });

            // Calculate total when amount or tax changes
            const amountInput = document.getElementById('amount');
            const taxInput = document.getElementById('tax');
            
            function calculateTotal() {
                // This is just for client-side display if needed
                // Server will calculate the final total
            }
            
            amountInput.addEventListener('input', calculateTotal);
            taxInput.addEventListener('input', calculateTotal);

            // Form validation
            const form = document.getElementById('expenseForm');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate required fields
                const requiredFields = ['date', 'payee', 'check_number', 'bank', 'fund_type', 'expense_type', 'amount'];
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('error');
                    } else {
                        field.classList.remove('error');
                    }
                });
                
                // Validate amount is positive number
                if (amountInput.value && (isNaN(amountInput.value) || parseFloat(amountInput.value) < 0)) {
                    isValid = false;
                    amountInput.classList.add('error');
                } else {
                    amountInput.classList.remove('error');
                }
                
                // Validate tax is positive number if provided
                if (taxInput.value && (isNaN(taxInput.value) || parseFloat(taxInput.value) < 0)) {
                    isValid = false;
                    taxInput.classList.add('error');
                } else {
                    taxInput.classList.remove('error');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill all required fields with valid values.');
                }
            });

            console.log('Edit expense page loaded successfully!');
        });
    </script>
</body>
</html>