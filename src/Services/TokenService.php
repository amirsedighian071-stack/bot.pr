<?php
namespace Services;
use Core\Database;
use Core\Config;

class TokenService {
    public static function getBalance(int $telegramId): int {
        $u = Database::fetchOne("SELECT tokens FROM users WHERE telegram_id=?", [$telegramId]);
        return (int)($u['tokens'] ?? 0);
    }

    public static function addTokens(int $telegramId, int $amount, string $type, string $reason=''): void {
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$telegramId]);
        if (!$user) return;
        $new = (int)$user['tokens'] + $amount;
        Database::query("UPDATE users SET tokens=? WHERE telegram_id=?", [$new, $telegramId]);
        Database::query("INSERT INTO token_transactions (user_id, telegram_id, amount, type, reason, balance_after) VALUES (?,?,?,?,?,?)",
            [$user['id'], $telegramId, $amount, $type, $reason, $new]);
    }

    public static function deductTokens(int $telegramId, int $amount, string $reason=''): bool {
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$telegramId]);
        if (!$user || (int)$user['tokens'] < $amount) return false;
        $new = (int)$user['tokens'] - $amount;
        Database::query("UPDATE users SET tokens=? WHERE telegram_id=?", [$new, $telegramId]);
        Database::query("INSERT INTO token_transactions (user_id, telegram_id, amount, type, reason, balance_after) VALUES (?,?,?,?,?,?)",
            [$user['id'], $telegramId, -$amount, 'redeem', $reason, $new]);
        return true;
    }

    // پاداش خرید: هر 200,000 تومان = 1 توکن
    public static function rewardPurchase(int $telegramId, int $amountToman): void {
        $per = 200000;
        $tokens = intdiv($amountToman, $per);
        if ($tokens >0) self::addTokens($telegramId, $tokens, 'purchase_reward', "پاداش خرید {$amountToman} تومان");
    }

    // پاداش دعوت
    public static function rewardInvite(int $inviterTelegramId, int $invitedTelegramId): void {
        // جلوگیری از تکرار
        $exists = Database::fetchOne("SELECT id FROM token_transactions WHERE telegram_id=? AND type='invite' AND reason LIKE ?", [$inviterTelegramId, "%$invitedTelegramId%"]);
        if ($exists) return;
        $per = (int)Config::setting('token_per_invite','1');
        self::addTokens($inviterTelegramId, $per, 'invite', "دعوت کاربر $invitedTelegramId");
        Database::query("INSERT INTO referrals (inviter_id, invited_id, tokens_awarded) VALUES (?,?,?)", [$inviterTelegramId, $invitedTelegramId, $per]);
    }

    // بازخرید هدایا
    public static function redeemGift(int $telegramId, string $giftType, ?string $cardNumber=null, ?string $cardHolder=null): array {
        $costMap = [
            '5gb' => (int)Config::setting('gift_5gb_tokens','4'),
            '10gb' => (int)Config::setting('gift_10gb_tokens','7'),
            'cash45000' => (int)Config::setting('gift_cash_tokens','13'),
        ];
        $cost = $costMap[$giftType] ?? null;
        if ($cost===null) return ['success'=>false,'message'=>'نوع هدیه نامعتبر'];
        $bal = self::getBalance($telegramId);
        if ($bal < $cost) return ['success'=>false,'message'=>"توکن کافی ندارید. موجودی: $bal - نیاز: $cost"];

        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$telegramId]);
        if (!$user) return ['success'=>false,'message'=>'کاربر یافت نشد'];

        if (!self::deductTokens($telegramId, $cost, "بازخرید هدیه $giftType")) return ['success'=>false,'message'=>'خطا کسر توکن'];

        if (in_array($giftType, ['5gb','10gb'])) {
            // ساخت خودکار اشتراک هدیه - از گروه پیش‌فرض جوایز یا اولین گروه
            $giftGroupId = Config::setting('gift_group_id','');
            $group = null;
            if ($giftGroupId) $group = Database::fetchOne("SELECT * FROM config_groups WHERE id=?", [$giftGroupId]);
            if (!$group) $group = Database::fetchOne("SELECT * FROM config_groups WHERE is_active=1 LIMIT 1");
            if (!$group) return ['success'=>false,'message'=>'گروه کانفیگ جوایز تنظیم نشده'];

            $volume = $giftType==='5gb'?5:10;
            $username = 'gift_' . $telegramId . '_' . time();
            // استفاده از اولین پلن متصل به گروه یا ساخت مستقیم
            $plan = Database::fetchOne("SELECT * FROM plans WHERE config_group_id=? LIMIT 1", [$group['id']]);
            $panelId = $group['panel_id'];
            // مستقیم via UnifiedPanelManager
            $res = \Panels\UnifiedPanelManager::createFromPlan($plan['id'] ?? 0, $username, $volume, 30);
            // اگر پلن نداشت، سعی مستقیم driver
            if (!$res['success'] && !$plan) {
                $panelRow = Database::fetchOne("SELECT * FROM panels WHERE id=?", [$panelId]);
                $driver = \Panels\PanelFactory::create($panelRow);
                $res = $driver->createUser($username, \Core\Helper::gbToBytes($volume), time()+30*86400, $group['inbound_ids']);
                $res['panel_id']=$panelId;
            }
            if ($res['success']) {
                Database::query("INSERT INTO subscriptions (user_id, telegram_id, order_id, panel_id, config_group_id, username, uuid, subscription_url, data_limit_bytes, expire_at, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$user['id'], $telegramId, null, $res['panel_id']??$panelId, $group['id'], $username, $res['uuid']??'', $res['subscription_url'], \Core\Helper::gbToBytes($volume), date('Y-m-d H:i:s', time()+30*86400), 'active']);
                Database::query("INSERT INTO token_gifts (user_id, telegram_id, gift_type, tokens_cost, status) VALUES (?,?,?,?,?)", [$user['id'],$telegramId,$giftType,$cost,'approved']);
                return ['success'=>true,'message'=>"هدیه {$volume} گیگ با موفقیت فعال شد",'subscription_url'=>$res['subscription_url']];
            } else {
                // بازگشت توکن
                self::addTokens($telegramId, $cost, 'redeem', "بازگشت توکن - خطا ساخت هدیه");
                return ['success'=>false,'message'=>'خطا ساخت اشتراک هدیه: '.($res['message']??'')];
            }
        } else {
            // هدیه نقدی
            if (!$cardNumber || !$cardHolder) return ['success'=>false,'message'=>'شماره کارت و نام دارنده الزامی است'];
            Database::query("INSERT INTO token_gifts (user_id, telegram_id, gift_type, tokens_cost, card_number, card_holder, status) VALUES (?,?,?,?,?,?,?)",
                [$user['id'],$telegramId,$giftType,$cost,$cardNumber,$cardHolder,'pending']);
            return ['success'=>true,'message'=>'درخواست هدیه نقدی ثبت شد و پس از بررسی واریز خواهد شد'];
        }
    }
}
