# AVA VPN — پنل مدیریت React (Dark Luxe)

> ری‌دیزاین کامل فرانت پنل PHP → **React + Vite + Tailwind + Framer Motion + Lucide**  
> تم **تاریک مطلق** (void #050507 / slate 900 #0f172a / 111827) · کارت‌های شیشه‌ای · نئون ملایم · گوشه‌های 20-22px · سایه soft · انیمیشن 60fps

## ویژگی‌ها (مطابق درخواست)

1. **React کامپوننتی** — تمام صفحات به کامپوننت‌های کوچک تقسیم شده (`src/components`, `src/pages`) با `react-router-dom` و state مدیریت‌شده. به راحتی روی PHP سوار می‌شود (SPA مجزا یا embed).
2. **Dark Only** — هیچ تم روشنی وجود ندارد. پالت: `#050507` بک‌گراند، `#0f172a` کارت دیپ، `#111827` سایدبار، گرادینت بنفش `violet-600 → indigo-600` برای اکسنت و نئون (`box-shadow: 0 0 20px rgba(139,92,246,0.35)`).
3. **Micro-animations** — `framer-motion` برای: هاور کارت‌ها (`y: -3`), باز/بسته آکاردئون سایدبار (`height` spring), تغییر صفحه (`AnimatePresence`), دکمه‌ها (`whileTap scale 0.97`), لود چارت‌ها.
4. **آیکون و المان لوکس** — `lucide-react` جایگزین `bootstrap-icons`؛ کارت‌ها `rounded-[20px]`، بوردر `white/[0.06]`، `backdrop-blur-[18px]`، سایه `0 8px 32px rgba(0,0,0,0.35)`.
5. **کاملاً ریسپانسیو** — سایدبار `300px` دسکتاپ ثابت، موبایل `translate-x` + overlay + `backdrop-blur`. گریدها: `grid-cols-1 sm:grid-cols-2 xl:grid-cols-4` و `overflow-x-auto` برای جداول.
6. **خروجی تمیز** — `src/` تایپ‌شده (TS), `lib/utils.ts` (`cn`), `components/ui.tsx` (Card/Button/Badge/Input...), هر صفحه یک فایل.

## ساختار

```
frontend/
├── index.html
├── vite.config.ts          # proxy -> PHP :8080
├── tailwind.config.js      # پالت void/slate/neon
├── src/
│   ├── main.tsx
│   ├── App.tsx             # Routes
│   ├── index.css           # glass, shimmer, scrollbar
│   ├── lib/utils.ts
│   ├── components/
│   │   ├── Layout.tsx      # سایدبار آکاردئونی + تاپبار شیشه‌ای + AnimatePresence
│   │   └── ui.tsx          # Card, Button (spring), Badge, Input, Select...
│   └── pages/
│       ├── Dashboard.tsx   # 4 stat کارت گرادینت، AreaChart (recharts), سلامت پنل‌ها
│       ├── Orders.tsx      # فیلتر چیپ، سرچ، جدول هوور، Idempotent badge
│       ├── Users.tsx
│       ├── Plans.tsx       # فرم گروه پنل Override (Outbound) + هشدار amber
│       ├── Panels.tsx      # کارت پنل + تست/همگام‌سازی
│       ├── Groups.tsx
│       ├── Services.tsx
│       ├── Subscriptions.tsx
│       ├── Discounts.tsx
│       ├── Tickets.tsx
│       ├── Reports.tsx     # BarChart
│       ├── Broadcast.tsx
│       ├── SettingsBot.tsx
│       ├── SettingsGeneral.tsx
│       ├── Warehouse.tsx / Volumes.tsx / Backups.tsx
│       └── Login.tsx       # شیشه‌ای فول‌اسکرین + گرادینت بلور
└── dist/                   # خروجی build (برای deploy روی PHP)
```

## اجرا

```bash
cd frontend
npm install
npm run dev      # http://localhost:5173  (proxy به PHP)
npm run build    # خروجی در frontend/dist
npm run preview  # http://localhost:4173
```

### اتصال به بک‌اند PHP

دو روش:

**A) پنل مجزا (توصیه)** — `frontend/dist` را در `public/admin-react/` کپی کنید و یک فال‌بک PHP بسازید:

```php
// public/admin-react/index.php
// هر مسیر غیر فایل را به frontend/dist/index.html بده
$dist = __DIR__ . '/../../frontend/dist';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (file_exists($dist . $uri) && is_file($dist . $uri)) return false;
readfile($dist . '/index.html');
```

سپس API های JSON را از مسیر `admin/api/*.php` صدا بزنید:

```ts
fetch('/admin/api/dashboard.php', { credentials: 'include' })
  .then(r => r.json())
  .then(setStats)
```

نمونه API آماده در `admin/api/react/` (این ریپو).

**B) Embed در PHP** — در هر `admin/*.php` فقط `<div id="root"></div>` و `<script src="/frontend/dist/assets/index-*.js">` بگذارید.

### API نمونه (موجود)

- `GET /admin/api/dashboard.php` → `{ users, orders, revenue, subs, tickets }`
- `GET /admin/api/orders.php?status=&q=` → `orders[]`
- `POST /admin/api/orders.php` (verifyAndDeliver)

همه API ها `Auth::check()` و `$_SESSION` را چک می‌کنند، پس کوکی PHP کافی است.

## تم

```js
// tailwind.config.js
colors: {
  void: '#050507',
  abyss: '#08080c',
  slate: { 900:'#0f172a', 850:'#111827' },
  neon: { 500:'#8b5cf6', 600:'#7c3aed' }
}
```

کارت: `bg-[rgba(22,22,26,0.78)] backdrop-blur-[18px] border-white/[0.07] rounded-[20px]`

دکمه primary: `bg-gradient-to-l from-violet-600 to-indigo-600 shadow-[0_8px_20px_rgba(124,58,237,0.35)]`

## نکات پیاده‌سازی

- RTL کامل (`dir="rtl"` + Vazirmatn + `text-right` در جداول)
- هیچ `light-mode` کلاسی استفاده نشده — فقط dark
- جداول `overflow-x-auto` + `hover:bg-white/[0.04]`
- مودال‌ها با `framer-motion` (در `ui.tsx` می‌توان `Dialog` اضافه کرد)
- چارت‌ها `recharts` با گرادینت `linearGradient` و `Tooltip` شیشه‌ای

---

ساخته شده برای `amirsedighian071-stack/bot.pr` — شاخه `arena/8020e92e-bot-pr`
