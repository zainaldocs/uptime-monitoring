<?php
/**
 * System Settings and User Management API
 * Accessible only by Admin.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

// Secure endpoint: only allow Admins
startSessionIfNeeded();
requireAdmin();
validateCsrfToken(); // Validate CSRF on POST requests

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    if ($method === 'POST') {
        if ($action === 'update_smtp') {
            $smtp_host = trim($_POST['smtp_host'] ?? '');
            $smtp_port = trim($_POST['smtp_port'] ?? '');
            $smtp_auth = trim($_POST['smtp_auth'] ?? 'true');
            $smtp_user = trim($_POST['smtp_user'] ?? '');
            $smtp_pass = trim($_POST['smtp_pass'] ?? '');
            $alert_target_email = trim($_POST['alert_target_email'] ?? '');
            
            // Basic validations
            if (empty($smtp_host) || empty($smtp_port) || empty($alert_target_email)) {
                echo json_encode(['success' => false, 'error' => 'Host, Port, dan Email Target wajib diisi.']);
                exit();
            }
            if (!filter_var($alert_target_email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Format Email Target tidak valid.']);
                exit();
            }
            
            // Save or Update settings
            $settings = [
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_auth' => $smtp_auth,
                'smtp_user' => $smtp_user,
                'alert_target_email' => $alert_target_email
            ];
            
            // Only update password if not empty (prevent overwriting with blank)
            if (!empty($smtp_pass)) {
                $settings['smtp_pass'] = $smtp_pass;
            }
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            
            foreach ($settings as $key => $val) {
                $stmt->execute([$key, $val, $val]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Konfigurasi SMTP berhasil diperbarui.']);
            exit();
            
        } elseif ($action === 'test_smtp') {
            $smtp_host = trim($_POST['smtp_host'] ?? '');
            $smtp_port = trim($_POST['smtp_port'] ?? '');
            $smtp_auth = trim($_POST['smtp_auth'] ?? 'true');
            $smtp_user = trim($_POST['smtp_user'] ?? '');
            $smtp_pass = trim($_POST['smtp_pass'] ?? '');
            $test_email = trim($_POST['test_email'] ?? '');
            
            if (empty($smtp_host) || empty($smtp_port) || empty($test_email)) {
                echo json_encode(['success' => false, 'error' => 'Host, Port, dan Email Penerima wajib diisi.']);
                exit();
            }
            if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Format Email Penerima tidak valid.']);
                exit();
            }
            
            // If password is empty (not changed in form), get it from DB
            if (empty($smtp_pass)) {
                $dbPassStmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'smtp_pass'");
                $dbPassStmt->execute();
                $smtp_pass = $dbPassStmt->fetchColumn() ?: '';
            }
            
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = $smtp_auth === 'true';
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                
                $port = intval($smtp_port);
                $mail->Port = $port;
                
                if ($port === 465) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($port === 587 || $port === 25) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPAutoTLS = false;
                    $mail->SMTPSecure = '';
                }
                
                $secureVerify = getenv('SMTP_SECURE_VERIFY') !== 'false';
                if (!$secureVerify) {
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                }
                
                $mail->setFrom($smtp_user ?: 'no-reply@example.com', 'UMS Test Mailer');
                $mail->addAddress($test_email);
                
                $mail->isHTML(true);
                $mail->Subject = "📩 [TEST] Uji Coba Koneksi SMTP UMS";
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;'>
                        <h2 style='color: #10b981; margin-top: 0;'>Koneksi SMTP Berhasil!</h2>
                        <p>Email ini dikirim untuk menguji konfigurasi server SMTP pada aplikasi Uptime Monitoring System (UMS) Anda.</p>
                        <p>Detail server yang diuji:</p>
                        <ul>
                            <li><b>SMTP Host:</b> $smtp_host</li>
                            <li><b>SMTP Port:</b> $smtp_port</li>
                            <li><b>SMTP User:</b> $smtp_user</li>
                        </ul>
                        <p style='font-size: 12px; color: #9ca3af;'>Dikirim pada: " . date('Y-m-d H:i:s') . "</p>
                    </div>
                ";
                
                $mail->send();
                echo json_encode(['success' => true, 'message' => 'Email uji coba berhasil dikirim ke ' . $test_email]);
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Gagal mengirim email: ' . $mail->ErrorInfo]);
            }
            exit();
            
        } elseif ($action === 'create_user') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? 'Staff');
            
            // Validations
            if (empty($username) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Harap isi semua kolom wajib untuk user baru.']);
                exit();
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Format email tidak valid.']);
                exit();
            }
            if (strlen($password) < 8) {
                echo json_encode(['success' => false, 'error' => 'Password minimal harus 8 karakter.']);
                exit();
            }
            if (strlen($password) > 72) {
                echo json_encode(['success' => false, 'error' => 'Password terlalu panjang (maksimal 72 karakter).']);
                exit();
            }
            if (!in_array($role, ['Admin', 'Staff'])) {
                echo json_encode(['success' => false, 'error' => 'Role tidak valid.']);
                exit();
            }
            
            // Check duplicates
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Username sudah terdaftar.']);
                exit();
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $passwordHash, $role, $email]);
            
            echo json_encode(['success' => true, 'message' => 'User baru berhasil dibuat.']);
            exit();
            
        } elseif ($action === 'delete_user') {
            $id = intval($_POST['id'] ?? 0);
            $currentUserId = intval($_SESSION['user_id']);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID User tidak valid.']);
                exit();
            }
            if ($id === $currentUserId) {
                echo json_encode(['success' => false, 'error' => 'Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif.']);
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'User berhasil dihapus.']);
            exit();
            
        } elseif ($action === 'update_theme') {
            $theme = trim($_POST['theme'] ?? 'dark');
            if (!in_array($theme, ['dark', 'light'])) {
                echo json_encode(['success' => false, 'error' => 'Tema tidak valid.']);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('theme_mode', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$theme, $theme]);
            
            echo json_encode(['success' => true, 'message' => 'Tema default sistem berhasil disimpan.']);
            exit();
        }
    }
    
    // GET requests for listing users (since AJAX will render it inside settings UI)
    if ($method === 'GET' && $action === 'list_users') {
        $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY username ASC");
        $users = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $users]);
        exit();
    }
    
    echo json_encode(['success' => false, 'error' => 'Aksi atau metode HTTP tidak valid.']);
    
} catch (PDOException $e) {
    error_log("Database error in api/settings.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan basis data internal pada sistem.']);
}
