<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function login($username, $password, $conn, $pdo = null) {
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Log successful login activity
        if ($pdo) {
            $loginData = [
                'username' => $user['username'],
                'role' => $user['role'],
                'login_time' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ];
            
            logActivity($pdo, $user['id'], $user['full_name'], 'LOGIN', 'users', $user['id'], null, $loginData);
        }
        
        return true;
    }
    return false;
}

function logout($pdo = null) {
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $userName = $_SESSION['full_name'];
        $username = $_SESSION['username'];
        
        if ($pdo) {
            try {
                $logoutData = [
                    'username' => $username,
                    'logout_time' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ];
                
                logActivity($pdo, $userId, $userName, 'LOGOUT', 'users', $userId, null, $logoutData);
            } catch (Exception $e) {
                error_log("Failed to log logout activity: " . $e->getMessage());
            }
        }
    }
    
    session_destroy();
    header('Location: login.php');
    exit();
}

function logActivity($pdo, $userId, $userName, $action, $tableName, $recordId, $oldValues = null, $newValues = null) {
    try {
        // Convert arrays to JSON if they aren't already strings
        if ($oldValues && !is_string($oldValues)) {
            $oldValues = json_encode($oldValues, JSON_PRETTY_PRINT);
        }
        
        if ($newValues && !is_string($newValues)) {
            $newValues = json_encode($newValues, JSON_PRETTY_PRINT);
        }

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id, user_name, action, table_name, 
                record_id, old_values, new_values, action_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId, $userName, $action, $tableName, 
            $recordId, $oldValues, $newValues
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

function logExpenseActivity($pdo, $action, $expenseId, $expenseData = null, $oldData = null) {
    if (!isLoggedIn()) return false;
    
    return logActivity(
        $pdo, 
        $_SESSION['user_id'], 
        $_SESSION['full_name'], 
        $action, 
        'expenses', 
        $expenseId, 
        $oldData, 
        $expenseData
    );
}

function logUserActivity($pdo, $action, $targetUserId, $userData = null, $oldData = null) {
    if (!isLoggedIn()) return false;
    
    return logActivity(
        $pdo, 
        $_SESSION['user_id'], 
        $_SESSION['full_name'], 
        $action, 
        'users', 
        $targetUserId, 
        $oldData, 
        $userData
    );
}

function logPasswordChange($pdo, $userId, $userName, $additionalData = []) {
    if (!isLoggedIn()) return false;
    
    $passwordChangeData = array_merge([
        'action_type' => 'password_change',
        'user_id' => $userId,
        'username' => $_SESSION['username'],
        'full_name' => $userName,
        'change_time' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ], $additionalData);
    
    return logActivity(
        $pdo, 
        $userId, 
        $userName, 
        'PASSWORD_CHANGE', 
        'users', 
        $userId, 
        null, 
        $passwordChangeData
    );
}

function getRecentActivities($pdo, $limit = 50, $userId = null) {
    try {
        $sql = "SELECT al.*, u.username 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id";
        
        if ($userId) {
            $sql .= " WHERE al.user_id = :user_id";
        }
        
        $sql .= " ORDER BY al.action_time DESC LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to get activities: " . $e->getMessage());
        return [];
    }
}

function getActivitiesByDateRange($pdo, $startDate, $endDate, $userId = null) {
    try {
        $sql = "SELECT al.*, u.username 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE DATE(al.action_time) BETWEEN :start_date AND :end_date";
        
        if ($userId) {
            $sql .= " AND al.user_id = :user_id";
        }
        
        $sql .= " ORDER BY al.action_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to get activities by date: " . $e->getMessage());
        return [];
    }
}

function getPasswordChangeHistory($pdo, $userId = null, $limit = 20) {
    try {
        $sql = "SELECT al.*, u.username 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE al.action = 'PASSWORD_CHANGE'";
        
        if ($userId) {
            $sql .= " AND al.user_id = :user_id";
        }
        
        $sql .= " ORDER BY al.action_time DESC LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to get password change history: " . $e->getMessage());
        return [];
    }
}

function formatActivityDisplay($activity) {
    $actionColor = [
        'LOGIN' => 'success',
        'LOGOUT' => 'info',
        'CREATE' => 'primary',
        'UPDATE' => 'warning',
        'DELETE' => 'danger',
        'PASSWORD_CHANGE' => 'warning'
    ];
    
    $color = $actionColor[$activity['action']] ?? 'secondary';
    
    return [
        'color' => $color,
        'message' => generateActivityMessage($activity),
        'time' => date('M j, Y g:i A', strtotime($activity['action_time'])),
        'details' => getActivityDetails($activity)
    ];
}

function generateActivityMessage($activity) {
    $action = strtolower($activity['action']);
    $table = $activity['table_name'];
    $userName = $activity['user_name'];
    
    switch ($activity['action']) {
        case 'LOGIN':
            return "{$userName} logged into the system";
        case 'LOGOUT':
            return "{$userName} logged out of the system";
        case 'CREATE':
            if ($table === 'expenses') {
                return "{$userName} created a new expense record (ID: {$activity['record_id']})";
            } elseif ($table === 'users') {
                return "{$userName} created a new user account (ID: {$activity['record_id']})";
            }
            return "{$userName} created a new {$table} record (ID: {$activity['record_id']})";
        case 'UPDATE':
            if ($table === 'expenses') {
                return "{$userName} updated expense record (ID: {$activity['record_id']})";
            } elseif ($table === 'users') {
                return "{$userName} updated user profile (ID: {$activity['record_id']})";
            }
            return "{$userName} updated {$table} record (ID: {$activity['record_id']})";
        case 'DELETE':
            if ($table === 'expenses') {
                return "{$userName} deleted expense record (ID: {$activity['record_id']})";
            } elseif ($table === 'users') {
                return "{$userName} deleted user account (ID: {$activity['record_id']})";
            }
            return "{$userName} deleted {$table} record (ID: {$activity['record_id']})";
        case 'PASSWORD_CHANGE':
            return "{$userName} changed their password";
        default:
            return "{$userName} performed {$action} on {$table} (ID: {$activity['record_id']})";
    }
}

function getActivityDetails($activity) {
    $details = [];
    
    if ($activity['new_values']) {
        $newValues = json_decode($activity['new_values'], true);
        
        if ($newValues) {
            switch ($activity['action']) {
                case 'LOGIN':
                case 'LOGOUT':
                    if (isset($newValues['ip_address'])) {
                        $details[] = "IP: " . $newValues['ip_address'];
                    }
                    break;
                    
                case 'PASSWORD_CHANGE':
                    if (isset($newValues['ip_address'])) {
                        $details[] = "IP: " . $newValues['ip_address'];
                    }
                    if (isset($newValues['change_time'])) {
                        $details[] = "Time: " . date('M j, Y g:i A', strtotime($newValues['change_time']));
                    }
                    break;
                    
                case 'CREATE':
                case 'UPDATE':
                    if ($activity['table_name'] === 'expenses' && isset($newValues['amount'])) {
                        $details[] = "Amount: ₱" . number_format($newValues['amount'], 2);
                    }
                    break;
            }
        }
    }
    
    return $details;
}

function getActivityIcon($action) {
    $icons = [
        'LOGIN' => 'bx-log-in',
        'LOGOUT' => 'bx-log-out',
        'CREATE' => 'bx-plus-circle',
        'UPDATE' => 'bx-edit',
        'DELETE' => 'bx-trash',
        'PASSWORD_CHANGE' => 'bx-key'
    ];
    
    return $icons[$action] ?? 'bx-info-circle';
}

function isSecuritySensitiveAction($action) {
    $sensitiveActions = ['LOGIN', 'LOGOUT', 'PASSWORD_CHANGE', 'DELETE'];
    return in_array($action, $sensitiveActions);
}

function getSecurityAlerts($pdo, $userId = null, $hours = 24) {
    try {
        $sql = "SELECT al.*, u.username 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE al.action_time >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                AND al.action IN ('LOGIN', 'PASSWORD_CHANGE', 'DELETE')";
        
        if ($userId) {
            $sql .= " AND al.user_id = :user_id";
        }
        
        $sql .= " ORDER BY al.action_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
        
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to get security alerts: " . $e->getMessage());
        return [];
    }
}

function cleanupOldLogs($pdo, $daysToKeep = 90) {
    try {
        $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE action_time < DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->bindValue(':days', $daysToKeep, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Failed to cleanup old logs: " . $e->getMessage());
        return false;
    }
}

function getActivityStats($pdo, $userId = null, $days = 30) {
    try {
        $sql = "SELECT 
                    action,
                    COUNT(*) as count,
                    DATE(action_time) as activity_date
                FROM activity_logs 
                WHERE action_time >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        if ($userId) {
            $sql .= " AND user_id = :user_id";
        }
        
        $sql .= " GROUP BY action, DATE(action_time) ORDER BY activity_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to get activity stats: " . $e->getMessage());
        return [];
    }
}
?>