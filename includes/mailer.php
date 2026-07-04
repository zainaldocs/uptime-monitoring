<?php
/**
 * PHPMailer Wrapper for SMTP alerts and reports
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends a generic HTML email using SMTP configuration stored in system_settings.
 * 
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $htmlBody HTML content of the email.
 * @return bool True if sent successfully, false otherwise.
 */
function sendEmail(string $to, string $subject, string $htmlBody): bool {
    try {
        $pdo = getDBConnection();
        
        // Fetch all SMTP settings
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'] ?? 'smtp.example.com';
        $mail->SMTPAuth   = ($settings['smtp_auth'] ?? 'true') === 'true';
        $mail->Username   = $settings['smtp_user'] ?? '';
        $mail->Password   = $settings['smtp_pass'] ?? '';
        
        // Set Port & Encryption based on port
        $port = intval($settings['smtp_port'] ?? 587);
        $mail->Port = $port;
        
        if ($port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($port === 587 || $port === 25) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = '';
        }
        
        // SSL certificate verification configuration (disabled in local, enabled in production)
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
        
        // Recipients
        $mail->setFrom($settings['smtp_user'] ?? 'no-reply@example.com', 'UMS Monitoring');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(html_entity_decode($htmlBody));
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    } catch (PDOException $e) {
        error_log("Database Error in Mailer: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends an instant alert email when a device goes DOWN.
 * 
 * @param array $device The device row data from db.
 * @param float|null $latency The latency recorded when it went down.
 * @return bool
 */
function sendAlertEmail(array $device, ?float $latency): bool {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'alert_target_email'");
        $stmt->execute();
        $targetEmail = $stmt->fetchColumn();
        
        if (!$targetEmail) {
            error_log("No alert target email configured. Alert skipped.");
            return false;
        }
        
        $deviceName = htmlspecialchars($device['name']);
        $deviceIp = htmlspecialchars($device['ip_address']);
        $devicePort = $device['port'] ? ":" . $device['port'] : "";
        $checkType = strtoupper($device['check_type']);
        $timestamp = date('Y-m-d H:i:s');
        
        $subject = "🚨 [ALERT UMS] Perangkat DOWN: $deviceName";
        
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #f3f4f6; border-radius: 12px; background-color: #fcfcfc;'>
            <div style='background-color: #ef4444; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0; text-align: center;'>
                <h2 style='margin: 0; font-size: 20px;'>🚨 Peringatan: Perangkat Tidak Aktif!</h2>
            </div>
            <div style='padding: 20px; color: #374151;'>
                <p>Sistem mendeteksi bahwa salah satu perangkat pemantauan Anda saat ini dalam status <b>DOWN</b>.</p>
                <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold; width: 40%;'>Nama Perangkat</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>$deviceName</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold;'>IP / Host</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-family: monospace; color: #111827;'>$deviceIp$devicePort</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold;'>Tipe Cek</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>$checkType</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold;'>Waktu Kejadian</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>$timestamp</td>
                    </tr>
                </table>
                <p style='margin-top: 20px; text-align: center;'>
                    <a href='" . APP_URL . "views/dashboard.php' style='display: inline-block; padding: 10px 20px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Buka Dashboard UMS</a>
                </p>
            </div>
            <div style='border-top: 1px solid #f3f4f6; padding-top: 15px; text-align: center; font-size: 11px; color: #9ca3af;'>
                Pesan otomatis dikirim oleh Uptime Monitoring System (UMS) Pro.
            </div>
        </div>
        ";
        
        return sendEmail($targetEmail, $subject, $htmlBody);
        
    } catch (PDOException $e) {
        error_log("Database error triggering alert email: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends a recovery email when a device goes back UP.
 * 
 * @param array $device The device row data from db.
 * @param float|null $latency The latency recorded when it recovered.
 * @return bool
 */
function sendRecoveryEmail(array $device, ?float $latency): bool {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'alert_target_email'");
        $stmt->execute();
        $targetEmail = $stmt->fetchColumn();
        
        if (!$targetEmail) {
            error_log("No alert target email configured. Recovery alert skipped.");
            return false;
        }
        
        $deviceName = htmlspecialchars($device['name']);
        $deviceIp = htmlspecialchars($device['ip_address']);
        $devicePort = $device['port'] ? ":" . $device['port'] : "";
        $checkType = strtoupper($device['check_type']);
        $timestamp = date('Y-m-d H:i:s');
        $latencyText = $latency !== null ? "{$latency} ms" : "N/A";
        
        $subject = "✅ [RECOVERY UMS] Perangkat UP: $deviceName";
        
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #f3f4f6; border-radius: 12px; background-color: #fcfcfc;'>
            <div style='background-color: #10b981; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0; text-align: center;'>
                <h2 style='margin: 0; font-size: 20px;'>✅ Pulih: Perangkat Kembali Online!</h2>
            </div>
            <div style='padding: 20px; color: #374151;'>
                <p>Sistem mendeteksi bahwa perangkat pemantauan Anda kini telah kembali aktif dan berstatus <b>UP</b>.</p>
                <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold; width: 40%;'>Nama Perangkat</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>$deviceName</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold;'>IP / Host</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-family: monospace; color: #111827;'>$deviceIp$devicePort</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold;'>Tipe Cek</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>$checkType</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold;'>Rata Latency</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>$latencyText</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: bold;'>Waktu Kejadian</td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>$timestamp</td>
                    </tr>
                </table>
                <p style='margin-top: 20px; text-align: center;'>
                    <a href='" . APP_URL . "views/dashboard.php' style='display: inline-block; padding: 10px 20px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Buka Dashboard UMS</a>
                </p>
            </div>
            <div style='border-top: 1px solid #f3f4f6; padding-top: 15px; text-align: center; font-size: 11px; color: #9ca3af;'>
                Pesan otomatis dikirim oleh Uptime Monitoring System (UMS) Pro.
            </div>
        </div>
        ";
        
        return sendEmail($targetEmail, $subject, $htmlBody);
        
    } catch (PDOException $e) {
        error_log("Database error triggering recovery email: " . $e->getMessage());
        return false;
    }
}
