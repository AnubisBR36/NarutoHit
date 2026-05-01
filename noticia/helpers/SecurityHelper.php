<?php

class SecurityHelper {
    
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function sanitizeHtml($html) {
        $allowed_tags = '<b><i><u><br><p><strong><em>';
        $sanitized = strip_tags($html, $allowed_tags);
        
        $sanitized = preg_replace('/<a\s+[^>]*href=["\']javascript:[^"\']*["\'][^>]*>.*?<\/a>/i', '', $sanitized);
        $sanitized = preg_replace('/<a\s+[^>]*href=["\']data:[^"\']*["\'][^>]*>.*?<\/a>/i', '', $sanitized);
        
        return $sanitized;
    }
    
    public static function sanitizeOutput($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
