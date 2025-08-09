<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Get current user data
$stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Track what's being changed
    $password_change_requested = !empty($current_password) || !empty($new_password) || !empty($confirm_password);
    $username_changed = $username !== $user['username'];
    $fullname_changed = $full_name !== $user['full_name'];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username must be 50 characters or less";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    } elseif ($username_changed) {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Username is already taken";
        }
    }
    
    // Validate full name
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($full_name) > 100) {
        $errors[] = "Full name must be 100 characters or less";
    }
    
    // Only validate passwords if any password field is filled
    if ($password_change_requested) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $db_password = $stmt->fetchColumn();
        
        if (!password_verify($current_password, $db_password)) {
            $errors[] = "Current password is incorrect";
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters";
        }
        
        // Confirm password match
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Prepare old values for logging
            $old_values = [
                'username' => $user['username'],
                'full_name' => $user['full_name']
            ];
            
            // Prepare new values for logging
            $new_values = [
                'username' => $username,
                'full_name' => $full_name,
                'username_changed' => $username_changed,
                'fullname_changed' => $fullname_changed,
                'password_changed' => !empty($new_password),
                'update_time' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ];
            
            // Update user data
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $full_name, $hashed_password, $user_id]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ? WHERE id = ?");
                $stmt->execute([$username, $full_name, $user_id]);
            }
            
            // Log the profile update activity (using $pdo, not $conn)
            logActivity($pdo, $user_id, $full_name, 'UPDATE', 'users', $user_id, $old_values, $new_values);
            
            // Log separate username change activity if username was changed
            if ($username_changed) {
                $username_change_data = [
                    'action_type' => 'username_change',
                    'old_username' => $user['username'],
                    'new_username' => $username,
                    'user_id' => $user_id,
                    'full_name' => $full_name,
                    'change_time' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ];
                
                logActivity($pdo, $user_id, $full_name, 'USERNAME_CHANGE', 'users', $user_id, null, $username_change_data);
            }
            
            // Log separate password change activity if password was changed
            if (!empty($new_password)) {
                $password_change_data = [
                    'action_type' => 'password_change',
                    'user_id' => $user_id,
                    'username' => $username, // Use new username
                    'full_name' => $full_name,
                    'change_time' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ];
                
                logActivity($pdo, $user_id, $full_name, 'PASSWORD_CHANGE', 'users', $user_id, null, $password_change_data);
            }
            
            $conn->commit();
            
            // Update session with new data
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $full_name;
            
            $success = true;
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in profile.php: " . $e->getMessage());
            $errors[] = "Database error occurred";
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error in profile.php: " . $e->getMessage());
            $errors[] = "An error occurred while updating profile";
        }
    }
}
?>
