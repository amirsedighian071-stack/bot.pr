<?php
namespace Core;

// کش سبک مبتنی بر فایل + APCu اگر موجود باشد - برای توکن پنل‌ها
class Cache {
    public static function get(string $key) {
        if (function_exists('apcu_fetch')) {
            $val = apcu_fetch($key, $success);
            if ($success) return $val;
        }
        $file = sys_get_temp_dir() . '/vpnbot_' . md5($key) . '.cache';
        if (!file_exists($file)) return null;
        $data = @unserialize(@file_get_contents($file));
        if (!$data || $data['expire'] < time()) {
            @unlink($file);
            return null;
        }
        return $data['value'];
    }

    public static function set(string $key, $value, int $ttl = 3600): void {
        if (function_exists('apcu_store')) {
            apcu_store($key, $value, $ttl);
        }
        $file = sys_get_temp_dir() . '/vpnbot_' . md5($key) . '.cache';
        @file_put_contents($file, serialize(['value'=>$value, 'expire'=>time()+$ttl]), LOCK_EX);
    }

    public static function delete(string $key): void {
        if (function_exists('apcu_delete')) apcu_delete($key);
        $file = sys_get_temp_dir() . '/vpnbot_' . md5($key) . '.cache';
        @unlink($file);
    }
}
