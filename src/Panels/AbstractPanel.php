<?php
namespace Panels;
use Core\Cache;
use Core\Helper;

abstract class AbstractPanel implements PanelInterface {
    protected array $config;
    protected string $type;
    protected ?string $cachedToken = null;

    public function __construct(array $panelRow) {
        $this->config = $panelRow;
        $this->type = $panelRow['type'] ?? 'manual';
    }

    protected function httpRequest(string $method, string $endpoint, array $data = [], array $headers = [], int $timeout = 0): array {
        $timeout = $timeout ?: (int)($this->config['timeout'] ?? 10);
        $url = rtrim($this->config['url'], '/') . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init($url);
        $defaultHeaders = ['Accept: application/json', 'Content-Type: application/json'];
        $headers = array_merge($defaultHeaders, $headers);
        if ($this->cachedToken || $this->getToken()) {
            // توکن در هدر اضافه می‌شود در کلاس فرزند اگر نیاز باشد
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];
        if (strtoupper($method) === 'POST' || strtoupper($method) === 'PUT' || strtoupper($method) === 'PATCH') {
            $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            $opts[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
        } elseif (!empty($data) && strtoupper($method) === 'GET') {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($err) {
            Helper::log("Panel {$this->config['name']} HTTP error: $err ($url)", 'panels.log');
            return ['success'=>false, 'code'=>$code, 'error'=>$err, 'raw'=>$raw];
        }
        $json = json_decode($raw, true);
        return ['success'=> $code >=200 && $code <300, 'code'=>$code, 'data'=>$json, 'raw'=>$raw];
    }

    protected function cacheKey(string $suffix): string {
        return "panel_{$this->config['id']}_{$suffix}";
    }

    public function getCapabilities(): array {
        return PanelCapabilities::get($this->type);
    }

    // پیش‌فرض‌ها - فرزندان override می‌کنند
    public function toggleUserStatus(string $username, bool $active): bool { return false; }
}
