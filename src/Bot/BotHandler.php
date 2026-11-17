<?php
namespace Bot;
use Core\Database;
use Core\Config;
use Core\Telegram;
use Core\Helper;
use Services\OrderService;
use Services\TokenService;
use Panels\UnifiedPanelManager;

class BotHandler {
    private Telegram $tg;
    private array $update;
    private ?int $chatId = null;
    private ?int $fromId = null;
    private ?string $text = null;

    public function __construct(array $update) {
        $this->update = $update;
        $this->tg = new Telegram();
        $msg = $update['message'] ?? $update['callback_query']['message'] ?? null;
        $this->chatId = $msg['chat']['id'] ?? $update['callback_query']['from']['id'] ?? null;
        $this->fromId = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? null;
        $this->text = $update['message']['text'] ?? null;
    }

    public function handle(): void {
        try {
            // ذخیره/آپدیت کاربر
            $this->ensureUser();

            if (isset($this->update['message'])) {
                $this->handleMessage();
            } elseif (isset($this->update['callback_query'])) {
                $this->handleCallback();
            }
        } catch (\Exception $e) {
            Helper::log("BotHandler error: ".$e->getMessage()."\n". $e->getTraceAsString(), 'bot.log');
        }
    }

    private function ensureUser(): void {
        if (!$this->fromId) return;
        $msg = $this->update['message'] ?? $this->update['callback_query'];
        $from = $msg['from'] ?? $this->update['callback_query']['from'] ?? null;
        if (!$from) return;
        $exists = Database::fetchOne("SELECT id FROM users WHERE telegram_id=?", [$this->fromId]);
        if (!$exists) {
            // بررسی رفرال
            $invitedBy = null;
            $txt = $this->text ?? '';
            if (str_starts_with($txt, '/start')) {
                $parts = explode(' ', $txt);
                if (isset($parts[1]) && is_numeric($parts[1]) && (int)$parts[1] !== $this->fromId) {
                    $invitedBy = (int)$parts[1];
                }
            }
            Database::query("INSERT INTO users (telegram_id, username, first_name, last_name, invited_by) VALUES (?,?,?,?,?)",
                [$this->fromId, $from['username']??null, $from['first_name']??null, $from['last_name']??null, $invitedBy]);
            // پاداش دعوت
            if ($invitedBy) {
                $inviter = Database::fetchOne("SELECT id FROM users WHERE telegram_id=?", [$invitedBy]);
                if ($inviter) TokenService::rewardInvite($invitedBy, $this->fromId);
            }
        } else {
            Database::query("UPDATE users SET last_seen_at=NOW(), username=?, first_name=? WHERE telegram_id=?", [$from['username']??null, $from['first_name']??null, $this->fromId]);
        }
    }

    private function handleMessage(): void {
        $msg = $this->update['message'];
        $text = trim($msg['text'] ?? '');
        $chatId = $this->chatId;

        // هندل فایل‌های پشتیبانی (عکس، ویس، ویدیو، سند)
        if (isset($msg['photo']) || isset($msg['voice']) || isset($msg['video']) || isset($msg['document'])) {
            $this->handleSupportMedia($msg);
            return;
        }

        // استیت‌ها (حجم دلخواه، نام کاربری، کد تخفیف، کارت)
        $state = Database::fetchOne("SELECT * FROM user_states WHERE telegram_id=?", [$this->fromId]);
        if ($state && $state['state']) {
            $this->handleState($state, $text, $msg);
            return;
        }

        if ($text === '/start' || str_starts_with($text, '/start ')) {
            $this->sendWelcome();
        } elseif ($text === '/help') {
            $this->tg->sendMessage($chatId, "برای استفاده از ربات از منوی شیشه‌ای استفاده کنید.", Keyboards::mainMenu());
        } else {
            // اگر متن آزاد در پشتیبانی باشد
            if ($text) {
                // چک اگر تیکت باز دارد، به عنوان پیام پشتیبانی ذخیره کن
                $openTicket = Database::fetchOne("SELECT * FROM tickets WHERE telegram_id=? AND status='open' ORDER BY id DESC LIMIT 1", [$this->fromId]);
                if ($openTicket) {
                    Database::query("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?,?,?,?)", [$openTicket['id'],'user',(string)$this->fromId,$text]);
                    $this->tg->sendMessage($chatId, "✅ پیام شما به پشتیبانی ارسال شد. منتظر پاسخ بمانید.", Keyboards::backToMain());
                    return;
                }
            }
            $this->sendWelcome();
        }
    }

    private function handleCallback(): void {
        $cq = $this->update['callback_query'];
        $data = $cq['data'] ?? '';
        $chatId = $this->chatId;
        $msgId = $cq['message']['message_id'] ?? null;
        $this->tg->answerCallbackQuery($cq['id']);

        // مسیریابی کال‌بک‌ها
        if ($data==='main_menu' || $data==='start') {
            $this->sendWelcome($msgId);
        } elseif ($data==='buy') {
            $this->showServices($msgId);
        } elseif (str_starts_with($data,'service_')) {
            $sid = (int)substr($data,8);
            $this->showPlans($sid, $msgId);
        } elseif (str_starts_with($data,'plan_')) {
            $pid = (int)substr($data,5);
            $this->showVolumes($pid, $msgId);
        } elseif (str_starts_with($data,'vol_') && !str_starts_with($data,'vol_custom_')) {
            // Check if renew flow
            $state = Database::fetchOne("SELECT * FROM user_states WHERE telegram_id=?", [$this->fromId]);
            if ($state && $state['state']==='await_renew_volume') {
                $parts = explode('_', $data);
                $gb = (float)$parts[2];
                $this->handleRenewVolume($gb, $chatId, $msgId);
                return;
            }
            // vol_{planId}_{gb}
            $parts = explode('_', $data);
            $pid = (int)$parts[1];
            $gb = (float)$parts[2];
            $this->askUsername($pid, $gb, $msgId);
        } elseif (str_starts_with($data,'vol_custom_')) {
            $pid = (int)substr($data,11);
            $state = Database::fetchOne("SELECT * FROM user_states WHERE telegram_id=?", [$this->fromId]);
            if ($state && $state['state']==='await_renew_volume') {
                $this->setState('await_renew_custom', ['plan_id'=>$pid, 'sub_id'=> json_decode($state['data_json']??'{}', true)['sub_id'] ?? 0]);
                $this->tg->sendMessage($chatId, "✏️ حجم دلخواه برای تمدید را به گیگابایت وارد کنید (مثلاً 25):", Keyboards::backToMain());
                return;
            }
            $this->setState('await_custom_volume', ['plan_id'=>$pid]);
            $this->tg->sendMessage($chatId, "✏️ حجم دلخواه را به گیگابایت وارد کنید (مثلاً 25):", Keyboards::backToMain());
        } elseif ($data==='rand_username') {
            $state = Database::fetchOne("SELECT * FROM user_states WHERE telegram_id=?", [$this->fromId]);
            $d = json_decode($state['data_json']??'{}', true);
            $rand = Helper::randomUsername('vpn');
            $d['username'] = $rand;
            Database::query("UPDATE user_states SET data_json=? WHERE telegram_id=?", [json_encode($d, JSON_UNESCAPED_UNICODE), $this->fromId]);
            $this->showInvoice($d, $chatId, $msgId);
        } elseif ($data==='trial') {
            $this->handleTrial($msgId);
        } elseif ($data==='my_subs') {
            $this->showMySubs($msgId);
        } elseif (str_starts_with($data,'sub_')) {
            $sid = (int)substr($data,4);
            $this->showSubDetail($sid, $msgId);
        } elseif (str_starts_with($data,'getlink_')) {
            $sid = (int)substr($data,8);
            $this->sendSubLink($sid);
        } elseif (str_starts_with($data,'qr_')) {
            $sid = (int)substr($data,3);
            $this->sendQr($sid);
        } elseif (str_starts_with($data,'renew_')) {
            $sid = (int)substr($data,6);
            $this->startRenew($sid, $msgId);
        } elseif ($data==='account') {
            $this->showAccount($msgId);
        } elseif ($data==='support') {
            $this->tg->editMessageText($chatId,$msgId,"🛟 بخش پشتیبانی:\nیکی از گزینه‌ها را انتخاب کنید:", Keyboards::supportMenu());
        } elseif ($data==='faq') {
            $faq = Config::setting('faq_text','سوالات متداول به زودی...');
            $this->tg->sendMessage($chatId, "❓ سوالات متداول:\n\n".$faq, Keyboards::backToMain());
        } elseif ($data==='new_ticket') {
            $this->setState('await_ticket_message', []);
            $this->tg->sendMessage($chatId, "💬 پیام خود را (متن، عکس، ویس، فایل) ارسال کنید تا به پشتیبانی منتقل شود:", Keyboards::backToMain());
        } elseif ($data==='manual_pay') {
            $this->setState('await_manual_pay', []);
            $this->tg->sendMessage($chatId, "💳 اگر پرداخت کردید اما تأیید نشده، لطفاً تصویر فیش و شماره سفارش (ORD-...) را ارسال کنید:", Keyboards::backToMain());
        } elseif ($data==='education') {
            $this->tg->editMessageText($chatId,$msgId,"📚 بخش آموزش:", Keyboards::educationMenu());
        } elseif ($data==='referral') {
            $this->showReferral($msgId);
        } elseif (str_starts_with($data,'gift_')) {
            $type = substr($data,5);
            $this->handleGift($type);
        } elseif (str_starts_with($data,'confirm_order_')) {
            $oid = substr($data,14);
            $this->confirmOrder($oid, $chatId, $msgId);
        } elseif (str_starts_with($data,'apply_discount_')) {
            $oid = substr($data,15);
            $this->setState('await_discount', ['order_id'=>$oid]);
            $this->tg->sendMessage($chatId, "🎟 کد تخفیف خود را وارد کنید:", Keyboards::backToMain());
        } elseif (str_starts_with($data,'cancel_order_')) {
            $oid = substr($data,13);
            Database::query("UPDATE orders SET status='failed' WHERE order_id_unique=?", [$oid]);
            $this->tg->editMessageText($chatId,$msgId,"❌ سفارش لغو شد.", Keyboards::mainMenu());
        } elseif (str_starts_with($data,'check_pay_')) {
            $oid = substr($data,10);
            $this->checkPayment($oid, $chatId, $msgId);
        } elseif ($data==='service_back') {
            $this->showServices($msgId);
        }
    }

    private function handleState(array $state, string $text, array $msg): void {
        $st = $state['state'];
        $data = json_decode($state['data_json']??'{}', true);

        if ($st==='await_custom_volume') {
            $gb = (float)$text;
            if ($gb <=0 || $gb>10000) {
                $this->tg->sendMessage($this->chatId, "❌ حجم نامعتبر. عددی بین 1 تا 10000 وارد کنید:");
                return;
            }
            $pid = (int)$data['plan_id'];
            $this->clearState();
            $this->askUsername($pid, $gb, null);
        } elseif ($st==='await_renew_custom') {
            $gb = (float)$text;
            if ($gb <=0 || $gb>10000) { $this->tg->sendMessage($this->chatId, "❌ حجم نامعتبر."); return; }
            $this->clearState();
            $this->handleRenewVolume($gb, $this->chatId, null);
        } elseif ($st==='await_renew_volume') {
            // حجم از طریق تایپ مستقیم برای تمدید
            $gb = (float)$text;
            if ($gb>0 && $gb<=10000) {
                $this->clearState();
                $this->handleRenewVolume($gb, $this->chatId, null);
                return;
            }
        } elseif ($st==='await_username') {
            $username = Helper::sanitizeUsername($text);
            if (!Helper::isValidUsername($username)) {
                $this->tg->sendMessage($this->chatId, "❌ نام کاربری نامعتبر. فقط حروف انگلیسی، عدد و _ (3-32 کاراکتر). دوباره وارد کنید:", Keyboards::usernameMenu());
                return;
            }
            // چک تکراری
            $exists = Database::fetchOne("SELECT id FROM subscriptions WHERE username=?", [$username]);
            if ($exists) {
                $this->tg->sendMessage($this->chatId, "❌ این نام کاربری قبلاً استفاده شده. نام دیگری وارد کنید یا تصادفی بزنید:", Keyboards::usernameMenu());
                return;
            }
            $data['username'] = $username;
            Database::query("UPDATE user_states SET data_json=? WHERE telegram_id=?", [json_encode($data, JSON_UNESCAPED_UNICODE), $this->fromId]);
            // نگه‌دار تا invoice نشان دهد
            $this->showInvoice($data, $this->chatId, null);
        } elseif ($st==='await_discount') {
            $code = trim($text);
            $orderId = $data['order_id'];
            $order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderId]);
            if (!$order) { $this->tg->sendMessage($this->chatId, "❌ سفارش یافت نشد"); $this->clearState(); return; }
            $calc = OrderService::calculatePrice((int)$order['plan_id'], (float)$order['volume_gb'], $code);
            if ($calc['discount_amount']<=0) {
                $this->tg->sendMessage($this->chatId, "❌ کد تخفیف نامعتبر یا منقضی شده.");
                return;
            }
            // اعمال
            Database::query("UPDATE orders SET discount_code=?, discount_amount=?, final_price=? WHERE order_id_unique=?", [$code, $calc['discount_amount'], $calc['final_price'], $orderId]);
            $this->clearState();
            // نمایش مجدد پیش‌فاکتور
            $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$order['plan_id']]);
            $service = Database::fetchOne("SELECT * FROM services WHERE id=?", [$plan['service_id']]);
            $msg = $this->renderInvoice($service['name']??'', $plan['name'], $order['volume_gb'], $order['username_config'], $calc['base_price'], $calc['fee'], $calc['discount_amount'], $calc['final_price']);
            $order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderId]);
            $kb = Keyboards::discountMenu($orderId);
            // اگر اینوویس قبلی دارد، به‌روزرسانی قیمت
            $this->tg->sendMessage($this->chatId, $msg . "\n\n✅ کد تخفیف اعمال شد!", $kb);
        } elseif ($st==='await_ticket_message') {
            $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
            // ایجاد تیکت اگر نیست
            $ticket = Database::fetchOne("SELECT * FROM tickets WHERE telegram_id=? AND status='open' ORDER BY id DESC LIMIT 1", [$this->fromId]);
            if (!$ticket) {
                Database::query("INSERT INTO tickets (user_id, telegram_id, subject, status) VALUES (?,?,?,?)", [$user['id'], $this->fromId, 'پشتیبانی', 'open']);
                $tid = (int)Database::lastInsertId();
            } else $tid = (int)$ticket['id'];
            Database::query("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?,?,?,?)", [$tid,'user',(string)$this->fromId,$text]);
            $this->clearState();
            $this->tg->sendMessage($this->chatId, "✅ پیام شما ثبت شد و پشتیبانی به زودی پاسخ می‌دهد.", Keyboards::mainMenu());
        } elseif ($st==='await_manual_pay') {
            // ذخیره به عنوان تیکت بررسی دستی
            $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
            Database::query("INSERT INTO tickets (user_id, telegram_id, subject, status) VALUES (?,?,?,?)", [$user['id'],$this->fromId,'پرداخت تأیید نشده - بررسی دستی','open']);
            $tid = (int)Database::lastInsertId();
            Database::query("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?,?,?,?)", [$tid,'user',(string)$this->fromId, $text]);
            $this->clearState();
            $this->tg->sendMessage($this->chatId, "✅ اطلاعات شما ثبت شد. ادمین به زودی بررسی می‌کند.", Keyboards::mainMenu());
        } elseif ($st==='await_cash_card') {
            $card = preg_replace('/\D/', '', $text);
            if (strlen($card) < 16) { $this->tg->sendMessage($this->chatId, "❌ شماره کارت نامعتبر. 16 رقم وارد کنید:"); return; }
            $data['card_number']=$card;
            $this->setState('await_cash_holder', $data);
            $this->tg->sendMessage($this->chatId, "👤 نام صاحب حساب را وارد کنید:");
        } elseif ($st==='await_cash_holder') {
            $holder = trim($text);
            if (mb_strlen($holder)<3) { $this->tg->sendMessage($this->chatId, "❌ نام نامعتبر:"); return; }
            $res = TokenService::redeemGift($this->fromId, 'cash45000', $data['card_number']??'', $holder);
            $this->clearState();
            $this->tg->sendMessage($this->chatId, ($res['success']?'✅ ':'❌ ').$res['message'], Keyboards::mainMenu());
        }
    }

    private function handleSupportMedia(array $msg): void {
        $fileId = null; $fileType='photo';
        if (isset($msg['photo'])) { $fileId = end($msg['photo'])['file_id']; $fileType='photo'; }
        elseif (isset($msg['voice'])) { $fileId=$msg['voice']['file_id']; $fileType='voice'; }
        elseif (isset($msg['video'])) { $fileId=$msg['video']['file_id']; $fileType='video'; }
        elseif (isset($msg['document'])) { $fileId=$msg['document']['file_id']; $fileType='document'; }

        $caption = $msg['caption'] ?? '';
        $dest = null;
        if ($fileId) {
            $f = $this->tg->getFile($fileId);
            if ($f && $f['ok']) {
                $filePath = $f['result']['file_path'];
                $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
                $destName = time().'_'.$this->fromId.'_'.bin2hex(random_bytes(4)).'.'.$ext;
                $dest = UPLOAD_PATH.'/tickets/'.$destName;
                $this->tg->downloadFile($filePath, $dest);
                $dest = 'uploads/tickets/'.$destName;
            }
        }
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
        if (!$user) return;
        // اگر استیت manual_pay یا ticket باشد
        $state = Database::fetchOne("SELECT * FROM user_states WHERE telegram_id=?", [$this->fromId]);
        $subject = 'پشتیبانی';
        if ($state && $state['state']==='await_manual_pay') $subject='پرداخت تأیید نشده';
        
        $ticket = Database::fetchOne("SELECT * FROM tickets WHERE telegram_id=? AND status='open' ORDER BY id DESC LIMIT 1", [$this->fromId]);
        if (!$ticket) {
            Database::query("INSERT INTO tickets (user_id, telegram_id, subject, status) VALUES (?,?,?,?)", [$user['id'],$this->fromId,$subject,'open']);
            $tid = (int)Database::lastInsertId();
        } else $tid=(int)$ticket['id'];
        Database::query("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, file_path, file_type) VALUES (?,?,?,?,?,?)",
            [$tid,'user',(string)$this->fromId,$caption ?: '[فایل]', $dest, $fileType]);
        $this->clearState();
        $this->tg->sendMessage($this->chatId, "✅ فایل شما به پشتیبانی ارسال شد.", Keyboards::mainMenu());
    }

    private function sendWelcome(?int $editMsgId=null): void {
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
        $tokens = $user['tokens'] ?? 0;
        $tpl = Config::setting('welcome_text','') ?: Database::fetchOne("SELECT text_value FROM bot_texts WHERE text_key='welcome'")['text_value'] ?? 'سلام {first_name} 👋';
        $msg = str_replace(['{first_name}','{user_id}','{username}','{tokens}'], [$user['first_name']??'', $this->fromId, $user['username']??'', $tokens], $tpl);
        $kb = Keyboards::mainMenu();
        if ($editMsgId) $this->tg->editMessageText($this->chatId,$editMsgId,$msg,$kb);
        else $this->tg->sendMessage($this->chatId,$msg,$kb);
    }

    private function showServices(?int $msgId): void {
        $kb = Keyboards::servicesMenu();
        $txt = "🛒 یک سرویس را انتخاب کنید:";
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
    }

    private function showPlans(int $serviceId, ?int $msgId): void {
        $kb = Keyboards::plansMenu($serviceId);
        $txt = "📋 پلن مورد نظر را انتخاب کنید:";
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
    }

    private function showVolumes(int $planId, ?int $msgId): void {
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$planId]);
        if (!$plan) { $this->tg->sendMessage($this->chatId,"❌ پلن یافت نشد"); return; }
        // اگر پلن حجم ثابت دارد، مستقیم به نام کاربری برو
        if ((float)$plan['volume_gb'] >0 && (int)$plan['price_per_gb']==0) {
            $this->askUsername($planId, (float)$plan['volume_gb'], $msgId);
            return;
        }
        $kb = Keyboards::volumesMenu($planId);
        $txt = "📦 حجم مورد نظر را انتخاب کنید (پلن: {$plan['name']}):";
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
    }

    private function askUsername(int $planId, float $volumeGb, ?int $msgId): void {
        $this->setState('await_username', ['plan_id'=>$planId, 'volume_gb'=>$volumeGb]);
        $txt = "👤 نام کاربری کانفیگ را وارد کنید (انگلیسی 3-32 کاراکتر) یا دکمه تصادفی را بزنید:";
        $kb = Keyboards::usernameMenu();
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
    }

    private function showInvoice(array $data, int $chatId, ?int $msgId): void {
        $planId = (int)$data['plan_id'];
        $volume = (float)$data['volume_gb'];
        $username = $data['username'] ?? Helper::randomUsername('vpn');
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$planId]);
        $service = Database::fetchOne("SELECT * FROM services WHERE id=?", [$plan['service_id']]);
        $calc = OrderService::calculatePrice($planId, $volume);

        // ثبت سفارش موقت pending
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
        $orderId = Helper::generateOrderId();
        // به جای createOrder کامل، اینجا دستی می‌سازیم تا کد تخفیف بعداً اعمال شود
        // اما برای سادگی از OrderService استفاده می‌کنیم و بعد کد تخفیف را هندل می‌کنیم
        // اینجا order ایجاد می‌کنیم با قیمت نهایی
        $callbackUrl = Helper::appUrl('public/callback.php?order_id='.$orderId);
        $customCb = Config::setting('abangateway_callback_url','');
        if ($customCb) $callbackUrl = rtrim($customCb,'/').'?order_id='.$orderId;

        Database::query("INSERT INTO orders (order_id_unique, user_id, telegram_id, service_id, plan_id, config_group_id, username_config, volume_gb, duration_days, price, fee, discount_amount, final_price, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$orderId, $user['id'], $this->fromId, $plan['service_id'], $planId, $plan['config_group_id'], $username, $volume, $plan['duration_days'], $calc['base_price'], $calc['fee'], 0, $calc['final_price'], 'pending']);
        
        // ایجاد فاکتور آبان
        $aban = new \Payment\AbanGateway();
        $inv = $aban->createInvoice($orderId, (int)$calc['final_price'], $callbackUrl, "خرید {$plan['name']}");
        if ($inv['success']) {
            Database::query("UPDATE orders SET invoice_url=?, abangateway_invoice_id=? WHERE order_id_unique=?", [$inv['url'],$inv['invoice_id']??null,$orderId]);
        }

        $txt = $this->renderInvoice($service['name']??'', $plan['name'], $volume, $username, $calc['base_price'], $calc['fee'], 0, $calc['final_price']);
        $txt .= "\n\n" . Config::setting('payment_before_text','برای پرداخت روی ثبت سفارش کلیک کنید.');

        // دو مرحله: ابتدا پیش‌فاکتور با دکمه‌های ثبت/تخفیف/لغو
        $kb = Keyboards::discountMenu($orderId);
        // اگر اینوویس داریم، دکمه پرداخت هم اضافه کن؟ spec: پس از ثبت سفارش اینوویس نمایش داده شود
        // اینجا پیش‌فاکتور را نشان می‌دهیم
        $this->clearState(); // state username پاک شد
        // ذخیره order_id برای ادامه
        $this->tg->sendMessage($chatId, $txt, $kb);
        if ($msgId) {
            // حذف پیام قبلی اگر بود
        }
    }

    private function renderInvoice(string $service, string $plan, $volume, string $username, int $price, int $fee, int $discount, int $final): string {
        $tpl = Database::fetchOne("SELECT text_value FROM bot_texts WHERE text_key='order_summary'")['text_value'] ?? "سفارش {service} - {plan}";
        return str_replace(['{service}','{plan}','{volume}','{username}','{price}','{fee}','{discount}','{final_price}'],
            [$service,$plan,$volume,$username,number_format($price),number_format($fee),number_format($discount),number_format($final)], $tpl) .
            "\n\n🧾 سفارش:\n▪️ سرویس: $service\n▪️ پلن: $plan\n▪️ حجم: $volume گیگ\n▪️ کاربر: $username\n▪️ قیمت: ".number_format($price)." ت\n▪️ کارمزد: ".number_format($fee)." ت\n▪️ تخفیف: ".number_format($discount)." ت\n▪️ نهایی: ".number_format($final)." تومان";
    }

    private function confirmOrder(string $orderId, int $chatId, ?int $msgId): void {
        $order = Database::fetchOne("SELECT * FROM orders WHERE order_id_unique=?", [$orderId]);
        if (!$order) { $this->tg->sendMessage($chatId,"❌ سفارش یافت نشد"); return; }
        if ($order['invoice_url']) {
            $txt = Config::setting('payment_caption','') ?: "💳 برای پرداخت روی دکمه زیر کلیک کنید:\n⚠️ پس از پرداخت، دکمه بررسی وضعیت را بزنید.\n\n💰 مبلغ: ".number_format($order['final_price'])." تومان\n🆔 سفارش: $orderId";
            $kb = Keyboards::invoiceMenu($orderId, $order['invoice_url']);
            if ($msgId) $this->tg->editMessageText($chatId,$msgId,$txt,$kb);
            else $this->tg->sendMessage($chatId,$txt,$kb);
        } else {
            // تلاش مجدد ساخت فاکتور
            $aban = new \Payment\AbanGateway();
            $cb = Helper::appUrl('public/callback.php?order_id='.$orderId);
            $inv = $aban->createInvoice($orderId, (int)$order['final_price'], $cb);
            if ($inv['success']) {
                Database::query("UPDATE orders SET invoice_url=? WHERE order_id_unique=?", [$inv['url'],$orderId]);
                $kb = Keyboards::invoiceMenu($orderId, $inv['url']);
                $txt = "💳 لینک پرداخت ایجاد شد. مبلغ: ".number_format($order['final_price'])." تومان";
                $this->tg->sendMessage($chatId,$txt,$kb);
            } else {
                $this->tg->sendMessage($chatId,"❌ خطا ایجاد لینک پرداخت: ".($inv['message']??''), Keyboards::backToMain());
            }
        }
    }

    private function checkPayment(string $orderId, int $chatId, ?int $msgId): void {
        $this->tg->sendMessage($chatId,"🔍 در حال بررسی وضعیت پرداخت $orderId ...");
        $res = OrderService::verifyAndDeliver($orderId);
        if ($res['success']) {
            $subUrl = $res['subscription_url'] ?? $res['delivery']['subscription_url'] ?? '';
            $txt = "✅ پرداخت تأیید و اشتراک تحویل شد!\n\n🔗 لینک اتصال:\n<code>$subUrl</code>\n\nبرای مشاهده QR به اشتراک من بروید.";
            $this->tg->sendMessage($chatId,$txt, Keyboards::mainMenu());
            // ارسال به پنل لاگ
            if ($subUrl) {
                // QR via api.qrserver
                $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=".urlencode($subUrl);
                $this->tg->sendPhoto($chatId,$qr,"📷 QR Code");
            }
        } else {
            $this->tg->sendMessage($chatId,"❌ ".$res['message']."\n\nاگر پرداخت کرده‌اید اما تأیید نشده، از بخش «پرداخت کردم اما تأیید نشده» فیش ارسال کنید.", Keyboards::invoiceMenu($orderId, Database::fetchOne("SELECT invoice_url FROM orders WHERE order_id_unique=?",[$orderId])['invoice_url']??''));
        }
    }

    private function handleTrial(?int $msgId): void {
        $trialUsed = Database::fetchOne("SELECT id FROM free_trials WHERE telegram_id=?", [$this->fromId]);
        if ($trialUsed) {
            $msg = Config::setting('trial_already_text','') ?: "❌ شما قبلاً تست رایگان دریافت کرده‌اید!";
            if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$msg, Keyboards::backToMain());
            else $this->tg->sendMessage($this->chatId,$msg,Keyboards::backToMain());
            return;
        }
        // ظرفیت روزانه
        $todayCount = Database::fetchOne("SELECT COUNT(*) as c FROM free_trials WHERE DATE(created_at)=CURDATE()")['c'] ?? 0;
        $limit = (int)Config::setting('trial_daily_limit','0');
        if ($limit>0 && $todayCount >= $limit) {
            $this->tg->sendMessage($this->chatId,"❌ ظرفیت تست امروز تکمیل شده. فردا تلاش کنید.",Keyboards::backToMain());
            return;
        }

        $groupId = Config::setting('trial_group_id','');
        $group = $groupId ? Database::fetchOne("SELECT * FROM config_groups WHERE id=?", [$groupId]) : Database::fetchOne("SELECT * FROM config_groups WHERE is_active=1 LIMIT 1");
        if (!$group) { $this->tg->sendMessage($this->chatId,"❌ گروه تست تنظیم نشده. با پشتیبانی تماس بگیرید.",Keyboards::backToMain()); return; }
        $panelRow = Database::fetchOne("SELECT * FROM panels WHERE id=?", [$group['panel_id']]);
        $username = 'Test'.$this->fromId.'_'.rand(100,999);
        $volMb = (int)Config::setting('trial_volume_mb','100');
        $days = (int)Config::setting('trial_days','1');
        $bytes = $volMb * 1024 * 1024;
        $expire = time()+$days*86400;
        $driver = \Panels\PanelFactory::create($panelRow);
        $res = $driver->createUser($username, $bytes, $expire, $group['inbound_ids']?:$group['group_identifier']);
        if ($res['success']) {
            $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
            Database::query("INSERT INTO subscriptions (user_id, telegram_id, panel_id, config_group_id, username, uuid, subscription_url, data_limit_bytes, expire_at, status) VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$user['id'],$this->fromId,$panelRow['id'],$group['id'],$username,$res['uuid']??'',$res['subscription_url'],$bytes,date('Y-m-d H:i:s',$expire),'active']);
            $subId = (int)Database::lastInsertId();
            Database::query("INSERT INTO free_trials (telegram_id, subscription_id) VALUES (?,?)", [$this->fromId,$subId]);
            Database::query("UPDATE users SET is_trial_used=1 WHERE telegram_id=?", [$this->fromId]);
            $tpl = Database::fetchOne("SELECT text_value FROM bot_texts WHERE text_key='trial_success'")['text_value'] ?? "تست فعال شد {username}";
            $txt = str_replace(['{username}','{volume}','{expire}','{sub_url}'], [$username, $volMb/1024, $days, $res['subscription_url']], $tpl);
            $txt = "✅ تست رایگان فعال شد!\n\n👤 $username\n📦 ".($volMb/1024)." گیگ\n⏳ $days روز\n\n🔗 لینک:\n<code>{$res['subscription_url']}</code>";
            $this->tg->sendMessage($this->chatId,$txt,Keyboards::backToMain());
        } else {
            $this->tg->sendMessage($this->chatId,"❌ خطا ساخت تست: ".($res['message']??''),Keyboards::backToMain());
        }
    }

    private function showMySubs(?int $msgId): void {
        $kb = Keyboards::mySubscriptionsMenu($this->fromId);
        $txt = "📦 5 اشتراک اخیر شما:";
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
    }

    private function showSubDetail(int $subId, ?int $msgId): void {
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=? AND telegram_id=?", [$subId,$this->fromId]);
        if (!$sub) { $this->tg->sendMessage($this->chatId,"❌ اشتراک یافت نشد"); return; }
        // سعی دریافت اطلاعات زنده از پنل
        $live = UnifiedPanelManager::getUserInfo($subId);
        if ($live) {
            $used = Helper::formatBytes((int)($live['used_traffic']??0));
            $total = Helper::formatBytes((int)($live['data_limit']??$sub['data_limit_bytes']));
            $expire = isset($live['expire']) ? date('Y-m-d H:i', (int)$live['expire']) : $sub['expire_at'];
            $subUrl = $live['subscription_url'] ?? $sub['subscription_url'];
        } else {
            $used = Helper::formatBytes((int)$sub['used_traffic_bytes']);
            $total = Helper::formatBytes((int)$sub['data_limit_bytes']);
            $expire = $sub['expire_at'];
            $subUrl = $sub['subscription_url'];
        }
        $txt = "📦 اشتراک: {$sub['username']}\n📊 مصرف: $used / $total\n⏳ انقضا: $expire\n🔗 لینک:\n<code>$subUrl</code>";
        $kb = Keyboards::subscriptionDetailMenu($subId);
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
    }

    private function sendSubLink(int $subId): void {
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=? AND telegram_id=?", [$subId,$this->fromId]);
        if (!$sub) return;
        $this->tg->sendMessage($this->chatId,"🔗 لینک اشتراک {$sub['username']}:\n\n<code>{$sub['subscription_url']}</code>");
    }

    private function sendQr(int $subId): void {
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=? AND telegram_id=?", [$subId,$this->fromId]);
        if (!$sub) return;
        $qr = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=".urlencode($sub['subscription_url']);
        $this->tg->sendPhoto($this->chatId,$qr,"📷 QR برای {$sub['username']}");
    }

    private function startRenew(int $subId, ?int $msgId): void {
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=? AND telegram_id=?", [$subId,$this->fromId]);
        if (!$sub) return;
        // پیدا کردن پلن مرتبط: از order یا نزدیک‌ترین پلن همان گروه
        $plan = null;
        if ($sub['order_id']) {
            $order = Database::fetchOne("SELECT plan_id FROM orders WHERE id=?", [$sub['order_id']]);
            if ($order) $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$order['plan_id']]);
        }
        if (!$plan) $plan = Database::fetchOne("SELECT * FROM plans WHERE config_group_id=? LIMIT 1", [$sub['config_group_id']]);
        if (!$plan) { $this->tg->sendMessage($this->chatId,"❌ پلن تمدید یافت نشد"); return; }
        // نمایش حجم‌ها برای تمدید
        $kb = Keyboards::volumesMenu((int)$plan['id']);
        // برای تمدید، استیت جدا
        $this->setState('await_renew_volume', ['sub_id'=>$subId, 'plan_id'=>$plan['id']]);
        $txt = "🔄 تمدید اشتراک {$sub['username']}\nحجم اضافه را انتخاب کنید:";
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
        // توجه: جریان تمدید حجم دلخواه مشابه قبل هندل می‌شود اما در handleState باید renew را هم پوشش داد
        // برای سادگی، اگر کاربر حجم انتخاب کند، در handleCallback renew حجم را مستقیم می‌سازیم
        // override: اگر استیت renew باشد، vol انتخاب = ساخت سفارش تمدید
    }

    private function showAccount(?int $msgId): void {
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
        $inviteCount = Database::fetchOne("SELECT COUNT(*) as c FROM referrals WHERE inviter_id=?", [$this->fromId])['c'] ?? 0;
        $txt = "👤 حساب کاربری:\n\n🆔 شناسه: {$this->fromId}\n👤 نام: {$user['first_name']}\n🔹 یوزرنیم: @".($user['username']??'-')."\n🪙 توکن: {$user['tokens']}\n👥 دعوت موفق: $inviteCount\n📅 عضویت: {$user['created_at']}\n🕒 استعلام: ".date('Y-m-d H:i:s');
        $kb = Keyboards::backToMain();
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
    }

    private function showReferral(?int $msgId): void {
        $user = Database::fetchOne("SELECT * FROM users WHERE telegram_id=?", [$this->fromId]);
        $botUsername = Config::setting('bot_username','');
        if (!$botUsername) {
            $info = $this->tg->request('getMe');
            $botUsername = $info['result']['username'] ?? 'yourbot';
            Config::setSetting('bot_username',$botUsername);
        }
        $link = "https://t.me/{$botUsername}?start={$this->fromId}";
        $inviteCount = Database::fetchOne("SELECT COUNT(*) as c FROM referrals WHERE inviter_id=?", [$this->fromId])['c'] ?? 0;
        $tpl = Database::fetchOne("SELECT text_value FROM bot_texts WHERE text_key='referral_text'")['text_value'] ?? '';
        $txt = str_replace(['{invite_link}','{invite_count}','{tokens}'], [$link,$inviteCount,$user['tokens']], $tpl);
        if (!$tpl) $txt = "🎁 لینک دعوت:\n$link\n\n👥 دعوت: $inviteCount\n🪙 توکن: {$user['tokens']}";
        // اگر استیت renew حجم بود، اینجا نمایش نده
        $kb = Keyboards::referralMenu((int)$user['tokens']);
        if ($msgId) $this->tg->editMessageText($this->chatId,$msgId,$txt,$kb);
        else $this->tg->sendMessage($this->chatId,$txt,$kb);
        // بنر عکس اگر تنظیم شده
        $banner = Config::setting('referral_banner','');
        if ($banner && filter_var($banner, FILTER_VALIDATE_URL)) {
            $this->tg->sendPhoto($this->chatId,$banner,"🎁 با دعوت دوستان توکن بگیرید!");
        }
    }

    private function handleGift(string $type): void {
        if ($type==='cash') {
            $this->setState('await_cash_card', []);
            $this->tg->sendMessage($this->chatId, "💳 شماره کارت 16 رقمی خود را وارد کنید:");
            return;
        }
        $giftMap = ['5gb'=>'5gb','10gb'=>'10gb'];
        $giftType = $giftMap[$type] ?? null;
        if (!$giftType) return;
        $res = TokenService::redeemGift($this->fromId, $giftType);
        $this->tg->sendMessage($this->chatId, ($res['success']?'✅ ':'❌ ').$res['message'] . (isset($res['subscription_url'])?"\n\n🔗 ".$res['subscription_url']:''), Keyboards::mainMenu());
    }

    private function handleRenewVolume(float $gb, int $chatId, ?int $msgId): void {
        $state = Database::fetchOne("SELECT * FROM user_states WHERE telegram_id=?", [$this->fromId]);
        $d = $state ? json_decode($state['data_json']??'{}', true) : [];
        $subId = (int)($d['sub_id'] ?? 0);
        if (!$subId) { $this->tg->sendMessage($chatId, "❌ خطا تمدید: اشتراک یافت نشد"); $this->clearState(); return; }
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE id=? AND telegram_id=?", [$subId, $this->fromId]);
        if (!$sub) { $this->tg->sendMessage($chatId, "❌ اشتراک یافت نشد"); return; }
        $planId = (int)($d['plan_id'] ?? 0);
        if (!$planId) $planId = (int)(Database::fetchOne("SELECT id FROM plans WHERE config_group_id=? LIMIT 1", [$sub['config_group_id']])['id'] ?? 0);
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id=?", [$planId]);
        // ساخت سفارش تمدید
        $order = OrderService::createOrder($this->fromId, $planId, $gb, $sub['username'], null, true, $subId);
        $this->clearState();
        $txt = "🔄 پیش‌فاکتور تمدید:\n▪️ اشتراک: {$sub['username']}\n▪️ حجم اضافه: {$gb} گیگ\n▪️ مبلغ نهایی: ".number_format($order['calc']['final_price'])." تومان";
        $kb = \Bot\Keyboards::discountMenu($order['order_id']);
        $this->tg->sendMessage($chatId, $txt, $kb);
    }

    private function setState(string $state, array $data): void {
        Database::query("INSERT INTO user_states (telegram_id, state, data_json) VALUES (?,?,?) ON DUPLICATE KEY UPDATE state=VALUES(state), data_json=VALUES(data_json)", [$this->fromId,$state,json_encode($data, JSON_UNESCAPED_UNICODE)]);
    }
    private function clearState(): void {
        Database::query("DELETE FROM user_states WHERE telegram_id=?", [$this->fromId]);
    }
    // رندر کمکی invoice متنی فارسی
}
