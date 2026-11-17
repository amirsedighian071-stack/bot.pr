<?php
namespace Core;

class Config {
    private static array $config = [];
    private static array $settingsCache = [];
    private static bool $loaded = false;

    public static function load(): void {
        if (self::$loaded) return;
        $file = CONFIG_PATH . '/config.php';
        if (!file_exists($file)) {
            $file = CONFIG_PATH . '/config.php.example';
        }
        if (file_exists($file)) {
            self::$config = require $file;
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null) {
        self::load();
        $keys = explode('.', $key);
        $val = self::$config;
        foreach ($keys as $k) {
            if (!isset($val[$k])) return $default;
            $val = $val[$k];
        }
        return $val;
    }

    // تنظیمات داینامیک از DB (settings table) با کش سبک
    public static function setting(string $key, $default = null) {
        if (isset(self::$settingsCache[$key])) return self::$settingsCache[$key];
        try {
            $row = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
            if ($row) {
                self::$settingsCache[$key] = $row['setting_value'];
                return $row['setting_value'];
            }
        } catch (\Exception $e) {}
        return $default;
    }

    public static function setSetting(string $key, string $value): void {
        Database::query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [$key, $value]);
        self::$settingsCache[$key] = $value;
    }

    public static function botToken(): string {
        return (string)self::setting('bot_token', '');
    }

    public static function adminTelegramId(): string {
        return (string)self::setting('admin_telegram_id', '');
    }

    public static function allSettings(): array {
        try {
            return Database::fetchAll("SELECT setting_key, setting_value FROM settings");
        } catch (\Exception $e) { return []; }
    }

    public static function clearCache(): void {
        self::$settingsCache = [];
    }
}
