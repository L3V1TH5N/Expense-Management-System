
<?php
    // © 2025 Gavriell Pangan. All rights reserved.
    // Licensed for personal use only. No resale or redistribution.
?>


<?php
    session_start();
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    require_once 'includes/auth.php';

    if (isLoggedIn()) {
        // Redirect based on role if already logged in
        if ($_SESSION['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } elseif ($_SESSION['role'] === 'encoder') {
            header('Location: encoder/dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    }

    $error = '';

    if ($_POST) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Pass both $conn and $pdo to the login function
        if (login($username, $password, $conn, $pdo)) {
            // Redirect based on role after successful login
            if ($_SESSION['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($_SESSION['role'] === 'encoder') {
                header('Location: encoder/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
            
            // Log failed login attempt (optional)
            if ($pdo) {
                $failedLoginData = json_encode([
                    'attempted_username' => $username,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'attempt_time' => date('Y-m-d H:i:s')
                ]);
                
                // Log with user_id = 0 for failed attempts, record_id = 0
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO activity_logs (user_id, user_name, action, table_name, record_id, new_values, action_time)
                        VALUES (0, 'Unknown User', 'FAILED_LOGIN', 'users', 0, :failed_data, NOW())
                    ");
                    $stmt->execute([':failed_data' => $failedLoginData]);
                } catch (PDOException $e) {
                    error_log("Failed to log failed login attempt: " . $e->getMessage());
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Login - Expense Management System</title>

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="assets/css/login_style.css">
    </head>

    <body>
        <div class="login">
            <div class="formLogin">
                <div id="login-header">
                    <h3>Expense Management System</h3>
                    <p>Municipal Treasurer's Office</p>
                </div>

                <?php if (isset($error) && $error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                    
                <form id="formLogin" method="POST">
                    <div class="inputContainer">
                        <input type="text" id="username" name="username" class="inputLogin" required placeholder="Enter your username">
                    </div>
                        
                    <div class="inputContainer">
                        <input type="password" id="password" name="password" class="inputLogin" required placeholder="Enter your password">
                    </div>
                        
                    <button type="submit" class="login-button">Login</button>
                </form>
            </div>
        </div>
    </body>
</html>