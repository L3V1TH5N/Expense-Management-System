<?php include 'functions/profile.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Expense Management System</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="functions/css/profile.css">
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-wallet'></i>
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
                    <span class="text">Expenses Records</span>
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
