-- ============================================
-- VPN Bot - Complete MySQL Schema (PHP 8.2+)
-- Optimized for Cloud Hosting, InnoDB, utf8mb4
-- ============================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- تنظیمات کلی سیستم (Key-Value)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ادمین‌های پنل وب
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin','finance','support','server') DEFAULT 'super_admin',
  `display_name` VARCHAR(100) NULL,
  `last_login_at` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کاربران تلگرام
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `telegram_id` BIGINT NOT NULL UNIQUE,
  `username` VARCHAR(100) NULL,
  `first_name` VARCHAR(100) NULL,
  `last_name` VARCHAR(100) NULL,
  `tokens` INT DEFAULT 0,
  `invited_by` BIGINT NULL,
  `is_blocked` TINYINT(1) DEFAULT 0,
  `is_trial_used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at` DATETIME NULL,
  `metadata` JSON NULL,
  INDEX `idx_invited_by` (`invited_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- وضعیت موقت کاربر برای جریان خرید (State Machine)
CREATE TABLE IF NOT EXISTS `user_states` (
  `telegram_id` BIGINT PRIMARY KEY,
  `state` VARCHAR(50) NULL,
  `data_json` JSON NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- سرویس‌ها (دسته‌بندی کلی)
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- پنل‌های VPN (چندپنلی)
CREATE TABLE IF NOT EXISTS `panels` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('pasarguard','marzban','marzneshin','xui_single','xui_multi','rebecca','remnawave','guard','wgdashboard','manual') NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `username` VARCHAR(100) NULL,
  `password` VARCHAR(255) NULL,
  `token` TEXT NULL,
  `api_key` VARCHAR(255) NULL,
  `proxies` TEXT NULL COMMENT 'JSON proxy_settings for PasarGuard/Marzban - e.g. {"vless":{},"vmess":{}}',
  `inbounds` TEXT NULL COMMENT 'JSON group_ids or inbound tags',
  `pasarguard_auth_mode` VARCHAR(20) DEFAULT 'api_key' COMMENT 'pasarguard: api_key or password',
  `timeout` INT DEFAULT 10,
  `is_active` TINYINT(1) DEFAULT 1,
  `renew_method` ENUM('append','reset') DEFAULT 'append',
  `capabilities_json` JSON NULL,
  `last_sync_at` DATETIME NULL,
  `last_error` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- گروه‌های کانفیگ اختصاصی (هر گروه به یک پنل + Inbound/Group متصل است)
CREATE TABLE IF NOT EXISTS `config_groups` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `panel_id` INT UNSIGNED NOT NULL,
  `inbound_ids` VARCHAR(255) NULL COMMENT 'comma separated ids or json',
  `group_identifier` VARCHAR(100) NULL COMMENT 'service name or group name in panel',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`panel_id`) REFERENCES `panels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- پلن‌ها
CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_id` INT UNSIGNED NOT NULL,
  `config_group_id` INT UNSIGNED NOT NULL,
  `group_ids` VARCHAR(255) NULL COMMENT 'Override panel group_ids for this plan (e.g. Ava test id) - if empty uses config_groups inbound_ids',
  `name` VARCHAR(100) NOT NULL,
  `price` INT NOT NULL COMMENT 'price in Toman',
  `price_per_gb` INT DEFAULT 0 COMMENT 'for custom volume',
  `duration_days` INT NOT NULL DEFAULT 30,
  `volume_gb` DECIMAL(10,2) DEFAULT 0 COMMENT '0 = custom',
  `is_renewable` TINYINT(1) DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`config_group_id`) REFERENCES `config_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- حجم‌های پیش‌فرض
CREATE TABLE IF NOT EXISTS `volumes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(50) NOT NULL,
  `gb` DECIMAL(10,2) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کدهای تخفیف
CREATE TABLE IF NOT EXISTS `discounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('percent','fixed') NOT NULL,
  `value` DECIMAL(10,2) NOT NULL,
  `max_uses` INT DEFAULT 0 COMMENT '0 = unlimited',
  `used_count` INT DEFAULT 0,
  `min_amount` INT DEFAULT 0,
  `plan_id` INT UNSIGNED NULL,
  `apply_on_fee` TINYINT(1) DEFAULT 0,
  `expire_at` DATETIME NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- سفارشات
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id_unique` VARCHAR(50) NOT NULL UNIQUE COMMENT 'ORD-XXXX',
  `user_id` INT UNSIGNED NOT NULL,
  `telegram_id` BIGINT NOT NULL,
  `service_id` INT UNSIGNED NULL,
  `plan_id` INT UNSIGNED NULL,
  `config_group_id` INT UNSIGNED NULL,
  `username_config` VARCHAR(100) NOT NULL,
  `volume_gb` DECIMAL(10,2) NOT NULL,
  `duration_days` INT NOT NULL,
  `price` INT NOT NULL COMMENT 'base price Toman',
  `fee` INT DEFAULT 3000,
  `discount_amount` INT DEFAULT 0,
  `discount_code` VARCHAR(50) NULL,
  `final_price` INT NOT NULL COMMENT 'Toman',
  `status` ENUM('pending','paid','failed','delivering','completed','manual_review') DEFAULT 'pending',
  `payment_gateway` VARCHAR(20) DEFAULT 'abangateway',
  `invoice_url` TEXT NULL,
  `abangateway_invoice_id` VARCHAR(100) NULL,
  `panel_response` TEXT NULL,
  `is_renewal` TINYINT(1) DEFAULT 0,
  `renew_subscription_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `paid_at` DATETIME NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_telegram` (`telegram_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اشتراک‌ها
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `telegram_id` BIGINT NOT NULL,
  `order_id` INT UNSIGNED NULL,
  `panel_id` INT UNSIGNED NOT NULL,
  `config_group_id` INT UNSIGNED NULL,
  `username` VARCHAR(100) NOT NULL,
  `uuid` VARCHAR(100) NULL,
  `subscription_url` TEXT NULL,
  `config_text` TEXT NULL,
  `data_limit_bytes` BIGINT DEFAULT 0,
  `used_traffic_bytes` BIGINT DEFAULT 0,
  `expire_at` DATETIME NULL,
  `status` ENUM('active','expired','disabled','deleted') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_telegram` (`telegram_id`),
  INDEX `idx_username` (`username`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`panel_id`) REFERENCES `panels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- انبار کانفیگ دستی
CREATE TABLE IF NOT EXISTS `warehouse_configs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `config_group_id` INT UNSIGNED NOT NULL,
  `config_text` TEXT NOT NULL,
  `is_used` TINYINT(1) DEFAULT 0,
  `used_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`config_group_id`) REFERENCES `config_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تیکت‌ها
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `telegram_id` BIGINT NOT NULL,
  `subject` VARCHAR(255) DEFAULT 'پشتیبانی',
  `status` ENUM('open','answered','closed','pending') DEFAULT 'open',
  `priority` ENUM('low','medium','high') DEFAULT 'medium',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_telegram` (`telegram_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('user','admin') NOT NULL,
  `sender_id` VARCHAR(50) NULL,
  `message` TEXT NULL,
  `file_path` VARCHAR(255) NULL,
  `file_type` VARCHAR(50) NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- لاگ پرداخت آبان‌گیت
CREATE TABLE IF NOT EXISTS `abangateway_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id_unique` VARCHAR(50) NULL,
  `abangateway_invoice_id` VARCHAR(100) NULL,
  `amount_rial` BIGINT NULL,
  `amount_toman` INT NULL,
  `status` VARCHAR(50) NULL,
  `response_json` JSON NULL,
  `callback_json` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order` (`order_id_unique`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تراکنش توکن
CREATE TABLE IF NOT EXISTS `token_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `telegram_id` BIGINT NOT NULL,
  `amount` INT NOT NULL COMMENT 'positive or negative',
  `type` ENUM('invite','purchase_reward','redeem','gift_cash','admin_adjust') NOT NULL,
  `reason` VARCHAR(255) NULL,
  `order_id` VARCHAR(50) NULL,
  `balance_after` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- درخواست هدیه نقدی
CREATE TABLE IF NOT EXISTS `token_gifts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `telegram_id` BIGINT NOT NULL,
  `gift_type` VARCHAR(50) NOT NULL COMMENT '5gb,10gb,cash45000',
  `tokens_cost` INT NOT NULL,
  `card_number` VARCHAR(30) NULL,
  `card_holder` VARCHAR(100) NULL,
  `status` ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
  `admin_note` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تست رایگان
CREATE TABLE IF NOT EXISTS `free_trials` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `telegram_id` BIGINT NOT NULL UNIQUE,
  `subscription_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- پیام همگانی
CREATE TABLE IF NOT EXISTS `broadcasts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NULL,
  `message` TEXT NOT NULL,
  `media_path` VARCHAR(255) NULL,
  `media_type` VARCHAR(20) NULL,
  `target_filter` VARCHAR(50) DEFAULT 'all' COMMENT 'all, buyers, no_sub',
  `status` ENUM('draft','sending','completed','failed') DEFAULT 'draft',
  `sent_count` INT DEFAULT 0,
  `total_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- لاگ ادمین
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تنظیمات متن‌های ربات
CREATE TABLE IF NOT EXISTS `bot_texts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `text_key` VARCHAR(100) NOT NULL UNIQUE,
  `text_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- منوی شیشه‌ای قابل ویرایش
CREATE TABLE IF NOT EXISTS `bot_menus` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `row_num` INT NOT NULL,
  `col_num` INT NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `callback_data` VARCHAR(100) NOT NULL,
  `emoji` VARCHAR(20) NULL,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- مقادیر پیش‌فرض
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('bot_token', ''),
('bot_username', ''),
('admin_telegram_id', ''),
('webhook_secret', ''),
('webhook_url', ''),
('abangateway_token', ''),
('abangateway_callback_url', ''),
('fee_amount', '3000'),
('trial_volume_mb', '100'),
('trial_days', '1'),
('trial_group_id', ''),
('token_per_invite', '1'),
('token_per_200k_purchase', '1'),
('gift_5gb_tokens', '4'),
('gift_10gb_tokens', '7'),
('gift_cash_tokens', '13'),
('gift_cash_amount', '45000'),
('support_channel_link', 'https://t.me/'),
('education_link', 'https://t.me/'),
('faq_text', 'سوالات متداول در اینجا نمایش داده می‌شود.')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO `volumes` (`label`, `gb`, `sort_order`) VALUES
('10 گیگ', 10, 1),
('30 گیگ', 30, 2),
('50 گیگ', 50, 3),
('100 گیگ', 100, 4),
('200 گیگ', 200, 5)
ON DUPLICATE KEY UPDATE label=VALUES(label);

INSERT INTO `bot_texts` (`text_key`, `text_value`) VALUES
('welcome', 'سلام {first_name} عزیز 👋\nبه ربات فروش VPN خوش آمدید!\n\n🔹 شناسه شما: {user_id}\n🔹 توکن: {tokens}\n\nاز منوی زیر انتخاب کنید:'),
('buy_guide', '🛒 برای خرید اشتراک، ابتدا سرویس مورد نظر را انتخاب کنید.'),
('trial_success', '✅ تست رایگان شما با موفقیت فعال شد!\n\n👤 نام کاربری: {username}\n📦 حجم: {volume} گیگ\n⏳ اعتبار: {expire} روز\n\n🔗 لینک اتصال:\n{sub_url}'),
('trial_already', '❌ شما قبلاً تست رایگان خود را دریافت کرده‌اید!'),
('order_summary', '🧾 پیش‌فاکتور سفارش:\n\n▪️ سرویس: {service}\n▪️ پلن: {plan}\n▪️ حجم: {volume} گیگ\n▪️ نام کاربری: {username}\n▪️ قیمت پایه: {price} تومان\n▪️ کارمزد: {fee} تومان\n▪️ تخفیف: {discount} تومان\n▪️ مبلغ نهایی: {final_price} تومان\n\nبرای ادامه ثبت سفارش را بزنید.'),
('payment_caption', '💳 برای پرداخت روی دکمه زیر کلیک کنید:\n⚠️ پس از پرداخت، دکمه «بررسی وضعیت پرداخت» را بزنید.'),
('payment_success', '✅ پرداخت شما با موفقیت تأیید شد!\n\n👤 نام کاربری: {username}\n🔗 لینک اتصال:\n{sub_url}\n\nاز خرید شما سپاسگزاریم!'),
('referral_text', '🎁 لینک دعوت اختصاصی شما:\n{invite_link}\n\n👥 دعوت موفق: {invite_count} نفر\n🪙 توکن فعلی: {tokens}\n\n💡 به ازای هر دعوت 1 توکن و هر 200 هزار تومان خرید 1 توکن هدیه بگیرید!')
ON DUPLICATE KEY UPDATE text_value=VALUES(text_value);

INSERT INTO `bot_menus` (`row_num`, `col_num`, `label`, `callback_data`, `emoji`, `is_active`) VALUES
(1, 1, 'خرید اشتراک', 'buy', '🛒', 1),
(2, 1, 'تست رایگان', 'trial', '🎁', 1),
(3, 1, 'حساب کاربری', 'account', '👤', 1),
(3, 2, 'اشتراک من', 'my_subs', '📦', 1),
(4, 1, 'پشتیبانی', 'support', '🛟', 1),
(4, 2, 'آموزش', 'education', '📚', 1),
(5, 1, 'زیرمجموعه‌گیری', 'referral', '👥', 1)
ON DUPLICATE KEY UPDATE label=VALUES(label);
