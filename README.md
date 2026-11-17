# VPN Bot - ربات فروش VPN با معماری Faoxima (PHP 8.2+ | Responsive | Cloud Optimized)

پروژه کامل **ربات تلگرام فروش اشتراک VPN** + **پنل مدیریت تحت وب ریسپانسیو** با الهام از مخزن **Mmd-Amir/Faoxima** و ماژولار Adapter Pattern. بهینه برای هاست ابری (PHP 8.2+, MySQL, HTTPS).

---

## ✨ ویژگی‌ها

### 🤖 ربات تلگرام (Inline Keyboard 100%)
- **منوی شیشه‌ای 5 ردیفی** (قابل ویرایش از پنل):
  - ردیف1: خرید اشتراک | ردیف2: تست رایگان | ردیف3: حساب کاربری|اشتراک من | ردیف4: پشتیبانی|آموزش | ردیف5: زیرمجموعه‌گیری
- **خرید هوشمند**: سرویس → پلن → حجم (پیش‌فرض + دلخواه با فرمول `(قیمت هر گیگ × گیگ) + کارمزد 3000`) → نام کاربری (اعتبارسنجی + تصادفی + استعلام تکراری) → پیش‌فاکتور → کد تخفیف → فاکتور آبان‌گیت → تحویل خودکار
- **آبان‌گیت (abangateway.ir)**: ارسال `order_id` یکتا + مبلغ به **ریال** (`تومان×10`) + `callback_url` + `Bearer Token` + تأیید دو مرحله‌ای (Callback + Verify) + جلوگیری از پردازش تکراری (Idempotency)
- **تست رایگان**: محدود 1 بار هر `telegram_id` + سقف روزانه + گروه اختصاصی
- **اشتراک من**: 5 خرید اخیر با دکمه، نمایش مصرف/انقضا، لینک + QR، **تمدید** (افزودن حجم/زمان به همان کانفیگ)
- **پشتیبانی 100% از پنل وب**: ارسال متن/عکس/ویس/ویدیو/فایل → نمایش در چت پنل → پاسخ مستقیم به تلگرام
- **زیرمجموعه‌گیری & توکن**: لینک `t.me/bot?start=ID`، 1 توکن هر دعوت + 1 توکن هر 200,000 تومان، جوایز 5GB/4T، 10GB/7T، نقدی 45,000/13T (کسر توکن، تحویل خودکار یا درخواست کارت)

### 🖥️ پنل مدیریت ریسپانسیو (Mobile-First, Bootstrap 5 RTL)
- **داشبورد**: آمار کاربران، درآمد، سفارشات، تیکت، نمودار فروش
- **تنظیمات ربات & وب‌هوک**: وارد کردن توکن + آیدی ادمین + Secret + دکمه **تنظیم خودکار وب‌هوک** (بدون ترمینال) + نمایش `getWebhookInfo`
- **Integrations Hub**: آبان‌گیت (توکن + Callback URL + تست + لاگ) + **مدیریت چندپنلی** (افزودن پنل با انتخاب نوع: پاسارگاد/مرزبان/مرزنشین/3x-ui/ربکا/رمناویو/گارد/WGDashboard/انبار دستی + تست + همگام‌سازی گروه‌ها)
- **گروه‌های کانفیگ**: ربط پلن به `panel_id + inbound_ids` (الگوی Faoxima)
- **سرویس/پلن/کد تخفیف/سفارش/اشتراک/کاربر**: CRUD کامل، فیلتر، جستجو، **استعلام مجدد آبان** و **تحویل مجدد**
- **تیکت**: چت مدرن با نمایش فایل، پاسخ با آپلود، ارسال به تلگرام
- **پیام همگانی**: ارسال به همه/خریداران/بدون اشتراک + آپلود مدیا
- **گزارش مالی & بکاپ**: نمودار، خروجی SQL، لاگ‌ها

### 🔌 معماری چندپنلی (Adapter Pattern - Faoxima)
```
PanelInterface
  ├─ authenticate() / getToken() (با Cache 6-12 ساعت)
  ├─ createUser(username, bytes, expire, groupIds)
  ├─ getUser / renewUser / deleteUser / toggleUserStatus
  └─ getGroupsOrInbounds() + PanelCapabilities Matrix
```
`UnifiedPanelManager` لایه یکپارچه برای ساخت/تمدید از روی `plan → group → panel → driver`

پشتیبانی کامل:
- **PasarGuard** (REST + Bearer)
- **Marzban** (`/api/admin/token` + Inbounds)
- **Marzneshin** (`/api/admins/token` + Services)
- **3x-ui** (Session Cookie + `/panel/api/inbounds`)
- **Rebecca / Remnawave / Guard / WGDashboard / Manual (انبار)**

Timeout + Retry + کش توکن برای پایداری ابری.

---

## 📁 ساختار پروژه
```
├── admin/               # پنل وب
│   ├── index.php        # داشبورد
│   ├── login.php
│   ├── layout/          # هدر/سایدبار ریسپانسیو
│   ├── settings/bot.php # ست وب‌هوک
│   ├── integrations/    # آبان‌گیت + پنل‌ها
│   ├── groups/ plans/ services/
│   ├── orders/ subscriptions/ users/
│   ├── tickets/ (view + gifts)
│   ├── discounts/ broadcast/ backups/ reports/
├── config/
│   ├── config.php       # کانفیگ اصلی (از install ساخته می‌شود)
│   ├── config.php.example
│   └── constants.php
├── database/schema.sql  # اسکیما کامل (22 جدول)
├── public/
│   ├── webhook.php      # وب‌هوک تلگرام (سریع، fastcgi_finish_request)
│   ├── callback.php     # کال‌بک آبان‌گیت (Verify + Deliver + Notify)
│   ├── cron.php         # هشدار انقضا + منقضی‌سازی + retry
│   ├── assets/css/js    # استایل Vazirmatn + RTL
│   └── uploads/tickets/
├── src/
│   ├── Core/            # Database, Config, Telegram, Auth, Cache, Helper
│   ├── Panels/          # Interface, Factory, Capabilities, UnifiedManager, Drivers/*
│   ├── Payment/AbanGateway.php
│   ├── Services/OrderService, TokenService
│   └── Bot/BotHandler, Keyboards
├── install/index.php    # نصب گرافیکی
├── .htaccess
└── logs/                # لاگ‌ها
```

---

## 🚀 نصب گام‌به‌گام روی هاست ابری

### 1) آپلود
فایل‌ها را در `public_html` یا ساب‌دامنه آپلود کنید. مطمئن شوید PHP 8.2+ و `pdo_mysql, curl, mbstring, json` فعال هستند. HTTPS الزامی است.

### 2) دیتابیس
یک دیتابیس MySQL بسازید (مثلاً `vpn_bot`). سپس:
- مرورگر: `https://yourdomain.com/install/` 
- فرم را پر کنید (هاست DB، نام، یوزر، رمز، آدرس سایت، ادمین پنل) → **نصب** → جداول + ادمین ساخته می‌شود.
- یا دستی: `mysql -u user -p vpn_bot < database/schema.sql` و `cp config/config.php.example config/config.php` را ویرایش کنید.

### 3) ست وب‌هوک (بدون ترمینال)
1. به `ادمین → تنظیمات ربات و وب‌هوک` بروید.
2. **توکن ربات** (از @BotFather) و **آیدی عددی ادمین** را وارد کنید.
3. **تنظیم خودکار وب‌هوک** را بزنید → سیستم `https://yourdomain.com/public/webhook.php` را با `secret_token` به تلگرام می‌فرستد.
4. تست: به ربات `/start` بدهید.

> دستی (اختیاری): `curl -F "url=https://yourdomain.com/public/webhook.php" -F "secret_token=YOUR_SECRET" https://api.telegram.org/bot<TOKEN>/setWebhook`

### 4) اتصال آبان‌گیت
1. از `abangateway.ir` پنل بگیرید و `ABAN_TOKEN` را کپی کنید.
2. در `ادمین → آبان‌گیت` توکن + `Callback URL` (پیش‌فرض `public/callback.php`) را وارد کنید → **تست اتصال**.
3. مبلغ به **ریال** ارسال می‌شود، نیاز به تنظیم دیگر نیست.

### 5) افزودن پنل VPN
1. `ادمین → پنل‌های VPN → افزودن`
2. نوع (مثلاً Marzban)، نام، URL (مثلاً `https://panel.example.com`), یوزر/پس، Timeout
3. **تست اتصال** → **همگام‌سازی گروه‌ها** (لیست Inbounds/Services را می‌گیرد)
4. `گروه‌های کانفیگ` بسازید (مثلاً VIP مرزبان → پنل مرزبان + Inbound IDs = `1,2`)
5. `سرویس‌ها` و `پلن‌ها` را بسازید و هر پلن را به **گروه** وصل کنید (قیمت، هر گیگ، مدت، حجم)

### 6) تنظیمات نهایی
- `تنظیمات عمومی`: کارمزد، تست (100MB/1روز)، توکن جوایز، لینک آموزش/دانلود، متن‌ها، منوساز
- `کد تخفیف`، `کاربران`، `پیام همگانی`
- `cron.php`: در هاست → Cron Jobs → هر 5 دقیقه:
  ```
  curl -s https://yourdomain.com/public/cron.php?secret=YOUR_CRON_SECRET >/dev/null 2>&1
  ```
  (SECRET را در `settings` → `cron_secret` بگذارید)

### 7) امنیت & نگهداری
- پس از نصب پوشه `install/` را **حذف** کنید.
- `logs/` را قابل نوشتن (755) کنید.
- `.htaccess` از دسترسی به `config, src, database` جلوگیری می‌کند.
- از `بکاپ` → دانلود منظم بگیرید.
- لاگ‌ها در `admin/api/logs.php` قابل مشاهده‌اند.

---

## 🔐 امنیت تراکنش (Idempotency)
- هر سفارش `order_id_unique = ORD-HEX-TIME` دارد.
- `callback.php` و `check_pay` هر دو `verifyInvoice` می‌کنند و `amount` را چک می‌کنند.
- اگر `status=completed` بود، دوباره تحویل نمی‌دهد.
- توکن منفی نمی‌شود، تست 1 بار، کد تخفیف `used_count` کنترل می‌شود.
- تمام درخواست‌های cURL با Timeout 5-15s و کش توکن.

---

## 🧪 تست محلی (بدون هاست)
```bash
php -S localhost:8000 -t public/ public/webhook.php
# در تب دیگر:
curl -X POST http://localhost:8000/webhook.php -H "Content-Type: application/json" -d '{"message":{"chat":{"id":123},"from":{"id":123,"first_name":"Test"},"text":"/start"}}'
```

---

## 📚 مستندات آبان‌گیت
- `POST https://api.abangateway.ir/api/v1/invoices`
- Header: `Authorization: Bearer $ABAN_TOKEN`
- Body: `{"amount": 100000, "order_id": "ORD-...", "callback_url": "https://.../public/callback.php?order_id=ORD-...", "description": "..."}`
- پاسخ: `{"payment_url": "https://..."}`
- Verify: `POST /invoices/verify` با `order_id`

---

## 📝 لایسنس
MIT - برای توسعه شخصی/تجاری آزاد است.

> ساخته شده با ❤️ برای هاست ابری، PHP 8.2+, معماری Faoxima, پنل ریسپانسیو و پرداخت خودکار.
