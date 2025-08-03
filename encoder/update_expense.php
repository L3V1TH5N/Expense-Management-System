<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate expense ID
        if (!isset($_POST['expense_id']) || !is_numeric($_POST['expense_id'])) {
            throw new Exception("Invalid expense ID");
        }
        
        $expense_id = (int)$_POST['expense_id'];
        $user_id = $_SESSION['user_id'];
        
        // Get original expense data for logging and validation
        $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ? AND created_by = ?");
        $stmt->execute([$expense_id, $user_id]);
        $original_expense = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$original_expense) {
            throw new Exception("Expense not found or access denied");
        }
        
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

        // Handle office assignments based on expense type
        $showOfficeTypes = ['Personal Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay'];
        
        if (in_array($data['expense_type'], $showOfficeTypes)) {
            if (empty($_POST['office_id'])) {
                throw new Exception("Office selection is required for this expense type");
            }
            
            $data['office_id'] = (int)$_POST['office_id'];
            $data['sub_office_id'] = !empty($_POST['sub_office_id']) ? (int)$_POST['sub_office_id'] : null;
            
            // Validate office hierarchy if sub-office is selected
            if ($data['sub_office_id'] && !validateOfficeHierarchy($conn, $data['office_id'], $data['sub_office_id'])) {
                throw new Exception("Selected sub-office doesn't belong to the selected office");
            }
        } else {
            $data['office_id'] = null;
            $data['sub_office_id'] = null;
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
            // Update the expense record
            $stmt = $conn->prepare("
                UPDATE expenses SET 
                    fund_type = ?, bank = ?, date = ?, check_number = ?, payee = ?,
                    office_id = ?, sub_office_id = ?, expense_type = ?,
                    amount = ?, tax = ?, total = ?
                WHERE id = ? AND created_by = ?
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
                $expense_id,
                $user_id
            ]);

            if (!$result) {
                throw new Exception("Failed to update expense record");
            }

            // Log the activity - FIXED: Using $pdo instead of $conn for activity logging
            $updated_data = [
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

            // FIXED: Use the correct PDO connection variable for logging
            logExpenseActivity($pdo, 'UPDATE', $expense_id, $updated_data, $original_expense);

            // Commit the transaction
            $conn->commit();
            
            $success = 'Expense updated successfully!';

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in expense update: " . $e->getMessage());
            throw new Exception("Database error occurred while updating the expense");
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error updating expense: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Redirect back to my_expenses.php with status
$redirect_url = 'my_expenses.php';
if ($success) {
    $redirect_url .= '?success=' . urlencode($success);
} elseif ($error) {
    $redirect_url .= '?error=' . urlencode($error);
}

header('Location: ' . $redirect_url);
exit;
?>