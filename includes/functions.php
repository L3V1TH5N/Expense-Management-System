<?php
    function getOffices($conn, $parent_id = null) {
        if ($parent_id === null) {
            $stmt = $conn->prepare("SELECT * FROM offices WHERE parent_id IS NULL ORDER BY name");
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("SELECT * FROM offices WHERE parent_id = ? ORDER BY name");
            $stmt->execute([$parent_id]);
        }
        return $stmt->fetchAll();
    }

    function getSubOffices($conn, $parent_id) {
        $stmt = $conn->prepare("SELECT * FROM offices WHERE parent_id = ? ORDER BY name");
        $stmt->execute([$parent_id]);
        return $stmt->fetchAll();
    }

    // NEW: Validate office hierarchy
    function validateOfficeHierarchy($conn, $parent_id, $sub_office_id) {
        if (!$parent_id || !$sub_office_id) {
            return true; // If either is null, it's valid
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM offices WHERE id = ? AND parent_id = ?");
        $stmt->execute([$sub_office_id, $parent_id]);
        return $stmt->fetchColumn() > 0;
    }

    function formatCurrency($amount) {
        return number_format($amount, 2);
    }

    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    function getOfficeName($conn, $office_id) {
        if (!$office_id) return 'N/A';
        
        $stmt = $conn->prepare("SELECT name FROM offices WHERE id = ?");
        $stmt->execute([$office_id]);
        $office = $stmt->fetch();
        
        return $office ? $office['name'] : 'Unknown Office';
    }

    function getFullOfficePath($conn, $office_id) {
        if (!$office_id) return 'N/A';
        
        $stmt = $conn->prepare("
            SELECT o1.name as office_name, o2.name as parent_name 
            FROM offices o1 
            LEFT JOIN offices o2 ON o1.parent_id = o2.id 
            WHERE o1.id = ?
        ");
        $stmt->execute([$office_id]);
        $office = $stmt->fetch();
        
        if (!$office) return 'Unknown Office';
        
        if ($office['parent_name']) {
            return $office['parent_name'] . ' > ' . $office['office_name'];
        }
        
        return $office['office_name'];
    }

    function calculateTotal($amount, $tax = 0) {
        return floatval($amount) + floatval($tax);
    }

    function isValidAmount($amount) {
        return is_numeric($amount) && $amount >= 0;
    }

    function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }

    function generateCheckNumber($prefix = 'CHK') {
        return $prefix . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    // NEW: Additional validation functions
    function validateExpenseData($data) {
        $errors = [];
        
        // Required fields validation
        $required_fields = [
            'fund_type' => 'Fund Type',
            'bank' => 'Bank',
            'date' => 'Date',
            'check_number' => 'Check Number',
            'payee' => 'Payee',
            'expense_type' => 'Expense Type',
            'amount' => 'Amount'
        ];

        foreach ($required_fields as $field => $name) {
            if (empty($data[$field])) {
                $errors[] = "$name is required";
            }
        }

        // Validate amount
        if (!empty($data['amount'])) {
            if (!is_numeric($data['amount']) || $data['amount'] < 0) {
                $errors[] = "Amount must be a valid positive number";
            }
        }

        // Validate tax
        if (isset($data['tax']) && !empty($data['tax'])) {
            if (!is_numeric($data['tax']) || $data['tax'] < 0) {
                $errors[] = "Tax must be a valid positive number";
            }
        }

        // Validate date
        if (!empty($data['date']) && !validateDate($data['date'])) {
            $errors[] = "Invalid date format";
        }

        // Validate fund type
        $valid_fund_types = ['General Fund', 'Special Education Fund', 'Trust Fund'];
        if (!empty($data['fund_type']) && !in_array($data['fund_type'], $valid_fund_types)) {
            $errors[] = "Invalid fund type";
        }

        // Validate expense type
        $valid_expense_types = [
            'Personal Services',
            'Maintenance and Other Operating Expenses',
            'Capital Outlay',
            'Cash Advance'
        ];
        if (!empty($data['expense_type']) && !in_array($data['expense_type'], $valid_expense_types)) {
            $errors[] = "Invalid expense type";
        }

        return $errors;
    }

    // NEW: Clean numeric input (remove formatting)
    function cleanNumericInput($value) {
        return str_replace([',', '$'], '', $value);
    }
?>