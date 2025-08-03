<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid expense ID']);
    exit;
}

$expense_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Get expense data - only allow access to expenses created by the current user
    $stmt = $conn->prepare("
        SELECT e.*, o.name as office_name, so.name as sub_office_name 
        FROM expenses e 
        LEFT JOIN offices o ON e.office_id = o.id 
        LEFT JOIN offices so ON e.sub_office_id = so.id 
        WHERE e.id = ? AND e.created_by = ?
    ");
    
    $stmt->execute([$expense_id, $user_id]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$expense) {
        http_response_code(404);
        echo json_encode(['error' => 'Expense not found or access denied']);
        exit;
    }
    
    // Format the response
    $response = [
        'id' => (int)$expense['id'],
        'fund_type' => $expense['fund_type'],
        'bank' => $expense['bank'],
        'date' => $expense['date'],
        'check_number' => $expense['check_number'],
        'payee' => $expense['payee'],
        'office_id' => $expense['office_id'] ? (int)$expense['office_id'] : null,
        'sub_office_id' => $expense['sub_office_id'] ? (int)$expense['sub_office_id'] : null,
        'office_name' => $expense['office_name'],
        'sub_office_name' => $expense['sub_office_name'],
        'expense_type' => $expense['expense_type'],
        'amount' => (float)$expense['amount'],
        'tax' => (float)$expense['tax'],
        'total' => (float)$expense['total'],
        'created_at' => $expense['created_at']
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in get_expense.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in get_expense.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>