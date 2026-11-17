<?php
namespace Bot;
use Core\Telegram;
use Core\Database;

class Keyboards {
    public static function mainMenu(): array {
        // چیدمان استاندارد 5 ردیفه - بدون تکرار ستونی
        $fallback = [
            [Telegram::btn('خرید اشتراک','buy')],
            [Telegram::btn('تست رایگان','trial')],
            [Telegram::btn('حساب کاربری','account'), Telegram::btn('اشتراک من','my_subs')],
            [Telegram::btn('پشتیبانی','support'), Telegram::btn('آموزش','education')],
            [Telegram::btn('زیرمجموعه‌گیری','referral')],
        ];
        try {
            $rows = Database::fetchAll("SELECT * FROM bot_menus WHERE is_active=1 ORDER BY row_num ASC, col_num ASC");
            if ($rows && count($rows) >= 5) {
                $grouped = [];
                foreach ($rows as $r) {
                    $rn = (int)($r['row_num'] ?? 0);
                    if($rn < 1) continue;
                    $grouped[$rn][] = $r;
                }
                if(count($grouped) === 5){
                    ksort($grouped);
                    $kb = [];
                    foreach ($grouped as $row) {
                        usort($row, fn($a,$b)=> (int)$a['col_num'] <=> (int)$b['col_num']);
                        $line=[];
                        foreach ($row as $btn) {
                            if(empty($btn['label']) || empty($btn['callback_data'])) continue;
                            $label = trim($btn['label']);
                            // حذف ایموجی اضافی اگر در DB مانده باشد، فقط متن تمیز
                            $line[] = Telegram::btn($label, $btn['callback_data']);
                        }
                        if($line) $kb[]=$line;
                    }
                    // اعتبارسنجی: باید دقیقا 5 ردیف با ساختار 1-1-2-2-1 باشد
                    if(count($kb)===5 && count($kb[0])===1 && count($kb[1])===1 && count($kb[2])===2 && count($kb[3])===2 && count($kb[4])===1){
                        return Telegram::inlineKeyboard($kb);
                    }
                }
            }
        } catch (\Throwable $e) {}
        return Telegram::inlineKeyboard($fallback);
    }

    public static function backToMain(): array {
        return Telegram::inlineKeyboard([[Telegram::btn('🏠 بازگشت به منوی اصلی','main_menu')]]);
    }

    public static function servicesMenu(): array {
        $services = Database::fetchAll("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order");
        $rows=[];
        foreach ($services as $s) $rows[] = [Telegram::btn($s['name'], 'service_'.$s['id'])];
        $rows[] = [Telegram::btn('🔙 بازگشت','main_menu')];
        return Telegram::inlineKeyboard($rows);
    }

    public static function plansMenu(int $serviceId): array {
        $plans = Database::fetchAll("SELECT * FROM plans WHERE service_id=? AND is_active=1 ORDER BY sort_order", [$serviceId]);
        $rows=[];
        foreach ($plans as $p) $rows[] = [Telegram::btn($p['name'].' - '.number_format($p['price']).' ت', 'plan_'.$p['id'])];
        $rows[] = [Telegram::btn('🔙 بازگشت','buy')];
        return Telegram::inlineKeyboard($rows);
    }

    public static function volumesMenu(int $planId): array {
        $vols = Database::fetchAll("SELECT * FROM volumes WHERE is_active=1 ORDER BY sort_order");
        $rows=[];
        foreach ($vols as $v) $rows[] = [Telegram::btn($v['label'], 'vol_'.$planId.'_'. $v['gb'])];
        $rows[] = [Telegram::btn('✏️ وارد کردن حجم دلخواه','vol_custom_'.$planId)];
        $rows[] = [Telegram::btn('🔙 بازگشت','service_back')];
        return Telegram::inlineKeyboard($rows);
    }

    public static function usernameMenu(): array {
        return Telegram::inlineKeyboard([
            [Telegram::btn('🎲 انتخاب تصادفی','rand_username')],
            [Telegram::btn('🔙 انصراف','main_menu')]
        ]);
    }

    public static function invoiceMenu(string $orderId, string $invoiceUrl): array {
        return Telegram::inlineKeyboard([
            [Telegram::btn('💳 پرداخت آنلاین / کارت به کارت','', $invoiceUrl)],
            [Telegram::btn('🔍 بررسی وضعیت پرداخت','check_pay_'.$orderId)],
            [Telegram::btn('❌ لغو سفارش','cancel_order_'.$orderId)]
        ]);
    }

    public static function mySubscriptionsMenu(int $telegramId): array {
        $subs = Database::fetchAll("SELECT * FROM subscriptions WHERE telegram_id=? ORDER BY id DESC LIMIT 5", [$telegramId]);
        $rows=[];
        foreach ($subs as $s) $rows[] = [Telegram::btn("{$s['username']} | ".round($s['data_limit_bytes']/1073741824,1)."GB", 'sub_'.$s['id'])];
        if (!$rows) $rows[] = [Telegram::btn('شما اشتراکی ندارید','main_menu')];
        else $rows[] = [Telegram::btn('🔙 بازگشت','main_menu')];
        return Telegram::inlineKeyboard($rows);
    }

    public static function subscriptionDetailMenu(int $subId): array {
        return Telegram::inlineKeyboard([
            [Telegram::btn('🔗 دریافت لینک','getlink_'.$subId), Telegram::btn('📷 QR','qr_'.$subId)],
            [Telegram::btn('🔄 تمدید اشتراک','renew_'.$subId)],
            [Telegram::btn('🔙 بازگشت','my_subs')]
        ]);
    }

    public static function supportMenu(): array {
        return Telegram::inlineKeyboard([
            [Telegram::btn('❓ سوالات متداول','faq')],
            [Telegram::btn('💬 گفتگو با پشتیبانی','new_ticket')],
            [Telegram::btn('💳 پرداخت کردم اما تأیید نشده','manual_pay')],
            [Telegram::btn('🔙 بازگشت','main_menu')]
        ]);
    }

    public static function educationMenu(): array {
        $edu = \Core\Config::setting('education_link','https://t.me/');
        $appLink = \Core\Config::setting('app_download_link','https://t.me/');
        return Telegram::inlineKeyboard([
            [Telegram::btn('📚 آموزش‌ها','', $edu)],
            [Telegram::btn('📥 دریافت برنامه','', $appLink)],
            [Telegram::btn('🔙 بازگشت','main_menu')]
        ]);
    }

    public static function referralMenu(int $tokens): array {
        $cost5 = \Core\Config::setting('gift_5gb_tokens','4');
        $cost10 = \Core\Config::setting('gift_10gb_tokens','7');
        $costCash = \Core\Config::setting('gift_cash_tokens','13');
        return Telegram::inlineKeyboard([
            [Telegram::btn("🎁 5 گیگ ({$cost5} توکن)",'gift_5gb'), Telegram::btn("🎁 10 گیگ ({$cost10} توکن)",'gift_10gb')],
            [Telegram::btn("💰 هدیه نقدی 45,000 تومان ({$costCash} توکن)",'gift_cash')],
            [Telegram::btn('🔙 بازگشت','main_menu')]
        ]);
    }

    public static function discountMenu(string $orderId): array {
        return Telegram::inlineKeyboard([
            [Telegram::btn('✅ ثبت سفارش','confirm_order_'.$orderId)],
            [Telegram::btn('🎟 اعمال کد تخفیف','apply_discount_'.$orderId)],
            [Telegram::btn('❌ لغو سفارش','cancel_order_'.$orderId)]
        ]);
    }
}
