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

// Fetch expense details
$query = "
    SELECT 
        e.*, 
        o.name as office_name, 
        so.name as sub_office_name,
        u.full_name as encoder_name,
        u.username as encoder_username
    FROM expenses e 
    LEFT JOIN offices o ON e.office_id = o.id 
    LEFT JOIN offices so ON e.sub_office_id = so.id 
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$expense_id]);
$expense = $stmt->fetch();

if (!$expense) {
    header('Location: all_expenses.php');
    exit();
}

// Format dates for display
$expense['date_formatted'] = date('F j, Y', strtotime($expense['date']));
$expense['created_at_formatted'] = date('F j, Y g:i A', strtotime($expense['created_at']));
$expense['updated_at_formatted'] = isset($expense['updated_at']) && $expense['updated_at'] ? date('F j, Y g:i A', strtotime($expense['updated_at'])) : 'Not updated yet';

// Format amount with currency
$expense['amount_formatted'] = '₱' . number_format($expense['amount'], 2);
$expense['tax_formatted'] = $expense['tax'] ? '₱' . number_format($expense['tax'], 2) : 'N/A';
$expense['total_formatted'] = '₱' . number_format($expense['total'], 2);

// Get activity logs for this expense
$activity_query = "
    SELECT al.*, u.username 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.table_name = 'expenses' AND al.record_id = ?
    ORDER BY al.action_time DESC
    LIMIT 10
";
$activity_stmt = $conn->prepare($activity_query);
$activity_stmt->execute([$expense_id]);
$activities = $activity_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>View Expense - Expense Management System</title>
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

        /* Expense Detail Container */
        .expense-detail-container {
            background: var(--light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid var(--grey);
            position: relative;
            overflow: hidden;
        }

        .expense-detail-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .expense-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .expense-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .expense-header h2 i {
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

        /* Detail Sections */
        .detail-section {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--grey);
        }

        .detail-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-section h3 i {
            color: var(--blue);
        }

        /* Detail Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            margin-bottom: 12px;
        }

        .detail-item strong {
            display: block;
            font-size: 14px;
            color: var(--dark-grey);
            margin-bottom: 4px;
        }

        .detail-item p {
            font-size: 16px;
            color: var(--dark);
            margin: 0;
            word-break: break-word;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
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

        /* Amount Highlight */
        .amount-highlight {
            font-size: 24px;
            font-weight: 700;
            color: var(--blue);
        }

        /* Activity Log */
        .activity-log {
            margin-top: 32px;
        }

        .activity-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            border-radius: 12px;
            background: var(--grey);
            margin-bottom: 12px;
            transition: all var(--animation-speed) ease;
        }

        .activity-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .activity-content {
            flex-grow: 1;
        }

        .activity-message {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .activity-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--dark-grey);
        }

        .activity-user {
            font-weight: 600;
            color: var(--blue);
        }

        .activity-time {
            font-style: italic;
        }

        /* Action Buttons */
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

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .detail-grid {
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

        .expense-detail-container {
            animation: fadeIn 0.6s ease-out forwards;
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
                            <a href="all_expenses.php">All Expenses</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">View Expense</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <!-- Expense Detail Container -->
            <div class="expense-detail-container">
                <div class="expense-header">
                    <h2>
                        <i class='bx bx-receipt'></i>
                        Expense Details
                    </h2>
                </div>

                <!-- Basic Information Section -->
                <div class="detail-section">
                    <h3><i class='bx bx-info-circle'></i> Basic Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>Date</strong>
                            <p><?php echo $expense['date_formatted']; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Payee</strong>
                            <p><?php echo htmlspecialchars($expense['payee']); ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Check Number</strong>
                            <p><?php echo htmlspecialchars($expense['check_number']); ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Bank</strong>
                            <p><?php echo htmlspecialchars($expense['bank']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Financial Information Section -->
                <div class="detail-section">
                    <h3><i class='bx bx-money'></i> Financial Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>Fund Type</strong>
                            <p>
                                <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $expense['fund_type'])); ?>">
                                    <i class='bx bx-wallet'></i>
                                    <?php echo htmlspecialchars($expense['fund_type']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <strong>Expense Type</strong>
                            <p><?php echo htmlspecialchars($expense['expense_type']); ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Amount</strong>
                            <p class="amount-highlight"><?php echo $expense['amount_formatted']; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Tax</strong>
                            <p><?php echo $expense['tax_formatted']; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Total</strong>
                            <p class="amount-highlight"><?php echo $expense['total_formatted']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Office Information Section -->
                <div class="detail-section">
                    <h3><i class='bx bx-building'></i> Office Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>Office</strong>
                            <p><?php echo $expense['office_name'] ? htmlspecialchars($expense['office_name']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Sub Office</strong>
                            <p><?php echo $expense['sub_office_name'] ? htmlspecialchars($expense['sub_office_name']) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- System Information Section -->
                <div class="detail-section">
                    <h3><i class='bx bx-data'></i> System Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>Created By</strong>
                            <p><?php echo htmlspecialchars($expense['encoder_name']); ?> (<?php echo htmlspecialchars($expense['encoder_username']); ?>)</p>
                        </div>
                        <div class="detail-item">
                            <strong>Created At</strong>
                            <p><?php echo $expense['created_at_formatted']; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Last Updated</strong>
                            <p><?php echo $expense['updated_at_formatted']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-primary">
                        <i class='bx bx-edit'></i>
                        Edit Expense
                    </a>
                    <a href="all_expenses.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i>
                        Back to All Expenses
                    </a>
                    <a href="delete_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this expense? This action cannot be undone.');">
                        <i class='bx bx-trash'></i>
                        Delete Expense
                    </a>
                </div>
            </div>

            
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate activity items on load
            const activityItems = document.querySelectorAll('.activity-item');
            activityItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add confirmation for delete action
            const deleteBtn = document.querySelector('.btn-danger');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            }

            // Add hover effects to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            console.log('Expense detail page loaded successfully!');
        });
    </script>
</body>
</html>
