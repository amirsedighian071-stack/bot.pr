<?php
namespace Panels;

// اینترفیس واحد برای تمام پنل‌ها - برگرفته از Faoxima Architecture
interface PanelInterface {
    public function authenticate(): bool;
    public function getToken(): ?string;
    
    /**
     * ساخت کاربر جدید
     * @param string $username
     * @param int $dataLimitBytes 0 = unlimited
     * @param int $expireTimestamp timestamp
     * @param mixed $groupOrInboundIds array|string|null
     * @return array ['success'=>bool, 'subscription_url'=>string, 'uuid'=>string, 'message'=>string]
     */
    public function createUser(string $username, int $dataLimitBytes, int $expireTimestamp, $groupOrInboundIds = null): array;
    
    public function getUser(string $username): ?array; // ['username','data_limit','used_traffic','expire','subscription_url','status']
    public function renewUser(string $username, int $addDataBytes, int $addTimeSeconds, bool $resetTraffic = false): array;
    public function deleteUser(string $username): bool;
    public function toggleUserStatus(string $username, bool $active): bool;
    
    /**
     * لیست گروه‌ها / Inboundها / سرویس‌ها برای اتصال پلن
     * @return array [['id'=>..., 'name'=>..., 'type'=>...], ...]
     */
    public function getGroupsOrInbounds(): array;
    
    public function testConnection(): array; // ['success'=>bool,'message'=>string]
    public function getCapabilities(): array;
}
