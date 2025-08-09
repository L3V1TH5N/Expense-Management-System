<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_expenses.php?error=Invalid request method');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get and validate form data
    $expense_id = (int)$_POST['expense_id'];
    $fund_type = trim($_POST['fund_type']);
    $bank = trim($_POST['bank']);
    $date = $_POST['date'];
    $check_number = trim($_POST['check_number']);
    $payee = trim($_POST['payee']);
    $expense_type = $_POST['expense_type'];
    $office_id = !empty($_POST['office_id']) ? (int)$_POST['office_id'] : null;
    $sub_office_id = !empty($_POST['sub_office_id']) ? (int)$_POST['sub_office_id'] : null;
    $amount = (float)$_POST['amount'];
    $tax = (float)$_POST['tax'];
    $total = (float)$_POST['total'];
    
    // Validation - Allow 0 amounts
    if (empty($fund_type) || empty($bank) || empty($date) || empty($check_number) || 
        empty($payee) || empty($expense_type) || $amount < 0) {
        throw new Exception('All required fields must be filled and amount cannot be negative');
    }
    
    // CORRECTED: ALL expense types require office selection (same as encode.php)
    $office_required_types = ['Personnel Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay', 'Cash Advance', 'Others'];
    if (in_array($expense_type, $office_required_types) && empty($office_id)) {
        throw new Exception('Office selection is required for all expense types');
    }
    
    // For Cash Advance, ensure tax is 0 (same as encode.php)
    if ($expense_type === 'Cash Advance') {
        $tax = 0;
        $total = $amount; // Recalculate total without tax
    }
    
    // Validate that the expense belongs to the current user
    $check_stmt = $conn->prepare("SELECT created_by FROM expenses WHERE id = ?");
    $check_stmt->execute([$expense_id]);
    $expense_owner = $check_stmt->fetchColumn();
    
    if (!$expense_owner || $expense_owner != $user_id) {
        throw new Exception('Expense not found or you do not have permission to edit it');
    }
    
    $conn->beginTransaction();
    
    // Get original expense data for logging
    $original_stmt = $conn->prepare("
        SELECT e.*, o.name as office_name, so.name as sub_office_name 
        FROM expenses e 
        LEFT JOIN offices o ON e.office_id = o.id 
        LEFT JOIN offices so ON e.sub_office_id = so.id 
        WHERE e.id = ?
    ");
    $original_stmt->execute([$expense_id]);
    $original_expense = $original_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update the expense
    $update_stmt = $conn->prepare("
        UPDATE expenses SET 
            fund_type = ?, 
            bank = ?, 
            date = ?, 
            check_number = ?, 
            payee = ?, 
            office_id = ?, 
            sub_office_id = ?, 
            expense_type = ?, 
            amount = ?, 
            tax = ?, 
            total = ?, 
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND created_by = ?
    ");
    
    $result = $update_stmt->execute([
        $fund_type, 
        $bank, 
        $date, 
        $check_number, 
        $payee, 
        $office_id, 
        $sub_office_id, 
        $expense_type, 
        $amount, 
        $tax, 
        $total, 
        $expense_id, 
        $user_id
    ]);
    
    if (!$result) {
        throw new Exception('Failed to update expense');
    }
    
    if ($update_stmt->rowCount() === 0) {
        throw new Exception('No changes were made or expense not found');
    }
    
    // Log the activity if function exists
    if (function_exists('logExpenseActivity')) {
        try {
            $new_data = [
                'fund_type' => $fund_type,
                'bank' => $bank,
                'date' => $date,
                'check_number' => $check_number,
                'payee' => $payee,
                'office_id' => $office_id,
                'sub_office_id' => $sub_office_id,
                'expense_type' => $expense_type,
                'amount' => $amount,
                'tax' => $tax,
                'total' => $total
            ];
            
            logExpenseActivity($conn, 'UPDATE', $expense_id, $original_expense, $new_data);
        } catch (Exception $e) {
            error_log("Failed to log expense update: " . $e->getMessage());
        }
    }
    
    $conn->commit();
    
    // Redirect with success message
    header('Location: my_expenses.php?success=' . urlencode('Expense updated successfully!'));
    exit;
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Error updating expense: " . $e->getMessage());
    header('Location: my_expenses.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>
