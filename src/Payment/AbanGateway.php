<?php
namespace Payment;
use Core\Config;
use Core\Database;
use Core\Helper;

class AbanGateway {
    private string $token;
    private string $base = 'https://api.abangateway.ir/api/v1';

    public function __construct(?string $token = null) {
        $this->token = $token ?: (string)Config::setting('abangateway_token','');
    }

    private function headers(): array {
        return [
            "Authorization: Bearer {$this->token}",
            "Content-Type: application/json",
            "Accept: application/json"
        ];
    }

    // ایجاد فاکتور - مبلغ به تومان ورودی، به ریال ارسال می‌شود
    public function createInvoice(string $orderId, int $amountToman, string $callbackUrl, string $description = 'خرید VPN', ?string $payerPhone = null): array {
        if (empty($this->token)) return ['success'=>false,'message'=>'توکن آبان‌گیت تنظیم نشده'];
        $amountRial = $amountToman * 10;
        $payload = [
            'amount' => $amountRial,
            'order_id' => $orderId,
            'callback_url' => $callbackUrl,
            'description' => $description,
        ];
        if ($payerPhone) $payload['payer_phone'] = $payerPhone;

        $ch = curl_init($this->base . '/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER=>$this->headers(),
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
        // فرمت‌های مختلف پاسخ را پوشش بده
        $invoiceUrl = $json['payment_url'] ?? $json['url'] ?? $json['link'] ?? $json['data']['payment_url'] ?? $json['data']['url'] ?? null;
        $invoiceId = $json['invoice_id'] ?? $json['id'] ?? $json['data']['id'] ?? null;

        if (($code>=200 && $code<300) && $invoiceUrl) {
            Database::query("UPDATE abangateway_logs SET abangateway_invoice_id=?, status='created' WHERE order_id_unique=? ORDER BY id DESC LIMIT 1", [$invoiceId, $orderId]);
            return ['success'=>true,'url'=>$invoiceUrl,'invoice_id'=>$invoiceId,'raw'=>$json];
        }
        // اگر API آبان در دسترس نبود (تست)، یک URL نمونه برگردان
        if (!$invoiceUrl) {
            $msg = $json['message'] ?? $raw ?: 'پاسخ نامعتبر از آبان‌گیت';
            Helper::log("AbanGateway create failed order $orderId: $msg", 'payment.log');
            return ['success'=>false,'message'=>$msg,'raw'=>$json];
        }
        return ['success'=>false,'message'=>'خطا ناشناخته','raw'=>$json];
    }

    // استعلام وضعیت فاکتور
    public function verifyInvoice(string $orderId, ?string $invoiceId = null): array {
        if (empty($this->token)) return ['success'=>false,'message'=>'توکن تنظیم نشده'];
        $url = $this->base . '/invoices/verify';
        $payload = ['order_id'=>$orderId];
        if ($invoiceId) $payload['invoice_id']=$invoiceId;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>json_encode($payload),
            CURLOPT_HTTPHEADER=>$this->headers(),
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
        // بررسی دقیق مبلغ
        return ['success'=>false,'paid'=>false,'status'=>$status??'pending','raw'=>$json,'message'=> $json['message'] ?? 'پرداخت تأیید نشد'];
    }

    // Callback هندلر - آبان گیت به این آدرس POST می‌زند
    public static function handleCallback(array $payload): array {
        // payload نمونه: ['order_id'=>'ORD-...', 'status'=>'paid', 'amount'=>..., 'invoice_id'=>...]
        $orderId = $payload['order_id'] ?? $payload['orderId'] ?? null;
        $status = $payload['status'] ?? null;
        if (!$orderId) return ['success'=>false,'message'=>'order_id missing'];
        
        // Idempotency: اگر سفارش قبلاً completed بود، دوباره پردازش نکن
        $order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderId]);
        if (!$order) return ['success'=>false,'message'=>'سفارش یافت نشد'];
        if (in_array($order['status'], ['completed','paid'])) {
            return ['success'=>true,'message'=>'قبلاً پردازش شده (Idempotent)'];
        }

        $gamma = new self();
        $verify = $gamma->verifyInvoice($orderId, $payload['invoice_id'] ?? $order['abangateway_invoice_id']);
        if ($verify['paid'] ?? false) {
            // به OrderService واگذار می‌شود
            return ['success'=>true,'verified'=>true,'order'=>$order];
        }
        return ['success'=>false,'message'=>'تأیید ناموفق','raw'=>$verify];
    }

    public function testConnection(): array {
        if (empty($this->token)) return ['success'=>false,'message'=>'توکن خالی است'];
        $ch = curl_init($this->base . '/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CUSTOMREQUEST=>'GET',
            CURLOPT_HTTPHEADER=>$this->headers(),
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
