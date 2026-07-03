<?php
/**
 * Authentication and RBAC Guard Helpers
 */

/**
 * Dynamically determines the base URL path of the application.
 * Handles subdirectories (like /uptime-monitoring/) automatically.
 * 
 * @return string
 */
function getBaseUrl(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($scriptName);
    $dir = str_replace('\\', '/', $dir);
    
    // Strip trailing folder names to get the root directory path
    $dir = preg_replace('/\/(views|api|workers|includes|config)$/', '', $dir);
    
    return rtrim($dir, '/') . '/';
}

/**
 * Starts the session safely if not already started.
 */
function startSessionIfNeeded(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session cookie settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Use secure cookies if HTTPS is enabled
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
    }
    
    // Generate CSRF token if not set
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Validates the CSRF token on POST requests.
 * Accessible from API or Page endpoints.
 */
function validateCsrfToken(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        // Support JSON input as well
        if (empty($token)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['csrf_token'] ?? '';
        }
        
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'error' => 'Validasi CSRF gagal. Silakan muat ulang halaman.']);
            exit();
        }
    }
}

/**
 * Checks if the user is currently logged in.
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    startSessionIfNeeded();
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user is an Admin.
 * 
 * @return bool
 */
function isAdmin(): bool {
    startSessionIfNeeded();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

/**
 * Guard that redirects to login page if user is not authenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: " . getBaseUrl() . "views/login.php");
        exit();
    }
}

/**
 * Guard that restricts access to Admin role only.
 * Returns 403 Forbidden or redirects appropriately.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        // For API requests, return JSON
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['error' => 'Forbidden: Admin access required.']);
            exit();
        }
        
        // For View pages, show 403 error page or alert
        header('HTTP/1.1 403 Forbidden');
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>403 Forbidden</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-900 text-white flex items-center justify-center h-screen'>
            <div class='text-center p-8 bg-gray-800 rounded-lg shadow-xl border border-gray-700 max-w-md'>
                <h1 class='text-6xl font-bold text-red-500 mb-4'>403</h1>
                <h2 class='text-2xl font-semibold mb-2'>Akses Ditolak</h2>
                <p class='text-gray-400 mb-6'>Halaman ini hanya dapat diakses oleh Administrator.</p>
                <a href='" . getBaseUrl() . "views/dashboard.php' class='px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors'>Kembali ke Dashboard</a>
            </div>
        </body>
        </html>";
        exit();
    }
}
