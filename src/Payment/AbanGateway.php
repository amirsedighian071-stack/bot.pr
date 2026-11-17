<?php
namespace Payment;
use Core\Config;
use Core\Database;
use Core\Helper;

/**
 * AbanGateway - کپی دقیق ساختار فاکسیما آپدیت‌شده
 * مرجع: /tmp/faoxima/re/rx/function/database_helpers_2.php + payment/abangateway.php
 * - endpoint: PaySetting.urlabangateway (https://...)
 * - apiKey: PaySetting.apiabangateway
 * - routes: /create , /verify , callback /payment/abangateway.php?order=
 * - payload: amount (toman), order_id, callback_url=https://domainhosts/payment/abangateway.php?order=, user_id
 * - verify: authority=abn_..., order_id, check amount_rial vs billed_toman*10
 */
class AbanGateway {
    private string $token;
    private string $legacyBase = 'https://api.abangateway.ir/api/v1';

    public function __construct(?string $token = null) {
        $this->token = $token ?: (string)(self::abangatewayApiKey() ?: Config::setting('abangateway_token',''));
    }

    // ===== Faoxima-compatible static helpers =====
    public static function abangatewayEndpoint(): ?string {
        // 1. تنظیمات فاکسیما: PaySetting.urlabangateway
        $url = '';
        try {
            $row = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key='urlabangateway' LIMIT 1");
            if ($row && !empty($row['setting_value'])) $url = trim($row['setting_value']);
        } catch (\Throwable $e) {}
        if ($url === '') {
            try {
                $row = Database::fetchOne("SELECT ValuePay FROM PaySetting WHERE NamePay='urlabangateway' LIMIT 1");
                if ($row && !empty($row['ValuePay'])) $url = trim($row['ValuePay']);
            } catch (\Throwable $e) {}
        }
        if ($url === '') $url = trim((string)Config::setting('abangateway_api_url',''));
        if ($url === '') $url = trim((string)Config::setting('urlabangateway',''));
        if ($url === '' || stripos($url, 'https://') !== 0 || !filter_var($url, FILTER_VALIDATE_URL)) {
            // fallback به تنظیمات قدیمی callback_url اگر endpoint ذخیره نشده بود
            $cb = trim((string)Config::setting('abangateway_callback_url',''));
            if ($cb && preg_match('#^https://([^/]+)#', $cb, $m)) {
                // سعی حدس endpoint از callback نیست - برگرد null تا isReady false شود
            }
            return null;
        }
        return rtrim($url, '/');
    }

    public static function abangatewayApiKey(): string {
        $key = '';
        try {
            $row = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key='apiabangateway' LIMIT 1");
            if ($row && !empty($row['setting_value'])) $key = trim($row['setting_value']);
        } catch (\Throwable $e) {}
        if ($key === '') {
            try {
                $row = Database::fetchOne("SELECT ValuePay FROM PaySetting WHERE NamePay='apiabangateway' LIMIT 1");
                if ($row && !empty($row['ValuePay'])) $key = trim($row['ValuePay']);
            } catch (\Throwable $e) {}
        }
        if ($key === '') $key = trim((string)Config::setting('abangateway_token',''));
        if ($key === '') $key = trim((string)Config::setting('apiabangateway',''));
        return ($key === '0') ? '' : $key;
    }

    public static function abangatewayIsReady(): bool {
        $status = '';
        try {
            $row = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key='statusabangateway' LIMIT 1");
            if ($row) $status = (string)$row['setting_value'];
        } catch (\Throwable $e) {}
        if ($status === '') {
            try {
                $row = Database::fetchOne("SELECT ValuePay FROM PaySetting WHERE NamePay='statusabangateway' LIMIT 1");
                if ($row) $status = (string)$row['ValuePay'];
            } catch (\Throwable $e) {}
        }
        // اگر status تنظیم نشده، اما endpoint و key موجود باشد، آماده فرض کن (سازگاری با نسخه قدیمی)
        $ep = self::abangatewayEndpoint();
        $key = self::abangatewayApiKey();
        if ($status === '' ) return $ep !== null && $key !== '';
        return $status === 'onabangateway' && $ep !== null && $key !== '';
    }

    public static function abangatewayPayUrlFor(string $authority): ?string {
        $authority = trim($authority);
        $endpoint = self::abangatewayEndpoint();
        if ($endpoint === null || !preg_match('/^abn_([A-Za-z0-9_]{6,64})$/', $authority, $m)) return null;
        $parts = parse_url($endpoint);
        if (!is_array($parts) || empty($parts['host'])) return null;
        $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':'.$parts['port'] : '');
        return $origin . '/pay/' . $m[1];
    }

    public static function getDomainHosts(): string {
        // دقیقاً مانند فاکسیما: $domainhosts = rtrim(preg_replace('#^https?://','', $domainhosts), '/')
        $host = '';
        $appUrl = Config::get('app.url','');
        if ($appUrl) {
            $host = preg_replace('#^https?://#','', $appUrl);
            $host = rtrim($host, '/');
            $host = explode('/', $host)[0];
        }
        if ($host === '' && !empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }
        // fallback به globals اگر فاکسیما config وجود داشت
        if ($host === '' && !empty($GLOBALS['domainhosts'])) $host = $GLOBALS['domainhosts'];
        return $host;
    }

    public static function abangatewayRequest(string $path, array $payload): array {
        $endpoint = self::abangatewayEndpoint();
        $apiKey = self::abangatewayApiKey();
        if ($endpoint === null || $apiKey === '') {
            return ['ok'=>false,'status_code'=>0,'data'=>null,'message'=>'آدرس درگاه یا کلید اتصال آبان گیت وی تنظیم نشده است'];
        }
        $url = $endpoint . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_ENCODING=>'',
            CURLOPT_MAXREDIRS=>3,
            CURLOPT_CONNECTTIMEOUT=>10,
            CURLOPT_TIMEOUT=>30,
            CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST=>'POST',
            CURLOPT_HTTPHEADER=>[
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer '.$apiKey,
            ],
            CURLOPT_POSTFIELDS=>json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false) {
            error_log('AbanGateway request failed: '.json_encode(['path'=>$path,'error'=>$err,'errno'=>$errno], JSON_UNESCAPED_UNICODE));
            return ['ok'=>false,'status_code'=>0,'data'=>null,'message'=>'خطا در ارتباط با آبان گیت وی'];
        }
        $dec = json_decode($resp, true);
        if (!is_array($dec)) {
            error_log('AbanGateway invalid response: '.json_encode(['path'=>$path,'status_code'=>$code,'raw'=>mb_substr((string)$resp,0,500)], JSON_UNESCAPED_UNICODE));
            return ['ok'=>false,'status_code'=>$code,'data'=>null,'message'=>'پاسخ نامعتبر از آبان گیت وی'];
        }
        $msg = (string)($dec['message'] ?? ($dec['error'] ?? ''));
        return ['ok'=>$code>=200 && $code<300,'status_code'=>$code,'data'=>$dec,'message'=>$msg];
    }

    public static function abangatewayCreatePayment(string $order_id, int $amount_toman, $user_id = null): array {
        $domain = self::getDomainHosts();
        if ($domain === '') $domain = 'example.com';
        $payload = [
            'amount'=> (int)$amount_toman,
            'order_id'=> (string)$order_id,
            'callback_url'=> 'https://'.$domain.'/payment/abangateway.php?order='.rawurlencode((string)$order_id),
        ];
        if ($user_id !== null && $user_id !== '') $payload['user_id']=(string)$user_id;
        $ans = self::abangatewayRequest('/create', $payload);
        $data = is_array($ans['data']) ? $ans['data'] : [];
        if (!$ans['ok'] || empty($data['success'])) {
            return ['success'=>false,'message'=>$ans['message']!==''?$ans['message']:'ساخت لینک پرداخت در آبان گیت وی ناموفق بود','status_code'=>$ans['status_code']];
        }
        $authority = trim((string)($data['authority'] ?? ''));
        $payUrl = trim((string)($data['payment_link'] ?? ''));
        if ($authority === '' || stripos($payUrl,'https://')!==0) {
            error_log('AbanGateway missing authority/payment_link: '.json_encode($data, JSON_UNESCAPED_UNICODE));
            return ['success'=>false,'message'=>'پاسخ نامعتبر از آبان گیت وی'];
        }
        return ['success'=>true,'authority'=>$authority,'pay_url'=>$payUrl];
    }

    public static function abangatewayVerifyPayment(string $authority, string $order_id, int $billed_toman): array {
        $ans = self::abangatewayRequest('/verify', ['authority'=>(string)$authority,'order_id'=>(string)$order_id]);
        $data = is_array($ans['data']) ? $ans['data'] : [];
        $res = ['paid'=>false,'reachable'=>$ans['status_code']>0,'status_code'=>$ans['status_code'],'message'=>$ans['message'],'amount_rial'=>(int)($data['amount']??0)];
        if (!$ans['ok'] || empty($data['success'])) return $res;
        if ((string)($data['order_id']??'') !== (string)$order_id) { $res['message']='order mismatch'; return $res; }
        if ($res['amount_rial'] < ((int)$billed_toman)*10) { $res['message']='amount mismatch'; return $res; }
        $res['paid']=true;
        return $res;
    }

    // ===== Legacy wrappers برای سازگاری با bot.pr existing code =====
    private function legacyHeaders(): array {
        $tok = $this->token ?: self::abangatewayApiKey();
        return ["Authorization: Bearer {$tok}","Content-Type: application/json","Accept: application/json"];
    }

    public function createInvoice(string $orderId, int $amountToman, string $callbackUrl, string $description = 'خرید VPN', ?string $payerPhone = null): array {
        // اگر تنظیمات فاکسیما فعال بود، از آن استفاده کن (اولویت)
        if (self::abangatewayEndpoint() !== null && self::abangatewayApiKey() !== '') {
            $res = self::abangatewayCreatePayment($orderId, $amountToman, null);
            if ($res['success']) {
                Database::query("INSERT INTO abangateway_logs (order_id_unique, amount_rial, amount_toman, status, response_json) VALUES (?,?,?,?,?)",
                    [$orderId, $amountToman*10, $amountToman, 200, json_encode($res, JSON_UNESCAPED_UNICODE)]);
                Database::query("UPDATE abangateway_logs SET abangateway_invoice_id=?, status='created' WHERE order_id_unique=? ORDER BY id DESC LIMIT 1", [$res['authority'], $orderId]);
                return ['success'=>true,'url'=>$res['pay_url'],'invoice_id'=>$res['authority'],'authority'=>$res['authority'],'raw'=>$res];
            }
            // لاگ خطا
            Database::query("INSERT INTO abangateway_logs (order_id_unique, amount_rial, amount_toman, status, response_json) VALUES (?,?,?,?,?)",
                [$orderId, $amountToman*10, $amountToman, $res['status_code']??0, json_encode($res, JSON_UNESCAPED_UNICODE)]);
            return ['success'=>false,'message'=>$res['message']??'خطا','raw'=>$res];
        }
        // fallback legacy: api.abangateway.ir/api/v1/invoices
        if (empty($this->token) && empty(self::abangatewayApiKey())) return ['success'=>false,'message'=>'توکن آبان‌گیت تنظیم نشده'];
        $amountRial = $amountToman * 10;
        $payload = ['amount'=>$amountRial,'order_id'=>$orderId,'callback_url'=>$callbackUrl,'description'=>$description];
        if ($payerPhone) $payload['payer_phone']=$payerPhone;
        $base = $this->legacyBase;
        // اگر endpoint فاکسیما نداریم ولی legacyBase داریم
        $ch = curl_init($base . '/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER=>$this->legacyHeaders(),
            CURLOPT_TIMEOUT=>15,
            CURLOPT_SSL_VERIFYPEER=>true,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        Database::query("INSERT INTO abangateway_logs (order_id_unique, amount_rial, amount_toman, status, response_json) VALUES (?,?,?,?,?)",
            [$orderId, $amountRial, $amountToman, $code, $raw]);
        if ($err) return ['success'=>false,'message'=>"خطا شبکه آبان‌گیت: $err"];
        $json = json_decode($raw, true);
        $invoiceUrl = $json['payment_url'] ?? $json['url'] ?? $json['link'] ?? $json['data']['payment_url'] ?? $json['data']['url'] ?? null;
        $invoiceId = $json['invoice_id'] ?? $json['id'] ?? $json['data']['id'] ?? null;
        if (($code>=200 && $code<300) && $invoiceUrl) {
            Database::query("UPDATE abangateway_logs SET abangateway_invoice_id=?, status='created' WHERE order_id_unique=? ORDER BY id DESC LIMIT 1", [$invoiceId, $orderId]);
            return ['success'=>true,'url'=>$invoiceUrl,'invoice_id'=>$invoiceId,'raw'=>$json];
        }
        if (!$invoiceUrl) {
            $msg = $json['message'] ?? $raw ?: 'پاسخ نامعتبر از آبان‌گیت';
            Helper::log("AbanGateway create failed order $orderId: $msg", 'payment.log');
            return ['success'=>false,'message'=>$msg,'raw'=>$json];
        }
        return ['success'=>false,'message'=>'خطا ناشناخته','raw'=>$json];
    }

    public function verifyInvoice(string $orderId, ?string $invoiceId = null): array {
        // اگر فاکسیما endpoint فعال است، از authority استفاده کن
        if (self::abangatewayEndpoint() !== null && $invoiceId && strpos($invoiceId,'abn_')===0) {
            $order = Database::fetchOne("SELECT final_price FROM orders WHERE order_id_unique=?", [$orderId]);
            $billed = (int)($order['final_price'] ?? 0);
            $res = self::abangatewayVerifyPayment($invoiceId, $orderId, $billed);
            $status = $res['paid'] ? 'paid' : ($res['message'] ?? 'pending');
            Database::query("INSERT INTO abangateway_logs (order_id_unique, abangateway_invoice_id, status, response_json) VALUES (?,?,?,?)",
                [$orderId, $invoiceId, $status, json_encode($res, JSON_UNESCAPED_UNICODE)]);
            if ($res['paid']) return ['success'=>true,'paid'=>true,'status'=>'paid','raw'=>$res];
            return ['success'=>false,'paid'=>false,'status'=>$status,'raw'=>$res,'message'=>$res['message']??'پرداخت تأیید نشد'];
        }
        // legacy verify
        if (empty($this->token) && empty(self::abangatewayApiKey())) return ['success'=>false,'message'=>'توکن تنظیم نشده'];
        $url = $this->legacyBase . '/invoices/verify';
        $payload = ['order_id'=>$orderId];
        if ($invoiceId) $payload['invoice_id']=$invoiceId;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>json_encode($payload),
            CURLOPT_HTTPHEADER=>$this->legacyHeaders(),
            CURLOPT_TIMEOUT=>15,
            CURLOPT_SSL_VERIFYPEER=>true,
        ]);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode($raw, true);
        $status = $json['status'] ?? $json['data']['status'] ?? null;
        $isPaid = in_array(strtolower((string)$status), ['paid','success','verified','completed','ok'], true) || ($json['verified']===true) || ($code==200 && ($json['success']??false));
        Database::query("INSERT INTO abangateway_logs (order_id_unique, abangateway_invoice_id, status, response_json) VALUES (?,?,?,?)",
            [$orderId, $invoiceId, $status ?: ($isPaid?'paid':'unknown'), $raw]);
        if ($isPaid) return ['success'=>true,'paid'=>true,'status'=>$status,'raw'=>$json];
        return ['success'=>false,'paid'=>false,'status'=>$status??'pending','raw'=>$json,'message'=> $json['message'] ?? 'پرداخت تأیید نشد'];
    }

    public static function handleCallback(array $payload): array {
        $orderId = $payload['order_id'] ?? $payload['orderId'] ?? null;
        if (!$orderId) return ['success'=>false,'message'=>'order_id missing'];
        $order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderId]);
        if (!$order) return ['success'=>false,'message'=>'سفارش یافت نشد'];
        if (in_array($order['status'], ['completed','paid'])) return ['success'=>true,'message'=>'قبلاً پردازش شده (Idempotent)'];
        $gamma = new self();
        $verify = $gamma->verifyInvoice($orderId, $payload['invoice_id'] ?? $payload['authority'] ?? $order['abangateway_invoice_id']);
        if ($verify['paid'] ?? false) return ['success'=>true,'verified'=>true,'order'=>$order];
        return ['success'=>false,'message'=>'تأیید ناموفق','raw'=>$verify];
    }

    public function testConnection(): array {
        // اولویت فاکسیما
        if (self::abangatewayEndpoint() !== null) {
            $ep = self::abangatewayEndpoint();
            $key = self::abangatewayApiKey();
            if (empty($key)) return ['success'=>false,'message'=>'کلید اتصال خالی است'];
            // تست با درخواست ساختگی به /create با amount کم و order تستی - یا فقط چک https
            $ch = curl_init($ep . '/create');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_POST=>true,
                CURLOPT_POSTFIELDS=>json_encode(['amount'=>1000,'order_id'=>'TEST_'.time(),'callback_url'=>'https://'.self::getDomainHosts().'/payment/abangateway.php?order=TEST'], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','Authorization: Bearer '.$key],
                CURLOPT_TIMEOUT=>10,
                CURLOPT_SSL_VERIFYPEER=>false,
            ]);
            $raw=curl_exec($ch);
            $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
            $err=curl_error($ch);
            curl_close($ch);
            if ($err) return ['success'=>false,'message'=>'خطا شبکه: '.$err];
            if ($code==401) return ['success'=>false,'message'=>'کلید نامعتبر (401) - کلید فاکسیما آپدیت‌شده را بررسی کنید'];
            if ($code>=200 && $code<500) {
                // حتی اگر سفارش تست رد شود، اتصال برقرار است
                return ['success'=>true,'message'=>'اتصال به آبان‌گیت (فاکسیما) موفق (کد '.$code.') - آدرس: '.$ep];
            }
            return ['success'=>false,'message'=>'خطا اتصال: '.$raw];
        }
        // legacy
        $tok = $this->token ?: self::abangatewayApiKey();
        if (empty($tok)) return ['success'=>false,'message'=>'توکن خالی است'];
        $ch = curl_init($this->legacyBase . '/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CUSTOMREQUEST=>'GET',
            CURLOPT_HTTPHEADER=>$this->legacyHeaders(),
            CURLOPT_TIMEOUT=>10,
            CURLOPT_SSL_VERIFYPEER=>true,
        ]);
        $raw=curl_exec($ch);
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code==401) return ['success'=>false,'message'=>'توکن نامعتبر (401)'];
        if ($code>=200 && $code<500) return ['success'=>true,'message'=>'اتصال به آبان‌گیت موفق (کد '.$code.')'];
        return ['success'=>false,'message'=>'خطا اتصال: '.$raw];
    }
}
