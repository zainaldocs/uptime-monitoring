<?php
/**
 * Live Status Monitoring Endpoint (JSON)
 * Accessible by logged-in users (Admin & Staff).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

startSessionIfNeeded();

// Restrict access to logged-in users
if (!isLoggedIn()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

/**
 * Format timestamp into human readable duration text.
 */
function formatDuration(?string $timestamp): string {
    if (empty($timestamp)) {
        return 'N/A';
    }
    
    $seconds = time() - strtotime($timestamp);
    if ($seconds < 0) {
        $seconds = 0;
    }
    
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($days > 0) {
        return "{$days} hari {$hours} jam";
    } elseif ($hours > 0) {
        return "{$hours} jam {$minutes} mnt";
    } elseif ($minutes > 0) {
        return "{$minutes} mnt {$secs} dtk";
    } else {
        return "{$secs} dtk";
    }
}

try {
    $pdo = getDBConnection();
    
    // 1. Fetch all groups
    $groupsStmt = $pdo->query("SELECT * FROM `groups` ORDER BY group_name ASC");
    $groups = $groupsStmt->fetchAll();
    
    // 2. Fetch all devices with their latest latency from status_logs
    $deviceQuery = "
        SELECT d.*, 
               (SELECT latency FROM status_logs WHERE device_id = d.id ORDER BY timestamp DESC LIMIT 1) as last_latency
        FROM devices d
        ORDER BY d.name ASC
    ";
    $devicesStmt = $pdo->query($deviceQuery);
    $devices = $devicesStmt->fetchAll();
    
    // Map devices by their group ID
    $groupedDevices = [];
    $unassignedDevices = [];
    
    foreach ($devices as $device) {
        $deviceData = [
            'id' => $device['id'],
            'name' => $device['name'],
            'ip_address' => $device['ip_address'],
            'port' => $device['port'],
            'check_type' => $device['check_type'],
            'status' => $device['status'],
            'last_status_change' => $device['last_status_change'],
            'duration_text' => formatDuration($device['last_status_change']),
            'latency' => $device['last_latency'] !== null ? round($device['last_latency'], 1) : null
        ];
        
        if ($device['group_id'] === null) {
            $unassignedDevices[] = $deviceData;
        } else {
            $groupedDevices[$device['group_id']][] = $deviceData;
        }
    }
    
    // 3. Construct hierarchical structure
    $responseGroups = [];
    
    foreach ($groups as $group) {
        $responseGroups[] = [
            'id' => $group['id'],
            'group_name' => $group['group_name'],
            'devices' => $groupedDevices[$group['id']] ?? []
        ];
    }
    
    // Append virtual group for unassigned devices if any exist
    if (!empty($unassignedDevices)) {
        $responseGroups[] = [
            'id' => null,
            'group_name' => 'Tanpa Grup',
            'devices' => $unassignedDevices
        ];
    }
    
    echo json_encode([
        'success' => true,
        'groups' => $responseGroups,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in api/status_live.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => 'Terjadi kesalahan basis data internal pada sistem.'
    ]);
}
