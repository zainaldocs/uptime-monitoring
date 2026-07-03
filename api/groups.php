<?php
/**
 * Groups Management API
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
    
    if ($method === 'GET') {
        // Fetch all groups
        $stmt = $pdo->query("SELECT * FROM `groups` ORDER BY group_name ASC");
        $groups = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $groups]);
        exit();
        
    } elseif ($method === 'POST') {
        if ($action === 'create') {
            $group_name = trim($_POST['group_name'] ?? '');
            
            if (empty($group_name)) {
                echo json_encode(['success' => false, 'error' => 'Nama grup tidak boleh kosong.']);
                exit();
            }
            if (mb_strlen($group_name) > 100) {
                echo json_encode(['success' => false, 'error' => 'Nama grup tidak boleh melebihi 100 karakter.']);
                exit();
            }
            
            // Check duplicate
            $chk = $pdo->prepare("SELECT id FROM `groups` WHERE group_name = ?");
            $chk->execute([$group_name]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Nama grup sudah terdaftar.']);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO `groups` (group_name) VALUES (?)");
            $stmt->execute([$group_name]);
            
            echo json_encode(['success' => true, 'message' => 'Grup berhasil ditambahkan.', 'id' => $pdo->lastInsertId()]);
            exit();
            
        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $group_name = trim($_POST['group_name'] ?? '');
            
            if ($id <= 0 || empty($group_name)) {
                echo json_encode(['success' => false, 'error' => 'ID grup atau nama grup tidak valid.']);
                exit();
            }
            if (mb_strlen($group_name) > 100) {
                echo json_encode(['success' => false, 'error' => 'Nama grup tidak boleh melebihi 100 karakter.']);
                exit();
            }
            
            // Check duplicate excluding self
            $chk = $pdo->prepare("SELECT id FROM `groups` WHERE group_name = ? AND id != ?");
            $chk->execute([$group_name, $id]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Nama grup sudah digunakan oleh grup lain.']);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE `groups` SET group_name = ? WHERE id = ?");
            $stmt->execute([$group_name, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Grup berhasil diperbarui.']);
            exit();
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID grup tidak valid.']);
                exit();
            }
            
            // Note: Foreign key is ON DELETE SET NULL, so devices group_id will automatically become NULL.
            $stmt = $pdo->prepare("DELETE FROM `groups` WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Grup berhasil dihapus.']);
            exit();
        }
    }
    
    // Fallback for unhandled action/method
    echo json_encode(['success' => false, 'error' => 'Aksi atau metode HTTP tidak valid.']);
    
} catch (PDOException $e) {
    error_log("Database error in api/groups.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan basis data internal pada sistem.']);
}
