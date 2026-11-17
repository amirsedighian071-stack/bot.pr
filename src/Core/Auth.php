<?php
namespace Core;

class Auth {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $name = Config::get('security.session_name', 'VPNBOTSESSID');
            session_name($name);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    public static function login(string $username, string $password): bool {
        $admin = Database::fetchOne("SELECT * FROM admins WHERE username = ? AND is_active=1", [$username]);
        if (!$admin) return false;
        if (!password_verify($password, $admin['password_hash'])) return false;
        self::startSession();
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        Database::query("UPDATE admins SET last_login_at=NOW(), last_login_ip=? WHERE id=?", [$_SERVER['REMOTE_ADDR'] ?? '', $admin['id']]);
        Database::query("INSERT INTO audit_logs (admin_id, action, details, ip) VALUES (?,?,?,?)", [$admin['id'], 'login', 'ورود موفق', $_SERVER['REMOTE_ADDR'] ?? '']);
        return true;
    }

    public static function check(): bool {
        self::startSession();
        return isset($_SESSION['admin_id']);
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            Helper::redirect(Helper::appUrl('admin/login.php'));
        }
    }

    public static function admin(): ?array {
        self::startSession();
        if (!isset($_SESSION['admin_id'])) return null;
        return Database::fetchOne("SELECT * FROM admins WHERE id=?", [$_SESSION['admin_id']]);
    }

    public static function logout(): void {
        self::startSession();
        session_destroy();
    }

    public static function hasRole(array $roles): bool {
        self::startSession();
        return in_array($_SESSION['admin_role'] ?? '', $roles);
    }

    public static function csrfToken(): string {
        self::startSession();
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool {
        self::startSession();
        return hash_equals($_SESSION['_csrf'] ?? '', $token ?? '');
    }
}
