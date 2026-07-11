<?php
// api/staff/list.php
require_once '../api_helper.php';
apiRequireAdmin();

// Get active staff
$stmt = $pdo->query("SELECT id, name, username, email, phone, status, created_at FROM users WHERE role = 'staff' AND status = 'active' ORDER BY created_at DESC");
$active_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending staff
$stmt = $pdo->query("SELECT id, name, username, email, phone, status, created_at FROM users WHERE role = 'staff' AND status = 'pending' ORDER BY created_at DESC");
$pending_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendJSON([
    'activeStaff' => $active_staff,
    'pendingStaff' => $pending_staff
]);
?>
