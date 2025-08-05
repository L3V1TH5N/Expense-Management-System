<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $username = sanitizeInput($_POST['username']);
                $password = $_POST['password'];
                $full_name = sanitizeInput($_POST['full_name']);
                $role = sanitizeInput($_POST['role']);
                
                // Validate inputs
                $errors = [];
                if (empty($username)) $errors[] = "Username is required";
                if (empty($password)) $errors[] = "Password is required";
                if (empty($full_name)) $errors[] = "Full name is required";
                if (!in_array($role, ['admin', 'encoder'])) $errors[] = "Invalid role";
                
                if (empty($errors)) {
                    // Check if username exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    
                    if ($stmt->fetch()) {
                        $errors[] = "Username already exists";
                    } else {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert new user
                        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$username, $hashed_password, $full_name, $role])) {
                            $new_user_id = $conn->lastInsertId();
                            
                            // Log the activity
                            logUserActivity($pdo, 'CREATE', $new_user_id, [
                                'username' => $username,
                                'full_name' => $full_name,
                                'role' => $role
                            ]);
                            
                            $_SESSION['success_message'] = "User created successfully";
                            header("Location: manage_users.php");
                            exit();
                        } else {
                            $errors[] = "Failed to create user";
                        }
                    }
                }
                break;
                
            case 'edit_user':
                $user_id = (int)$_POST['user_id'];
                $username = sanitizeInput($_POST['username']);
                $full_name = sanitizeInput($_POST['full_name']);
                $role = sanitizeInput($_POST['role']);
                
                // Get current user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_user = $stmt->fetch();
                
                if (!$current_user) {
                    $errors[] = "User not found";
                } else {
                    // Validate inputs
                    $errors = [];
                    if (empty($username)) $errors[] = "Username is required";
                    if (empty($full_name)) $errors[] = "Full name is required";
                    if (!in_array($role, ['admin', 'encoder'])) $errors[] = "Invalid role";
                    
                    if (empty($errors)) {
                        // Check if username exists (excluding current user)
                        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                        $stmt->execute([$username, $user_id]);
                        
                        if ($stmt->fetch()) {
                            $errors[] = "Username already exists";
                        } else {
                            // Prepare old values for logging
                            $old_values = [
                                'username' => $current_user['username'],
                                'full_name' => $current_user['full_name'],
                                'role' => $current_user['role']
                            ];
                            
                            // Update user
                            $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?");
                            if ($stmt->execute([$username, $full_name, $role, $user_id])) {
                                // Log the activity
                                logUserActivity($pdo, 'UPDATE', $user_id, [
                                    'username' => $username,
                                    'full_name' => $full_name,
                                    'role' => $role
                                ], $old_values);
                                
                                $_SESSION['success_message'] = "User updated successfully";
                                header("Location: manage_users.php");
                                exit();
                            } else {
                                $errors[] = "Failed to update user";
                            }
                        }
                    }
                }
                break;
                
            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                
                // Get user data before deletion for logging
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $errors[] = "User not found";
                } else if ($user_id == $_SESSION['user_id']) {
                    $errors[] = "You cannot delete your own account";
                } else {
                    // Delete user
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        // Log the activity
                        logUserActivity($pdo, 'DELETE', $user_id, null, [
                            'username' => $user['username'],
                            'full_name' => $user['full_name'],
                            'role' => $user['role']
                        ]);
                        
                        $_SESSION['success_message'] = "User deleted successfully";
                        header("Location: manage_users.php");
                        exit();
                    } else {
                        $errors[] = "Failed to delete user";
                    }
                }
                break;
                
            case 'reset_password':
                $user_id = (int)$_POST['user_id'];
                $new_password = $_POST['new_password'];
                
                if (empty($new_password)) {
                    $errors[] = "New password is required";
                } else {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        // Log the password change
                        logPasswordChange($pdo, $user_id, $_POST['full_name'], [
                            'changed_by' => $_SESSION['user_id'],
                            'changed_by_name' => $_SESSION['full_name']
                        ]);
                        
                        $_SESSION['success_message'] = "Password reset successfully";
                        header("Location: manage_users.php");
                        exit();
                    } else {
                        $errors[] = "Failed to reset password";
                    }
                }
                break;
        }
    }
}

// Get all users with statistics
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COUNT(e.id) as expense_count,
        COALESCE(SUM(e.total), 0) as total_amount,
        MAX(e.created_at) as last_activity
    FROM users u 
    LEFT JOIN expenses e ON u.id = e.created_by 
    GROUP BY u.id 
    ORDER BY u.role DESC, u.username ASC
");
$stmt->execute();
$users = $stmt->fetchAll();

// Get user statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$total_users = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$stmt->execute();
$total_admins = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'encoder'");
$stmt->execute();
$total_encoders = $stmt->fetch()['total'];

// Get success message if any
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

// Get admin user information
$user_id = $_SESSION['user_id'];
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Manage Users - Expense Management System</title>
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-danger: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --gradient-admin: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-encoder: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --shadow-light: 0 4px 20px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
            --border-radius: 16px;
            --animation-speed: 0.3s;
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
        }

        /* Page Header Banner */
        .user-management-banner {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .user-management-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.08'%3E%3Cpath d='M20 20c0 4.4-3.6 8-8 8s-8-3.6-8-8 3.6-8 8-8 8 3.6 8 8zm0-20c0 4.4-3.6 8-8 8s-8-3.6-8-8 3.6-8 8-8 8 3.6 8 8z'/%3E%3C/g%3E%3C/svg%3E") repeat;
            animation: float 30s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .user-management-banner h2 {
            font-size: 28px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-management-banner p {
            opacity: 0.9;
            font-size: 16px;
        }

        /* User Statistics Cards */
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .user-stats .stat-card {
            background: var(--light);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .user-stats .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }

        .user-stats .stat-card.total-users::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .user-stats .stat-card.total-admins::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-admin);
        }

        .user-stats .stat-card.total-encoders::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-encoder);
        }

        .stat-card i {
            font-size: 32px;
            flex-shrink: 0;
        }

        .stat-card.total-users i { color: #667eea; }
        .stat-card.total-admins i { color: #764ba2; }
        .stat-card.total-encoders i { color: #f093fb; }

        .stat-card .text h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .stat-card .text p {
            color: var(--dark-grey);
            font-size: 13px;
            margin: 0;
            line-height: 1.3;
        }

        /* Enhanced Form Styling */
        .user-form-container {
            background: var(--light);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
            position: relative;
            overflow: hidden;
        }

        .user-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-success);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--grey);
        }

        .form-header h3 {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .form-header i {
            font-size: 28px;
            color: var(--blue);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .form-group {
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--grey);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--light);
            color: var(--dark);
        }

        .form-group input:focus, 
        .form-group select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(60, 145, 230, 0.1);
            transform: translateY(-1px);
        }

        .form-group input:hover, 
        .form-group select:hover {
            border-color: var(--dark-grey);
        }

        /* Enhanced Button Styling */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
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
            transition: left 0.6s ease;
        }

        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: var(--gradient-danger);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 65, 108, 0.4);
        }
        
        .btn-warning {
            background: var(--gradient-warning);
            color: white;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
        }
        
        .btn-info {
            background: var(--gradient-info);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
        }

        /* Enhanced Table Styling */
        .users-table-container {
            background: var(--light);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--grey);
        }

        .table-header h3 {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-header i {
            font-size: 28px;
            color: var(--blue);
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
        }
        
        .user-table th, 
        .user-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--grey);
        }
        
        .user-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-table tbody tr {
            transition: all 0.3s ease;
        }

        .user-table tbody tr:hover {
            background: var(--grey);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .user-details h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .user-details p {
            margin: 0;
            font-size: 13px;
            color: var(--dark-grey);
        }
        
        /* Enhanced Badge Styling */
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
        
        .badge-admin {
            background: var(--gradient-admin);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .badge-encoder {
            background: var(--gradient-encoder);
            color: white;
            box-shadow: 0 2px 8px rgba(240, 147, 251, 0.3);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            padding: 8px 12px;
            font-size: 12px;
            min-width: auto;
        }

        /* User Statistics in Table */
        .user-stats-mini {
            font-size: 12px;
            color: var(--dark-grey);
        }

        .user-stats-mini .stat {
            display: block;
            margin-bottom: 2px;
        }

        .user-stats-mini .stat strong {
            color: var(--dark);
        }

        /* Enhanced Modal Styling */
        .modal {
            font-family: 'Poppins', sans-serif;
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: var(--light);
            margin: 8% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
            overflow: hidden;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            background: var(--gradient-primary);
            color: white;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            color: white;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
        }

        .close:hover {
            opacity: 1;
            background: rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 32px;
        }

        .modal .form-group {
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }
        
        /* Enhanced Alert Styling */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-success::before {
            background: #28a745;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-danger::before {
            background: #dc3545;
        }

        .alert i {
            font-size: 20px;
        }

        /* Navigation Text */
        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .user-stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .user-form-container,
            .users-table-container {
                padding: 20px;
                margin-bottom: 20px;
            }

            .user-management-banner {
                padding: 1.5rem;
            }

            .user-management-banner h2 {
                font-size: 24px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .user-table {
                font-size: 14px;
            }

            .user-table th,
            .user-table td {
                padding: 12px 8px;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .modal-body {
                padding: 24px;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in {
            animation: slideInRight 0.5s ease-out forwards;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Loading States */
        .btn.loading {
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn.loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark-grey);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--grey);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            opacity: 0;
            animation: tooltipFadeIn 0.3s ease forwards;
        }

        @keyframes tooltipFadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(4px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
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
            <li class="active">
                <a href="manage_users.php">
                    <i class='bx bxs-user-account'></i>
                    <span class="text">Manage Users</span>
                </a>
            </li>
            <li>
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
                            <a class="active" href="#">Manage Users</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success fade-in">
                    <i class='bx bxs-check-circle'></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fade-in">
                    <i class='bx bxs-error-circle'></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p style="margin: 4px 0;"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Statistics -->
            <div class="user-stats fade-in">
                <div class="stat-card total-users">
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>Total System Users</p>
                    </span>
                </div>

                <div class="stat-card total-admins">
                    <i class='bx bxs-shield-alt-2'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_admins); ?></h3>
                        <p>Administrators</p>
                    </span>
                </div>

                <div class="stat-card total-encoders">
                    <i class='bx bxs-user-check'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_encoders); ?></h3>
                        <p>Encoders</p>
                    </span>
                </div>
            </div>

            <!-- Add New User Form -->
            <div class="user-form-container slide-in">
                <div class="form-header">
                    <i class='bx bxs-user-plus'></i>
                    <h3>Add New User</h3>
                </div>
                <form method="POST" action="manage_users.php" id="addUserForm">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">
                                <i class='bx bxs-user'></i> Username
                            </label>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Enter unique username" 
                                   data-tooltip="Username must be unique in the system">
                        </div>
                        <div class="form-group">
                            <label for="password">
                                <i class='bx bxs-lock'></i> Password
                            </label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter secure password"
                                   data-tooltip="Use a strong password with at least 8 characters">
                        </div>
                        <div class="form-group">
                            <label for="full_name">
                                <i class='bx bxs-id-card'></i> Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" required 
                                   placeholder="Enter complete full name">
                        </div>
                        <div class="form-group">
                            <label for="role">
                                <i class='bx bxs-user-badge'></i> Role
                            </label>
                            <select id="role" name="role" required>
                                <option value="">Select user role</option>
                                <option value="admin">Administrator</option>
                                <option value="encoder">Encoder</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bxs-user-plus'></i>
                        Create User Account
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="users-table-container slide-in">
                <div class="table-header">
                    <h3>
                        <i class='bx bxs-group'></i>
                        System Users (<?php echo count($users); ?>)
                    </h3>
                </div>
                
                <?php if (!empty($users)): ?>
                <div style="overflow-x: auto;">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th><i class='bx bxs-user'></i> User Information</th>
                                <th><i class='bx bxs-user-badge'></i> Role</th>
                                <th><i class='bx bxs-data'></i> Statistics</th>
                                <th><i class='bx bxs-time'></i> Last Activity</th>
                                <th><i class='bx bxs-cog'></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                            <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                        <i class='bx <?php echo $user['role'] === 'admin' ? 'bxs-shield-alt-2' : 'bxs-user'; ?>'></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="user-stats-mini">
                                        <span class="stat">
                                            <strong><?php echo number_format($user['expense_count']); ?></strong> expenses
                                        </span>
                                        <span class="stat">
                                            <strong>₱<?php echo number_format($user['total_amount'], 2); ?></strong> total
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['last_activity']): ?>
                                        <span style="font-size: 13px; color: var(--dark-grey);">
                                            <?php echo date('M j, Y', strtotime($user['last_activity'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size: 13px; color: var(--dark-grey);">
                                            Never
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info edit-user" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-tooltip="Edit user information">
                                            <i class='bx bxs-edit'></i>
                                        </button>
                                        <button class="btn btn-warning reset-password" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            data-tooltip="Reset user password">
                                            <i class='bx bxs-key'></i>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-danger delete-user" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-tooltip="Delete user account">
                                            <i class='bx bxs-trash'></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class='bx bxs-user-x'></i>
                    <h3>No Users Found</h3>
                    <p>There are no users in the system yet. Create the first user account above.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class='bx bxs-edit'></i>
                    Edit User Account
                </h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST" action="manage_users.php">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-group">
                        <label for="edit_username">
                            <i class='bx bxs-user'></i> Username
                        </label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_full_name">
                            <i class='bx bxs-id-card'></i> Full Name
                        </label>
                        <input type="text" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">
                            <i class='bx bxs-user-badge'></i> Role
                        </label>
                        <select id="edit_role" name="role" required>
                            <option value="admin">Administrator</option>
                            <option value="encoder">Encoder</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class='bx bx-x'></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bxs-save'></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class='bx bxs-key'></i>
                    Reset User Password
                </h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="resetPasswordForm" method="POST" action="manage_users.php">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" id="reset_user_id" name="user_id">
                    <input type="hidden" id="reset_full_name" name="full_name">
                    <div class="form-group">
                        <label for="new_password">
                            <i class='bx bxs-lock'></i> New Password
                        </label>
                        <input type="password" id="new_password" name="new_password" required 
                               placeholder="Enter new secure password">
                    </div>
                    <div style="background: #fff3cd; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #856404;">
                        <strong>Note:</strong> The user will need to use this new password to log in. Make sure to communicate it securely.
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class='bx bx-x'></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class='bx bxs-key'></i>
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--gradient-danger);">
                <h3 class="modal-title">
                    <i class='bx bxs-trash'></i>
                    Delete User Account
                </h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="deleteUserForm" method="POST" action="manage_users.php">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" id="delete_user_id" name="user_id">
                    
                    <div style="text-align: center; margin-bottom: 24px;">
                        <i class='bx bxs-error-circle' style="font-size: 64px; color: var(--red); margin-bottom: 16px;"></i>
                        <h3 style="color: var(--dark); margin-bottom: 8px;">Are you absolutely sure?</h3>
                        <p style="color: var(--dark-grey); margin-bottom: 16px;">
                            You are about to delete user account: <strong id="delete_username_display"></strong>
                        </p>
                        <div style="background: #f8d7da; padding: 12px; border-radius: 8px; color: #721c24; font-size: 14px;">
                            <strong>Warning:</strong> This action cannot be undone. All user data and associated records will remain but the user will no longer be able to access the system.
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class='bx bx-x'></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class='bx bxs-trash'></i>
                            Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation delays for elements
            const fadeElements = document.querySelectorAll('.fade-in');
            const slideElements = document.querySelectorAll('.slide-in');
            
            fadeElements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
            
            slideElements.forEach((el, index) => {
                el.style.animationDelay = `${(index + fadeElements.length) * 0.1}s`;
            });

            // Modal functionality
            const editButtons = document.querySelectorAll('.edit-user');
            const editModal = document.getElementById('editUserModal');
            const editForm = document.getElementById('editUserForm');
            
            const resetButtons = document.querySelectorAll('.reset-password');
            const resetModal = document.getElementById('resetPasswordModal');
            const resetForm = document.getElementById('resetPasswordForm');
            
            const deleteButtons = document.querySelectorAll('.delete-user');
            const deleteModal = document.getElementById('deleteUserModal');
            const deleteForm = document.getElementById('deleteUserForm');

            // Edit User Modal
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('edit_user_id').value = this.dataset.userId;
                    document.getElementById('edit_username').value = this.dataset.username;
                    document.getElementById('edit_full_name').value = this.dataset.fullName;
                    document.getElementById('edit_role').value = this.dataset.role;
                    editModal.style.display = 'block';
                });
            });
            
            // Reset Password Modal
            resetButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('reset_user_id').value = this.dataset.userId;
                    document.getElementById('reset_full_name').value = this.dataset.fullName;
                    document.getElementById('new_password').value = '';
                    resetModal.style.display = 'block';
                });
            });
            
            // Delete User Modal
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_user_id').value = this.dataset.userId;
                    document.getElementById('delete_username_display').textContent = this.dataset.username;
                    deleteModal.style.display = 'block';
                });
            });
            
            // Close modals
            const closeButtons = document.querySelectorAll('.close, .close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    editModal.style.display = 'none';
                    resetModal.style.display = 'none';
                    deleteModal.style.display = 'none';
                });
            });
            
            // Close when clicking outside modal
            window.addEventListener('click', function(event) {
                if (event.target === editModal) {
                    editModal.style.display = 'none';
                }
                if (event.target === resetModal) {
                    resetModal.style.display = 'none';
                }
                if (event.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });

            // Form validation and loading states
            const forms = [editForm, resetForm, deleteForm];
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitButton = form.querySelector('button[type="submit"]');
                    submitButton.classList.add('loading');
                    submitButton.disabled = true;
                });
            });

            // Add User Form validation
            const addUserForm = document.getElementById('addUserForm');
            addUserForm.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.classList.add('loading');
                submitButton.disabled = true;
                
                // Basic client-side validation
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;
                const fullName = document.getElementById('full_name').value.trim();
                const role = document.getElementById('role').value;
                
                if (!username || !password || !fullName || !role) {
                    e.preventDefault();
                    submitButton.classList.remove('loading');
                    submitButton.disabled = false;
                    alert('Please fill in all required fields.');
                    return;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    submitButton.classList.remove('loading');
                    submitButton.disabled = false;
                    alert('Password must be at least 6 characters long.');
                    return;
                }
            });

            // Enhanced table interactions
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'fadeOut 0.5s ease forwards';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            });

            // Add fadeOut animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeOut {
                    from { opacity: 1; transform: translateY(0); }
                    to { opacity: 0; transform: translateY(-20px); }
                }
            `;
            document.head.appendChild(style);

            // Enhanced user avatar colors
            const avatars = document.querySelectorAll('.user-avatar');
            const colors = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'
            ];
            
            avatars.forEach((avatar, index) => {
                avatar.style.background = colors[index % colors.length];
            });
        });
    </script>
</body>
</html>
