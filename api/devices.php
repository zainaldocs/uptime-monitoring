<?php
/**
 * Devices Management API
 * Accessible only by Admin.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/checker.php';

// Secure endpoint: only allow Admins
startSessionIfNeeded();
requireAdmin();
validateCsrfToken(); // Validate CSRF on POST requests

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    if ($method === 'GET') {
        // Fetch all devices along with their group names
        $query = "SELECT d.*, g.group_name 
                  FROM devices d 
                  LEFT JOIN `groups` g ON d.group_id = g.id 
                  ORDER BY d.name ASC";
        $stmt = $pdo->query($query);
        $devices = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $devices]);
        exit();
        
    } elseif ($method === 'POST') {
        if ($action === 'create' || $action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $group_id = $_POST['group_id'] === '' ? null : intval($_POST['group_id']);
            $ip_address = trim($_POST['ip_address'] ?? '');
            $port = $_POST['port'] === '' ? null : intval($_POST['port']);
            $check_type = trim($_POST['check_type'] ?? 'ping');
            
            // Validations
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Nama perangkat tidak boleh kosong.']);
                exit();
            }
            if (mb_strlen($name) > 100) {
                echo json_encode(['success' => false, 'error' => 'Nama perangkat tidak boleh melebihi 100 karakter.']);
                exit();
            }
            if (empty($ip_address)) {
                echo json_encode(['success' => false, 'error' => 'IP Address/Host tidak boleh kosong.']);
                exit();
            }
            if (mb_strlen($ip_address) > 255) {
                echo json_encode(['success' => false, 'error' => 'Alamat IP/Host terlalu panjang.']);
                exit();
            }
            
            // Validate Host / IP Address format (allows single-label hostnames on local networks)
            $testHost = $ip_address;
            if (preg_match('/^https?:\/\//i', $ip_address)) {
                $parsedUrl = parse_url($ip_address);
                $testHost = $parsedUrl['host'] ?? '';
            }
            if (empty($testHost) || !preg_match('/^[a-zA-Z0-9.\-]+$/', $testHost)) {
                echo json_encode(['success' => false, 'error' => 'Format IP Address/Hostname tidak valid.']);
                exit();
            }
            if ($port !== null && ($port < 1 || $port > 65535)) {
                echo json_encode(['success' => false, 'error' => 'Port harus bernilai 1 - 65535.']);
                exit();
            }
            if (!in_array($check_type, ['ping', 'tcp', 'http'])) {
                echo json_encode(['success' => false, 'error' => 'Tipe cek tidak valid.']);
                exit();
            }
            if ($check_type === 'tcp' && $port === null) {
                echo json_encode(['success' => false, 'error' => 'Port wajib diisi jika tipe cek adalah TCP.']);
                exit();
            }
            
            // Verify group_id if not null
            if ($group_id !== null) {
                $chkGroup = $pdo->prepare("SELECT id FROM `groups` WHERE id = ?");
                $chkGroup->execute([$group_id]);
                if (!$chkGroup->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Grup yang dipilih tidak valid.']);
                    exit();
                }
            }
            
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO devices (group_id, name, ip_address, port, check_type, status, last_status_change) VALUES (?, ?, ?, ?, ?, 'UNKNOWN', NOW())");
                $stmt->execute([$group_id, $name, $ip_address, $port, $check_type]);
                $targetDeviceId = $pdo->lastInsertId();
            } else {
                if ($id <= 0) {
                    echo json_encode(['success' => false, 'error' => 'ID perangkat tidak valid.']);
                    exit();
                }
                
                $stmt = $pdo->prepare("UPDATE devices SET group_id = ?, name = ?, ip_address = ?, port = ?, check_type = ? WHERE id = ?");
                $stmt->execute([$group_id, $name, $ip_address, $port, $check_type, $id]);
                $targetDeviceId = $id;
            }
            
            // Run instant probe to avoid N/A status
            $probeResult = probeDevice($ip_address, $port, $check_type);
            $initialStatus = $probeResult['status'];
            $initialLatency = $probeResult['latency'];
            
            // Update device status with checked status
            $updateStatusStmt = $pdo->prepare("UPDATE devices SET status = ?, last_status_change = NOW() WHERE id = ?");
            $updateStatusStmt->execute([$initialStatus, $targetDeviceId]);
            
            // Insert initial status log
            $logStmt = $pdo->prepare("INSERT INTO status_logs (device_id, status, latency) VALUES (?, ?, ?)");
            $logStmt->execute([$targetDeviceId, $initialStatus, $initialLatency]);
            
            $msg = $action === 'create' ? 'Perangkat berhasil ditambahkan.' : 'Perangkat berhasil diperbarui.';
            echo json_encode(['success' => true, 'message' => $msg]);
            exit();
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID perangkat tidak valid.']);
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Perangkat berhasil dihapus.']);
            exit();
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Aksi atau metode HTTP tidak valid.']);
    
} catch (PDOException $e) {
    error_log("Database error in api/devices.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan basis data internal pada sistem.']);
}
