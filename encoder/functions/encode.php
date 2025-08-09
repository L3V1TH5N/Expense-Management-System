<?php
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';

    requireLogin();

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $success = '';
    $error = '';

    $stmt = $conn->prepare("SELECT id, name, parent_id FROM offices ORDER BY parent_id, name");
    $stmt->execute();
    $all_offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $data = [];
            $data['fund_type'] = sanitizeInput($_POST['fund_type'] ?? '');
            $data['bank'] = sanitizeInput($_POST['bank'] ?? '');
            $data['date'] = sanitizeInput($_POST['date'] ?? '');
            $data['check_number'] = sanitizeInput($_POST['check_number'] ?? '');
            $data['payee'] = sanitizeInput($_POST['payee'] ?? '');
            $data['expense_type'] = sanitizeInput($_POST['expense_type'] ?? '');
            $data['amount'] = cleanNumericInput($_POST['amount'] ?? '0');
            $data['tax'] = cleanNumericInput($_POST['tax'] ?? '0');

            error_log("DEBUG - Raw amount input: " . ($_POST['amount'] ?? 'NULL'));
            error_log("DEBUG - Cleaned amount: " . $data['amount']);
            error_log("DEBUG - Amount type: " . gettype($data['amount']));

            $validation_errors = validateExpenseData($data);
            if (!empty($validation_errors)) {
                error_log("DEBUG - Validation errors: " . implode(", ", $validation_errors));
                throw new Exception(implode("<br>", $validation_errors));
            }

            $data['office_id'] = !empty($_POST['office_id']) ? (int)$_POST['office_id'] : null;
            $data['sub_office_id'] = !empty($_POST['sub_office_id']) ? (int)$_POST['sub_office_id'] : null;

            if ($data['sub_office_id'] && !validateOfficeHierarchy($conn, $data['office_id'], $data['sub_office_id'])) {
                throw new Exception("Selected sub-office doesn't belong to the selected office");
            }

            if (isOfficeRequiredForExpenseType($data['expense_type']) && empty($data['office_id'])) {
                throw new Exception("Office selection is required for this expense type");
            }

            $amount = (float)$data['amount'];
            $tax = (float)$data['tax'];
            if (!isTaxApplicableForExpenseType($data['expense_type'])) {
                $tax = 0;
            }
            $total = $amount + $tax;
            if ($amount < 0) {
                throw new Exception("Amount cannot be negative");
            }

            if ($tax < 0) {
                throw new Exception("Tax cannot be negative");
            }
            $conn->beginTransaction();

            try {
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
                $conn->commit();
                $success = 'Expense encoded successfully!';
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

    $stmt = $conn->prepare("SELECT id, name FROM offices WHERE parent_id IS NULL ORDER BY name");
    $stmt->execute();
    $main_offices = $stmt->fetchAll();
?>
