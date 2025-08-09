<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

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

// Get statistics for display
$stats = [];

// Total expenses by current user
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM expenses WHERE created_by = ?");
$stmt->execute([$user_id]);
$stats['total_expenses'] = $stmt->fetch()['total'];

// Issued checks count (excluding Cash Advance)
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM expenses 
    WHERE created_by = ? AND expense_type IN ('Personnel Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay')
");
$stmt->execute([$user_id]);
$stats['issued_checks'] = $stmt->fetch()['count'];

// Cash advances count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM expenses WHERE created_by = ? AND expense_type = 'Cash Advance'");
$stmt->execute([$user_id]);
$stats['cash_advances'] = $stmt->fetch()['count'];

// Get statistics by fund type
$fund_stats = [];
$stmt = $conn->prepare("
    SELECT fund_type, COUNT(*) as count, SUM(total) as amount 
    FROM expenses 
    WHERE created_by = ? 
    GROUP BY fund_type
");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch()) {
    $fund_stats[$row['fund_type']] = [
        'count' => $row['count'],
        'amount' => $row['amount']
    ];
}

// Date range of available data
$stmt = $conn->prepare("
    SELECT MIN(date) as min_date, MAX(date) as max_date 
    FROM expenses WHERE created_by = ?
");
$stmt->execute([$user_id]);
$date_range = $stmt->fetch();

// Get available banks for the user
$stmt = $conn->prepare("SELECT DISTINCT bank FROM expenses WHERE created_by = ? ORDER BY bank");
$stmt->execute([$user_id]);
$available_banks = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get office statistics for Statement of Expenses
$office_stats = [];
$stmt = $conn->prepare("
    SELECT 
        COALESCE(o1.name, 'No Office Assigned') as office_name,
        COALESCE(o2.name, '') as parent_office_name,
        e.expense_type,
        SUM(e.total) as total_amount,
        COUNT(*) as transaction_count
    FROM expenses e
    LEFT JOIN offices o1 ON e.office_id = o1.id
    LEFT JOIN offices o2 ON o1.parent_id = o2.id
    WHERE e.created_by = ? AND e.expense_type IN ('Personnel Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay')
    GROUP BY COALESCE(o1.name, 'No Office Assigned'), COALESCE(o2.name, ''), e.expense_type
    ORDER BY COALESCE(o2.name, ''), COALESCE(o1.name, 'No Office Assigned'), e.expense_type
");
$stmt->execute([$user_id]);
$office_expense_data = $stmt->fetchAll();

// Get total by fund types for Statement of Expenses
$stmt = $conn->prepare("
    SELECT 
        fund_type,
        SUM(CASE WHEN expense_type = 'Personnel Services' THEN total ELSE 0 END) as Personnel_services,
        SUM(CASE WHEN expense_type = 'Maintenance and Other Operating Expenses' THEN total ELSE 0 END) as mooe,
        SUM(CASE WHEN expense_type = 'Capital Outlay' THEN total ELSE 0 END) as capital_outlay,
        SUM(total) as total_amount
    FROM expenses 
    WHERE created_by = ? AND expense_type IN ('Personnel Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay')
    GROUP BY fund_type
    ORDER BY fund_type
");
$stmt->execute([$user_id]);
$fund_totals = $stmt->fetchAll();
?>
