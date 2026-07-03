<?php
/**
 * Authentication Action Handler (API/Router Endpoint)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

startSessionIfNeeded();

$action = $_GET['action'] ?? '';
$baseUrl = getBaseUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // CSRF verification
    if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        header("Location: " . $baseUrl . "views/login.php?error=csrf");
        exit();
    }
    
    if (empty($username) || empty($password)) {
        header("Location: " . $baseUrl . "views/login.php?error=required");
        exit();
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $pdo = getDBConnection();
        
        // Rate Limiting: Max 5 failed attempts in 15 minutes
        $limitStmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= NOW() - INTERVAL 15 MINUTE");
        $limitStmt->execute([$ip]);
        $failedCount = intval($limitStmt->fetchColumn() ?? 0);
        
        if ($failedCount >= 5) {
            header("Location: " . $baseUrl . "views/login.php?error=locked");
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID for security (prevent session fixation)
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Clear failed attempts for this IP on success
            $clearAttemptsStmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $clearAttemptsStmt->execute([$ip]);
            
            // Redirect to dashboard
            header("Location: " . $baseUrl . "views/dashboard.php");
            exit();
        } else {
            // Track failed login attempt
            $logAttemptStmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
            $logAttemptStmt->execute([$ip, $username]);
            
            // Invalid credentials
            header("Location: " . $baseUrl . "views/login.php?error=invalid");
            exit();
        }
    } catch (PDOException $e) {
        // Log error and redirect with generic error
        error_log("Login database error: " . $e->getMessage());
        header("Location: " . $baseUrl . "views/login.php?error=invalid");
        exit();
    }
} elseif ($action === 'logout') {
    // Unset all session values
    $_SESSION = [];
    
    // Destroy session cookie if set
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login page with logout flag
    header("Location: " . $baseUrl . "views/login.php?logout=success");
    exit();
} else {
    // Unknown actions redirect to index
    header("Location: " . $baseUrl . "index.php");
    exit();
}
