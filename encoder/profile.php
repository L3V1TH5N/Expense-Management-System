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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Expense Management System</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Profile Specific Styles */
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--light);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--grey);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 16px;
            font-weight: 600;
        }
        
        .profile-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .profile-header p {
            color: var(--dark-grey);
            font-size: 16px;
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .form-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--blue);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--grey);
            border-radius: 12px;
            font-size: 14px;
            font-family: var(--poppins);
            transition: all 0.3s ease;
            background: var(--light);
            color: var(--dark);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(60, 145, 230, 0.1);
        }
        
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--dark-grey);
            font-size: 20px;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--blue);
        }
        
        .form-actions {
            margin-top: 40px;
            text-align: center;
        }
        
        .btn-save {
            background: var(--blue);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.3);
        }
        
        .btn-save:hover {
            background: var(--blue);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(60, 145, 230, 0.4);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left-color: #28a745;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        .password-help {
            font-size: 13px;
            color: var(--dark-grey);
            margin-bottom: 16px;
            line-height: 1.5;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .strength-weak { color: var(--red); }
        .strength-medium { color: var(--yellow); }
        .strength-strong { color: #28a745; }
        
        .security-notice {
            background: var(--light-blue);
            border: 1px solid var(--blue);
            border-radius: 12px;
            padding: 16px;
            margin-top: 24px;
            font-size: 14px;
            color: var(--dark);
        }
        
        .security-notice i {
            margin-right: 8px;
            color: var(--blue);
        }
        
        .username-status {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }
        
        .status-available { color: #28a745; }
        .status-taken { color: var(--red); }
        .status-checking { color: var(--yellow); }
        
        @media screen and (max-width: 768px) {
            .profile-container {
                padding: 24px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 28px;
            }
            
            .btn-save {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-smile'></i>
            <span class="text"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="encode.php">
                    <i class='bx bxs-plus-circle'></i>
                    <span class="text">Encode Expense</span>
                </a>
            </li>
            <li>
                <a href="my_expenses.php">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">Expenses</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class='bx bxs-report'></i>
                    <span class="text">Reports</span>
                </a>
            </li>
            <li>
                <a href="export.php">
                    <i class='bx bxs-download'></i>
                    <span class="text">Export Reports</span>
                </a>
            </li>
            <br><br>
            <li class="active">
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
                    <h1>My Profile</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">My Profile</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="profile-container">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class='bx bx-check-circle'></i> 
                        Profile updated successfully!
                        <?php 
                        $changes = [];
                        if ($username_changed) $changes[] = "username";
                        if ($fullname_changed) $changes[] = "full name";
                        if ($password_change_requested && !empty($new_password)) $changes[] = "password";
                        
                        if (!empty($changes)): ?>
                            <br><small><i class='bx bx-info-circle'></i> Updated: <?php echo implode(', ', $changes); ?>. All changes have been logged for security purposes.</small>
                        <?php endif; ?>
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class='bx bx-error-circle'></i> 
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo substr($user['full_name'], 0, 1); ?>
                    </div>
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                
                <form method="POST" action="profile.php">
                    <div class="form-section">
                        <h3><i class='bx bx-user'></i> Basic Information</h3>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control"
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   required oninput="checkUsername()">
                            <div id="username_status" class="username-status"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control"
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class='bx bx-key'></i> Change Password</h3>
                        <p class="password-help">Leave password fields blank to keep current password. Click the eye icon to show/hide passwords.</p>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-input-group">
                                <input type="password" id="current_password" name="current_password" class="form-control"
                                       placeholder="Enter your current password">
                                <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                    <i class='bx bx-hide' id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-input-group">
                                <input type="password" id="new_password" name="new_password" class="form-control"
                                       placeholder="Enter new password (min. 8 characters)" 
                                       oninput="checkPasswordStrength()">
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                    <i class='bx bx-hide' id="new_password_icon"></i>
                                </button>
                            </div>
                            <div id="password_strength" class="password-strength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                       placeholder="Confirm your new password" 
                                       oninput="checkPasswordMatch()">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class='bx bx-hide' id="confirm_password_icon"></i>
                                </button>
                            </div>
                            <div id="password_match" class="password-strength"></div>
                        </div>
                        
                        <div class="security-notice">
                            <i class='bx bx-shield-check'></i>
                            <strong>Security Notice:</strong> All profile changes are logged for security purposes including the date, time, and type of change. Your actual password is never stored in logs.
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class='bx bx-save'></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script>
        let usernameCheckTimeout;
        const originalUsername = '<?php echo $user['username']; ?>';
        
        function checkUsername() {
            const username = document.getElementById('username').value;
            const statusDiv = document.getElementById('username_status');
            
            clearTimeout(usernameCheckTimeout);
            
            if (username.length < 3) {
                statusDiv.innerHTML = '<span class="status-taken">Username must be at least 3 characters</span>';
                return;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                statusDiv.innerHTML = '<span class="status-taken">Username can only contain letters, numbers, and underscores</span>';
                return;
            }
            
            if (username === originalUsername) {
                statusDiv.innerHTML = '<span class="status-available">Current username</span>';
                return;
            }
            
            statusDiv.innerHTML = '<span class="status-checking">Checking availability...</span>';
            
            usernameCheckTimeout = setTimeout(() => {
                fetch('check_username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'username=' + encodeURIComponent(username)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        statusDiv.innerHTML = '<span class="status-available"><i class="bx bx-check"></i> Username available</span>';
                    } else {
                        statusDiv.innerHTML = '<span class="status-taken"><i class="bx bx-x"></i> Username is already taken</span>';
                    }
                })
                .catch(error => {
                    statusDiv.innerHTML = '<span class="status-taken">Error checking username</span>';
                });
            }, 500);
        }
        
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId + '_icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'bx bx-show';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'bx bx-hide';
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthDiv = document.getElementById('password_strength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength++;
            else feedback.push('at least 8 characters');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('lowercase letter');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('uppercase letter');
            
            if (/\d/.test(password)) strength++;
            else feedback.push('number');
            
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
            else feedback.push('special character');
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 2) {
                strengthText = 'Weak password';
                strengthClass = 'strength-weak';
            } else if (strength < 4) {
                strengthText = 'Medium password';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong password';
                strengthClass = 'strength-strong';
            }
            
            if (feedback.length > 0 && strength < 4) {
                strengthText += ' - Add: ' + feedback.slice(0, 2).join(', ');
            }
            
            strengthDiv.innerHTML = `<span class="${strengthClass}">${strengthText}</span>`;
        }
        
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password_match');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<span class="strength-strong"><i class="bx bx-check"></i> Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<span class="strength-weak"><i class="bx bx-x"></i> Passwords do not match</span>';
            }
        }
        
        <?php if ($success): ?>
        document.getElementById('current_password').value = '';
        document.getElementById('new_password').value = '';
        document.getElementById('confirm_password').value = '';
        document.getElementById('password_strength').innerHTML = '';
        document.getElementById('password_match').innerHTML = '';
        <?php endif; ?>
    </script>
</body>
</html>