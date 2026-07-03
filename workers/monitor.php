<?php
/**
 * Uptime Monitoring Background Worker
 * Runs every 1 minute via Cron.
 */

// Allow execution only from CLI
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden: Access allowed only from Command Line Interface.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/checker.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting Uptime Monitoring probe loop...\n";

try {
    $pdo = getDBConnection();
    
    // Fetch all active devices
    $stmt = $pdo->query("SELECT * FROM devices");
    $devices = $stmt->fetchAll();
    
    if (empty($devices)) {
        echo "No devices registered for monitoring.\n";
        exit();
    }
    
    $updateStmt = $pdo->prepare("UPDATE devices SET status = ?, last_status_change = NOW() WHERE id = ?");
    $logStmt = $pdo->prepare("INSERT INTO status_logs (device_id, status, latency) VALUES (?, ?, ?)");
    
    foreach ($devices as $device) {
        $deviceId = $device['id'];
        $deviceName = $device['name'];
        $oldStatus = $device['status'];
        
        echo "Checking '$deviceName' ({$device['ip_address']})... ";
        
        // Execute network check
        $result = probeDevice($device['ip_address'], $device['port'], $device['check_type']);
        $newStatus = $result['status'];
        $latency = $result['latency'];
        
        echo "Status: $newStatus | Latency: " . ($latency !== null ? "{$latency}ms" : "N/A") . "\n";
        
        // Always write to status_logs
        $logStmt->execute([$deviceId, $newStatus, $latency]);
        
        // Check for status change
        if ($oldStatus !== $newStatus) {
            echo ">> Status changed from $oldStatus to $newStatus for '$deviceName'!\n";
            $updateStmt->execute([$newStatus, $deviceId]);
            
            // Trigger instant email alert on UP -> DOWN transition
            if ($oldStatus === 'UP' && $newStatus === 'DOWN') {
                $mailerPath = __DIR__ . '/../includes/mailer.php';
                if (file_exists($mailerPath)) {
                    require_once $mailerPath;
                    if (function_exists('sendAlertEmail')) {
                        echo "Triggering SMTP Alert for '$deviceName'...\n";
                        sendAlertEmail($device, $latency);
                    }
                }
            }
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Probe loop completed successfully.\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
