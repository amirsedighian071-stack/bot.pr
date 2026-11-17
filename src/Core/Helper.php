<?php
namespace Core;

class Helper {
    public static function generateOrderId(): string {
        return 'ORD-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time() % 10000;
    }

    public static function randomUsername(string $prefix = 'user'): string {
        return $prefix . rand(10000, 99999) . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 3);
    }

    public static function sanitizeUsername(string $u): string {
        $u = strtolower(trim($u));
        $u = preg_replace('/[^a-z0-9_]/', '', $u);
        return substr($u, 0, 32);
    }

    public static function isValidUsername(string $u): bool {
        return (bool)preg_match('/^[a-zA-Z0-9_]{3,32}$/', $u);
    }

    public static function formatBytes(int $bytes): string {
        if ($bytes < 1024) return $bytes . ' B';
        $units = ['KB','MB','GB','TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i-1];
    }

    public static function gbToBytes(float $gb): int {
        return (int)($gb * 1024 * 1024 * 1024);
    }

    public static function bytesToGb(int $bytes): float {
        return round($bytes / (1024*1024*1024), 2);
    }

    public static function numberFormat(int $n): string {
        return number_format($n);
    }

    public static function escapeHtml(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function appUrl(string $path = ''): string {
        $base = rtrim(Config::get('app.url', ''), '/');
        if (!$base) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = $proto . '://' . $host;
        }
        return $base . '/' . ltrim($path, '/');
    }

    public static function log(string $msg, string $file = 'app.log'): void {
        $line = date('Y-m-d H:i:s') . " | $msg\n";
        @file_put_contents(LOG_PATH . '/' . $file, $line, FILE_APPEND);
    }

    public static function jsonResponse(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): void {
        header("Location: $url");
        exit;
    }

    public static function generateSecret(int $len = 32): string {
        return bin2hex(random_bytes($len));
    }
}
