<?php
namespace Panels;

// ماتریس قابلیت‌ها مشابه panel_capability.php در فاکسیما
class PanelCapabilities {
    const MAP = [
        'pasarguard' => ['create'=>true,'renew'=>true,'inbounds'=>false,'groups'=>true,'reset_traffic'=>true,'multi_inbound'=>true],
        'marzban' => ['create'=>true,'renew'=>true,'inbounds'=>true,'groups'=>false,'reset_traffic'=>false,'multi_inbound'=>true],
        'marzneshin' => ['create'=>true,'renew'=>true,'inbounds'=>false,'groups'=>true,'reset_traffic'=>true,'multi_inbound'=>true],
        'xui_single' => ['create'=>true,'renew'=>true,'inbounds'=>true,'groups'=>false,'reset_traffic'=>true,'multi_inbound'=>false],
        'xui_multi' => ['create'=>true,'renew'=>true,'inbounds'=>true,'groups'=>false,'reset_traffic'=>true,'multi_inbound'=>true],
        'rebecca' => ['create'=>true,'renew'=>true,'inbounds'=>false,'groups'=>true,'reset_traffic'=>true,'multi_inbound'=>true],
        'remnawave' => ['create'=>true,'renew'=>true,'inbounds'=>false,'groups'=>false,'reset_traffic'=>true,'multi_inbound'=>true],
        'guard' => ['create'=>true,'renew'=>true,'inbounds'=>false,'groups'=>true,'reset_traffic'=>true,'multi_inbound'=>false],
        'wgdashboard' => ['create'=>true,'renew'=>true,'inbounds'=>false,'groups'=>false,'reset_traffic'=>false,'multi_inbound'=>false],
        'manual' => ['create'=>true,'renew'=>false,'inbounds'=>false,'groups'=>false,'reset_traffic'=>false,'multi_inbound'=>false],
    ];

    public static function get(string $type): array {
        return self::MAP[$type] ?? self::MAP['manual'];
    }

    public static function supports(string $type, string $cap): bool {
        return self::get($type)[$cap] ?? false;
    }
}
