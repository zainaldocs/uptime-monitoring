<?php
/**
 * Daily Uptime Report Background Worker
 * Runs once a day via Cron (e.g. at 08:00 AM).
 */

// Allow execution only from CLI
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden: Access allowed only from Command Line Interface.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting Automated Daily Report generator...\n";

try {
    $pdo = getDBConnection();
    
    // Check if daily report email trigger is enabled
    $stmtTrigger = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'email_trigger_daily_report'");
    $stmtTrigger->execute();
    $dailyReportTrigger = $stmtTrigger->fetchColumn();
    if ($dailyReportTrigger === '0') {
        echo "Daily report email notification is disabled in settings. Skipping report.\n";
        exit(0);
    }
    
    // Fetch target email
    $stmtEmail = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'alert_target_email'");
    $stmtEmail->execute();
    $targetEmail = $stmtEmail->fetchColumn();
    
    if (!$targetEmail) {
        echo "Error: No target email configured in settings. Skipping report.\n";
        exit(1);
    }
    
    // Fetch all devices
    $devicesStmt = $pdo->query("SELECT * FROM devices ORDER BY name ASC");
    $devices = $devicesStmt->fetchAll();
    
    if (empty($devices)) {
        echo "No devices registered to report.\n";
        exit();
    }
    
    $reportData = [];
    
    foreach ($devices as $device) {
        $deviceId = $device['id'];
        
        // Calculate uptime percentage and average latency for the past 24 hours
        $statsQuery = "
            SELECT 
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = 'UP' THEN 1 ELSE 0 END) as up_checks,
                AVG(latency) as avg_latency
            FROM status_logs 
            WHERE device_id = ? AND timestamp >= NOW() - INTERVAL 1 DAY
        ";
        
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute([$deviceId]);
        $stats = $statsStmt->fetch();
        
        $totalChecks = intval($stats['total_checks'] ?? 0);
        $upChecks = intval($stats['up_checks'] ?? 0);
        $avgLatency = $stats['avg_latency'];
        
        $uptimePct = $totalChecks > 0 ? ($upChecks / $totalChecks) * 100 : 100;
        
        $reportData[] = [
            'name' => $device['name'],
            'ip_address' => $device['ip_address'],
            'port' => $device['port'],
            'check_type' => $device['check_type'],
            'status' => $device['status'],
            'uptime_pct' => round($uptimePct, 2),
            'avg_latency' => $avgLatency !== null ? round($avgLatency, 1) : null
        ];
    }
    
    // Build HTML template
    $timestamp = date('d F Y H:i');
    $subject = "📊 Laporan Performa Ketersediaan Harian - $timestamp";
    
    $tableRows = "";
    foreach ($reportData as $row) {
        $statusBadge = $row['status'] === 'UP' 
            ? "<span style='padding: 3px 8px; font-weight: bold; font-size: 11px; background-color: #d1fae5; color: #065f46; border-radius: 9999px;'>UP</span>" 
            : "<span style='padding: 3px 8px; font-weight: bold; font-size: 11px; background-color: #fee2e2; color: #991b1b; border-radius: 9999px;'>DOWN</span>";
            
        $portSuffix = $row['port'] ? ":{$row['port']}" : "";
        $latencyText = $row['avg_latency'] !== null ? "{$row['avg_latency']} ms" : "N/A";
        
        // Coloring for Uptime %
        $uptimeColor = "#10b981"; // green
        if ($row['uptime_pct'] < 95) $uptimeColor = "#ef4444"; // red
        elseif ($row['uptime_pct'] < 99) $uptimeColor = "#f59e0b"; // orange
        
        $tableRows .= "
        <tr style='border-bottom: 1px solid #e5e7eb;'>
            <td style='padding: 12px 10px; font-weight: bold; font-size: 13px;'>{$row['name']}</td>
            <td style='padding: 12px 10px; font-family: monospace; font-size: 12px; color: #4b5563;'>{$row['ip_address']}{$portSuffix}</td>
            <td style='padding: 12px 10px; font-size: 12px; text-transform: uppercase; color: #6b7280;'>{$row['check_type']}</td>
            <td style='padding: 12px 10px; font-weight: bold; font-size: 13px; color: $uptimeColor;'>{$row['uptime_pct']}%</td>
            <td style='padding: 12px 10px; font-size: 13px; color: #374151;'>$latencyText</td>
            <td style='padding: 12px 10px;'>$statusBadge</td>
        </tr>
        ";
    }
    
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 20px; border: 1px solid #f3f4f6; border-radius: 12px; background-color: #ffffff;'>
        <div style='background-color: #3b82f6; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;'>
            <h2 style='margin: 0; font-size: 22px;'>📊 Laporan Pemantauan Harian</h2>
            <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Ringkasan ketersediaan server & website dalam 24 jam terakhir</p>
        </div>
        
        <div style='padding: 20px;'>
            <p style='font-size: 13px; color: #9ca3af; margin-bottom: 20px;'>Dibuat pada: <b>$timestamp</b></p>
            
            <table style='width: 100%; border-collapse: collapse; text-align: left;'>
                <thead>
                    <tr style='background-color: #f9fafb; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-size: 12px;'>
                        <th style='padding: 10px; text-transform: uppercase;'>Nama</th>
                        <th style='padding: 10px; text-transform: uppercase;'>Alamat Host</th>
                        <th style='padding: 10px; text-transform: uppercase;'>Tipe Cek</th>
                        <th style='padding: 10px; text-transform: uppercase;'>Uptime (24j)</th>
                        <th style='padding: 10px; text-transform: uppercase;'>Rata Latency</th>
                        <th style='padding: 10px; text-transform: uppercase;'>Status</th>
                    </tr>
                </thead>
                <tbody>
                    $tableRows
                </tbody>
            </table>
            
            <p style='margin-top: 30px; text-align: center;'>
                <a href='" . APP_URL . "views/dashboard.php' style='display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;'>Buka Dashboard Sistem</a>
            </p>
        </div>
        
        <div style='border-top: 1px solid #f3f4f6; padding-top: 15px; text-align: center; font-size: 11px; color: #9ca3af;'>
            Pesan otomatis dikirim oleh Uptime Monitoring System (UMS) Pro.
        </div>
    </div>
    ";
    
    echo "Sending report to $targetEmail...\n";
    $success = sendEmail($targetEmail, $subject, $htmlBody);
    
    if ($success) {
        echo "Daily report sent successfully!\n";
    } else {
        echo "Failed to send daily report email.\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "Database Error in Daily Report: " . $e->getMessage() . "\n";
    exit(1);
}
