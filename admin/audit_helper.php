<?php
if (!function_exists('logAudit')) {
    function logAudit($pdo, $action, $entityType, $entityId = null, $oldValue = null, $newValue = null, $userId = null, $ip = null) {
        if (!in_array($action, ['create','update','delete','login','export','bulk_delete','status_change','password_reset','permission_change','login_failed'])) {
            $action = 'update';
        }
        $userId = $userId ?? ($_SESSION['admin_id'] ?? null);
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $oldJson = $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null;
        $newJson = $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null;

        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $action, $entityType, $entityId, $oldJson, $newJson, $ip]);
        } catch (Exception $e) {}
    }
}

if (!function_exists('logAuditBatch')) {
    function logAuditBatch($pdo, $action, $entityType, $ids, $ip = null) {
        $userId = $_SESSION['admin_id'] ?? null;
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        foreach ($ids as $id) {
            logAudit($pdo, $action, $entityType, $id, null, ['batch_action' => true], $userId, $ip);
        }
    }
}

if (!function_exists('captureOldValues')) {
    function captureOldValues($pdo, $table, $id, $columns = null) {
        try {
            $sql = "SELECT * FROM $table WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $columns) {
                return array_intersect_key($row, array_flip($columns));
            }
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('logLogin')) {
    function logLogin($pdo, $userId, $success = true, $ip = null) {
        $action = $success ? 'login' : 'login_failed';
        logAudit($pdo, $action, 'auth', $userId, null, [
            'success' => $success,
            'timestamp' => date('Y-m-d H:i:s')
        ], $userId, $ip);
    }
}
