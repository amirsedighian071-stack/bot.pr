<?php
namespace Panels;

class PanelFactory {
    public static function create(array $panelRow): PanelInterface {
        $type = $panelRow['type'];
        return match($type) {
            'pasarguard' => new Drivers\PasarGuardPanel($panelRow),
            'marzban' => new Drivers\MarzbanPanel($panelRow),
            'marzneshin' => new Drivers\MarzneshinPanel($panelRow),
            'xui_single', 'xui_multi' => new Drivers\XuiPanel($panelRow),
            'rebecca' => new Drivers\RebeccaPanel($panelRow),
            'remnawave' => new Drivers\RemnawavePanel($panelRow),
            'guard' => new Drivers\GuardPanel($panelRow),
            'wgdashboard' => new Drivers\WGDashboardPanel($panelRow),
            'manual' => new Drivers\ManualPanel($panelRow),
            default => new Drivers\ManualPanel($panelRow),
        };
    }

    public static function createById(int $panelId): ?PanelInterface {
        $row = \Core\Database::fetchOne("SELECT * FROM panels WHERE id=?", [$panelId]);
        if (!$row) return null;
        return self::create($row);
    }
}
