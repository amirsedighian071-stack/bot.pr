<?php
namespace Services;
use Core\Database;
use Core\Helper;
use Core\Config;
use Panels\UnifiedPanelManager;
use Payment\AbanGateway;

class OrderService {
    // محاسبه مبلغ نهایی
    public static function calculatePrice(int $planId, float $volumeGb, ?string $discountCode = null, int $fee = null): array {
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$planId]);
        if (!$plan) throw new \Exception("پلن یافت نشد");
        $fee = $fee ?? (int)Config::setting('fee_amount','3000');
        $basePrice = 0;
        $perGb = (int)$plan['price_per_gb'];
        
        // اگر حجم سفارشی 0 باشد یا پلن قیمت ثابت داشته باشد
        if ((float)$plan['volume_gb'] > 0 && $volumeGb == (float)$plan['volume_gb']) {
            $basePrice = (int)$plan['price'];
        } else {
            // حجم دلخواه: (قیمت هر گیگ * تعداد گیگ) + کارمزد
            // اگر پلن قیمت هر گیگ نداشته باشد، از قیمت پایه پلن به عنوان هر گیگ استفاده نکن
            if ($perGb > 0) {
                $basePrice = (int)($perGb * $volumeGb);
            } else {
                // پلن بدون قیمت دلخواه: قیمت پایه را متناسب کن
                $ratio = $volumeGb / max((float)$plan['volume_gb'],1);
                $basePrice = (int)($plan['price'] * $ratio);
            }
        }
        $priceWithFee = $basePrice + $fee;
        
        $discountAmount = 0;
        $discountRow = null;
        if ($discountCode) {
            $discountRow = Database::fetchOne("SELECT * FROM discounts WHERE code=? AND is_active=1", [$discountCode]);
            if ($discountRow) {
                // بررسی انقضا، سقف، پلن
                $valid = true;
                if ($discountRow['expire_at'] && strtotime($discountRow['expire_at']) < time()) $valid=false;
                if ($discountRow['max_uses']>0 && $discountRow['used_count'] >= $discountRow['max_uses']) $valid=false;
                if ($discountRow['plan_id'] && $discountRow['plan_id'] != $planId) $valid=false;
                if ($discountRow['min_amount']>0 && $priceWithFee < $discountRow['min_amount']) $valid=false;
                if ($valid) {
                    if ($discountRow['type']==='percent') {
                        $calcBase = $discountRow['apply_on_fee'] ? $priceWithFee : $basePrice;
                        $discountAmount = (int)($calcBase * ((float)$discountRow['value']/100));
                    } else {
                        $discountAmount = (int)$discountRow['value'];
                    }
                    $discountAmount = min($discountAmount, $priceWithFee);
                } else {
                    $discountRow = null;
                }
            }
        }
        
        $final = max(0, $priceWithFee - $discountAmount);
        return [
            'base_price'=>$basePrice,
            'fee'=>$fee,
            'price_with_fee'=>$priceWithFee,
            'discount_amount'=>$discountAmount,
            'final_price'=>$final,
            'discount_row'=>$discountRow
        ];
    }

    // ثبت سفارش
    public static function createOrder(int $telegramId, int $planId, float $volumeGb, string $configUsername, ?string $discountCode = null, bool $isRenewal=false, ?int $renewSubId=null): array {
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$telegramId]);
        if (!$user) throw new \Exception("کاربر یافت نشد");
        
        // جلوگیری از نام تکراری (استعلام پنل اگر ممکن باشد)
        $exists = Database::fetchOne("SELECT id FROM subscriptions WHERE username=?", [$configUsername]);
        if ($exists) throw new \Exception("نام کاربری تکراری است، لطفاً نام دیگری انتخاب کنید");
        
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$planId]);
        $calc = self::calculatePrice($planId, $volumeGb, $discountCode);
        
        $orderId = Helper::generateOrderId();
        $callbackUrl = Helper::appUrl('public/callback.php?order_id=' . $orderId);
        // اگر تنظیمات کال‌بک جدا دارد
        $customCb = Config::setting('abangateway_callback_url','');
        if ($customCb) $callbackUrl = rtrim($customCb,'/') . '?order_id=' . $orderId;

        Database::query("INSERT INTO orders (order_id_unique, user_id, telegram_id, service_id, plan_id, config_group_id, username_config, volume_gb, duration_days, price, fee, discount_amount, discount_code, final_price, status, payment_gateway, is_renewal, renew_subscription_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", [
            $orderId, $user['id'], $telegramId, $plan['service_id'], $planId, $plan['config_group_id'], $configUsername, $volumeGb, $plan['duration_days'], $calc['base_price'], $calc['fee'], $calc['discount_amount'], $discountCode, $calc['final_price'], 'pending', 'abangateway', $isRenewal?1:0, $renewSubId
        ]);
        $orderDbId = (int)Database::lastInsertId();
        
        // ایجاد فاکتور آبان
        $aban = new AbanGateway();
        $invoice = $aban->createInvoice($orderId, (int)$calc['final_price'], $callbackUrl, "خرید {$plan['name']} - {$volumeGb}GB");
        
        if ($invoice['success']) {
            Database::query("UPDATE orders SET invoice_url=?, abangateway_invoice_id=?, status='pending' WHERE id=?", [$invoice['url'], $invoice['invoice_id']??null, $orderDbId]);
        } else {
            // حتی اگر آبان fail شد، سفارش ذخیره می‌ماند تا ادمین استعلام مجدد بزند
            Database::query("UPDATE orders SET panel_response=? WHERE id=?", [$invoice['message'] ?? json_encode($invoice, JSON_UNESCAPED_UNICODE), $orderDbId]);
        }
        
        return [
            'order_id'=>$orderId,
            'db_id'=>$orderDbId,
            'calc'=>$calc,
            'invoice'=>$invoice,
            'plan'=>$plan
        ];
    }

    // تأیید و تحویل - با Idempotency
    public static function deliverOrder(string $orderIdUnique): array {
        $order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderIdUnique]);
        if (!$order) return ['success'=>false,'message'=>'سفارش یافت نشد'];
        if ($order['status']==='completed') return ['success'=>true,'message'=>'قبلاً تحویل شده (Idempotent)','subscription'=>$order];

        // اگر تمدید است
        if ($order['is_renewal'] && $order['renew_subscription_id']) {
            $renew = UnifiedPanelManager::renewSubscription((int)$order['renew_subscription_id'], (float)$order['volume_gb'], (int)$order['duration_days']);
            if ($renew['success']) {
                // بروزرسانی اشتراک
                $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=?", [$order['renew_subscription_id']]);
                if ($sub) {
                    $newLimit = ($sub['data_limit_bytes'] ??0) + Helper::gbToBytes((float)$order['volume_gb']);
                    $newExpire = max(strtotime($sub['expire_at']??'now'), time()) + ($order['duration_days']*86400);
                    Database::query("UPDATE subscriptions SET data_limit_bytes=?, expire_at=FROM_UNIXTIME(?), status='active' WHERE id=?", [$newLimit, $newExpire, $sub['id']]);
                }
                Database::query("UPDATE orders SET status='completed', paid_at=NOW() WHERE order_id_unique=?", [$orderIdUnique]);
                // توکن
                TokenService::rewardPurchase((int)$order['telegram_id'], (int)$order['final_price']);
                return ['success'=>true,'message'=>'تمدید انجام شد','renew'=>$renew];
            } else {
                Database::query("UPDATE orders SET status='delivering', panel_response=? WHERE order_id_unique=?", [$renew['message']??'خطا تمدید', $orderIdUnique]);
                return ['success'=>false,'message'=>'خطا در تمدید: '.($renew['message']??'')];
            }
        }

        // ساخت جدید
        $delivery = UnifiedPanelManager::createFromPlan((int)$order['plan_id'], $order['username_config'], (float)$order['volume_gb'], (int)$order['duration_days']);
        if ($delivery['success']) {
            $subUrl = $delivery['subscription_url'] ?? '';
            $uuid = $delivery['uuid'] ?? '';
            $panelId = $delivery['panel_id'] ?? $order['config_group_id'];
            $bytes = Helper::gbToBytes((float)$order['volume_gb']);
            $expireAt = date('Y-m-d H:i:s', time() + $order['duration_days']*86400);
            Database::query("INSERT INTO subscriptions (user_id, telegram_id, order_id, panel_id, config_group_id, username, uuid, subscription_url, config_text, data_limit_bytes, used_traffic_bytes, expire_at, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", [
                $order['user_id'], $order['telegram_id'], $order['id'], $panelId, $order['config_group_id'], $order['username_config'], $uuid, $subUrl, $subUrl, $bytes, 0, $expireAt, 'active'
            ]);
            Database::query("UPDATE orders SET status='completed', paid_at=NOW(), panel_response=? WHERE order_id_unique=?", [json_encode($delivery, JSON_UNESCAPED_UNICODE), $orderIdUnique]);
            
            // افزایش used_count تخفیف
            if ($order['discount_code']) Database::query("UPDATE discounts SET used_count=used_count+1 WHERE code=?", [$order['discount_code']]);
            TokenService::rewardPurchase((int)$order['telegram_id'], (int)$order['final_price']);
            
            return ['success'=>true,'message'=>'سفارش تحویل شد','subscription_url'=>$subUrl,'delivery'=>$delivery];
        } else {
            Database::query("UPDATE orders SET status='delivering', panel_response=? WHERE order_id_unique=?", [$delivery['message']??'خطا', $orderIdUnique]);
            return ['success'=>false,'message'=>'خطا تحویل: '.($delivery['message']??'نامشخص'),'delivery'=>$delivery];
        }
    }

    // استعلام دستی پرداخت و سپس تحویل
    public static function verifyAndDeliver(string $orderIdUnique): array {
        $order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderIdUnique]);
        if (!$order) return ['success'=>false,'message'=>'سفارش یافت نشد'];
        if ($order['status']==='completed') return ['success'=>true,'message'=>'قبلاً تکمیل شده'];
        
        $aban = new AbanGateway();
        $verify = $aban->verifyInvoice($orderIdUnique, $order['abangateway_invoice_id']);
        if ($verify['paid'] ?? $verify['success'] ?? false) {
            Database::query("UPDATE orders SET status='paid' WHERE order_id_unique=?", [$orderIdUnique]);
            return self::deliverOrder($orderIdUnique);
        }
        return ['success'=>false,'message'=>'پرداخت هنوز تأیید نشده: '.($verify['message']?? $verify['status']??'')];
    }
}
