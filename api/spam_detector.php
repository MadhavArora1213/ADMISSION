<?php
if (!function_exists('checkBlacklist')) {
    function checkBlacklist($pdo, $ip = null, $email = null, $userId = null, $phone = null) {
        $now = date('Y-m-d H:i:s');
        $blocked = null;

        if ($ip) {
            $stmt = $pdo->prepare("SELECT id, reason FROM blacklisted_entities WHERE entity_type = 'ip' AND entity_value = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > ?) LIMIT 1");
            $stmt->execute([$ip, $now]);
            $blocked = $stmt->fetch();
            if ($blocked) return ['blocked' => true, 'reason' => $blocked['reason'], 'type' => 'ip'];
        }
        if ($email) {
            $stmt = $pdo->prepare("SELECT id, reason FROM blacklisted_entities WHERE entity_type = 'email' AND entity_value = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > ?) LIMIT 1");
            $stmt->execute([$email, $now]);
            $blocked = $stmt->fetch();
            if ($blocked) return ['blocked' => true, 'reason' => $blocked['reason'], 'type' => 'email'];
        }
        if ($userId) {
            $stmt = $pdo->prepare("SELECT id, reason FROM blacklisted_entities WHERE entity_type = 'user' AND entity_value = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > ?) LIMIT 1");
            $stmt->execute([$userId, $now]);
            $blocked = $stmt->fetch();
            if ($blocked) return ['blocked' => true, 'reason' => $blocked['reason'], 'type' => 'user'];
        }
        if ($phone) {
            $stmt = $pdo->prepare("SELECT id, reason FROM blacklisted_entities WHERE entity_type = 'phone' AND entity_value = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > ?) LIMIT 1");
            $stmt->execute([$phone, $now]);
            $blocked = $stmt->fetch();
            if ($blocked) return ['blocked' => true, 'reason' => $blocked['reason'], 'type' => 'phone'];
        }

        return ['blocked' => false];
    }
}

if (!function_exists('checkVelocity')) {
    function checkVelocity($pdo, $ip, $userId = null, $windowMinutes = 10, $maxActions = 5) {
        $since = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
        $count = 0;
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM spam_detection_logs WHERE ip_address = ? AND created_at > ?");
            $stmt->execute([$ip, $since]);
            $count = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}
        return $count >= $maxActions;
    }
}

if (!function_exists('detectSpam')) {
    function detectSpam($pdo, $content, $ip, $userId = null, $deviceId = null, $contextType = 'general') {
        $flags = ['velocity' => false, 'vpn' => false, 'proxy' => false];
        $duplicateScore = 0.0;

        if (checkVelocity($pdo, $ip, $userId, 10, 5)) {
            $flags['velocity'] = true;
        }

        if ($content) {
            $lower = strtolower($content);
            $spamPatterns = ['/https?:\/\//i', '/\b(buy|sell|click here|free money|earn \$|whatsapp|telegram group|call now|limited offer)\b/i', '/(.{1,20})\1{3,}/i', '/<[^>]+>/i'];
            $matchCount = 0;
            foreach ($spamPatterns as $pattern) {
                if (preg_match($pattern, $content)) $matchCount++;
            }
            $duplicateScore = min(1.0, $matchCount / 3);
        }

        $flags['vpn'] = false;
        $flags['proxy'] = false;
        try {
            $checkIp = @gethostbyname(gethostbyaddr($ip));
        } catch (Exception $e) {}

        $isSpam = $flags['velocity'] || $duplicateScore > 0.6;

        try {
            $stmt = $pdo->prepare("INSERT INTO spam_detection_logs (id, user_id, ip_address, device_fingerprint, duplicate_content_score, velocity_flag, vpn_detected, proxy_detected, created_at) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $ip, $deviceId, $duplicateScore, $flags['velocity'] ? 1 : 0, $flags['vpn'] ? 1 : 0, $flags['proxy'] ? 1 : 0]);
        } catch (Exception $e) {}

        return ['is_spam' => $isSpam, 'flags' => $flags, 'duplicate_score' => $duplicateScore];
    }
}
