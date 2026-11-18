import { motion } from 'framer-motion'
import { Users, CreditCard, Receipt, MessageCircleMore, HardDrive, TrendingUp, ArrowUpRight, Zap, Activity } from 'lucide-react'
import { Card, CardHeader, Badge, Button } from '../components/ui'
import { AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'

const stats = [
  { label: 'کاربران کل', value: '12,847', sub: '+342 این هفته', icon: Users, gradient: 'from-violet-600 via-violet-600 to-indigo-600', glow: 'shadow-[0_12px_30px_rgba(124,58,237,0.35)]' },
  { label: 'درآمد کل', value: '۸۹۲,۴۰۰,۰۰۰ ت', sub: '+12.4% نسبت به ماه قبل', icon: CreditCard, gradient: 'from-emerald-600 to-teal-600', glow: 'shadow-[0_12px_30px_rgba(16,185,129,0.30)]' },
  { label: 'سفارش موفق', value: '3,421 / 3,892', sub: 'نرخ موفقیت 87.9%', icon: Receipt, gradient: 'from-amber-500 to-orange-600', glow: 'shadow-[0_12px_30px_rgba(245,158,11,0.30)]' },
  { label: 'تیکت باز', value: '28', sub: 'نیاز به پاسخ فوری', icon: MessageCircleMore, gradient: 'from-rose-600 to-pink-600', glow: 'shadow-[0_12px_30px_rgba(225,29,72,0.30)]' },
]

const chartData = [
  { name: 'شنبه', v: 12000000 },
  { name: 'یک‌شنبه', v: 19000000 },
  { name: 'دوشنبه', v: 15000000 },
  { name: 'سه‌شنبه', v: 22000000 },
  { name: 'چهارشنبه', v: 18000000 },
  { name: 'پنج‌شنبه', v: 25000000 },
  { name: 'جمعه', v: 21000000 },
]

export default function Dashboard() {
  return (
    <div className="w-full min-w-0 max-w-full space-y-6 overflow-x-hidden">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div className="min-w-0">
          <h1 className="truncate text-[22px] font-black tracking-tight text-white">داشبورد</h1>
          <p className="break-safe text-sm text-zinc-500">نمای کلی فروش، کاربران و سلامت پنل‌ها — بروزرسانی زنده · بدون بیرون‌زدگی</p>
        </div>
        <div className="flex shrink-0 flex-wrap items-center gap-2">
          <Badge tone="violet" className="gap-1.5"><span className="h-2 w-2 shrink-0 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]" /> آنلاین · Dark Neon #0b0f19</Badge>
          <Badge tone="zinc">PHP 8.2+</Badge>
        </div>
      </div>

      <div className="grid w-full min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {stats.map((s, i) => (
          <motion.div
            key={s.label}
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.06, duration: 0.4, ease: [0.32, 0.72, 0, 1] }}
            whileHover={{ y: -3 }}
            className={`group relative min-w-0 overflow-hidden rounded-[22px] bg-gradient-to-br ${s.gradient} p-[1px] ${s.glow}`}
          >
            <div className="relative rounded-[21px] bg-gradient-to-br from-black/20 to-black/5 p-5 backdrop-blur">
              <div className="absolute -left-6 -top-6 h-20 w-20 rounded-full bg-white/10 blur-2xl group-hover:bg-white/15 transition-colors" />
              <div className="flex min-w-0 items-start justify-between gap-3">
                <div className="min-w-0">
                  <div className="truncate text-xs font-bold tracking-wide text-white/70">{s.label}</div>
                  <div className="mt-2 truncate text-[20px] font-black leading-none text-white sm:text-[22px]">{s.value}</div>
                  <div className="mt-2 inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold text-white backdrop-blur"><TrendingUp className="h-3 w-3 shrink-0" /> <span className="truncate">{s.sub}</span></div>
                </div>
                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur ring-1 ring-white/10"><s.icon className="h-5 w-5" /></div>
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      <div className="grid w-full min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
        <Card className="min-w-0 xl:col-span-8 overflow-hidden">
          <CardHeader>
            <div className="flex min-w-0 items-center gap-2 font-black text-white truncate"><Activity className="h-4 w-4 shrink-0 text-violet-400" /> نمودار فروش ۷ روز اخیر</div>
            <Badge tone="emerald" className="shrink-0 gap-1"><ArrowUpRight className="h-3 w-3" /> +12.4%</Badge>
          </CardHeader>
          <div className="h-[260px] w-full min-w-0 p-2">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData}>
                <defs><linearGradient id="g2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor="#8b5cf6" stopOpacity={0.35} /><stop offset="100%" stopColor="#8b5cf6" stopOpacity={0} /></linearGradient></defs>
                <XAxis dataKey="name" tick={{ fill: '#71717a', fontSize: 11 }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fill: '#71717a', fontSize: 11 }} axisLine={false} tickLine={false} tickFormatter={(v: number) => `${(v/1000000).toFixed(0)}M`} width={38} />
                <Tooltip contentStyle={{ background: 'rgba(11,15,25,0.96)', border: '1px solid rgba(255,255,255,0.08)', borderRadius: 16, backdropFilter: 'blur(12px)', color: '#fff' }} formatter={(v: any) => [`${(v as number).toLocaleString('fa-IR')} تومان`, 'فروش']} />
                <Area type="monotone" dataKey="v" stroke="#8b5cf6" strokeWidth={2.5} fill="url(#g2)" dot={false} activeDot={{ r: 5, fill: '#8b5cf6', stroke: '#fff', strokeWidth: 2 }} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <div className="min-w-0 space-y-6 xl:col-span-4">
          <Card className="min-w-0 overflow-hidden">
            <CardHeader><div className="flex items-center gap-2 font-black text-white truncate"><Zap className="h-4 w-4 shrink-0 text-amber-400" /> دسترسی سریع</div></CardHeader>
            <div className="grid grid-cols-2 gap-2 p-4">
              {[
                { label: 'ست وب‌هوک', desc: 'Telegram', href: '/settings/bot' },
                { label: 'افزودن پنل', desc: 'Outbound', href: '/panels' },
                { label: 'پلن جدید', desc: 'Ava Test', href: '/plans' },
                { label: 'اجرای کرون', desc: 'Scheduler', href: '#' },
              ].map(a => (
                <a key={a.label} href={a.href} className="group min-w-0 rounded-2xl border border-white/10 bg-white/[0.04] p-3.5 hover:bg-white/[0.07] hover:border-violet-500/20 transition-colors">
                  <div className="truncate text-sm font-black text-white group-hover:text-violet-200">{a.label}</div>
                  <div className="truncate text-xs text-zinc-500">{a.desc}</div>
                </a>
              ))}
            </div>
            <div className="mx-4 mb-4 rounded-2xl border border-white/10 bg-[#0b0f19] p-3 min-w-0 overflow-hidden">
              <div className="text-xs font-bold text-zinc-400">آدرس وب‌هوک</div>
              <div className="mt-1 break-all font-mono text-xs text-violet-300">https://example.com/public/webhook.php</div>
              <Button size="sm" variant="secondary" className="mt-2 w-full">کپی آدرس</Button>
            </div>
          </Card>

          <Card className="min-w-0 overflow-hidden">
            <CardHeader><div className="flex min-w-0 items-center gap-2 font-black text-white truncate"><HardDrive className="h-4 w-4 shrink-0 text-sky-400" /> سلامت پنل‌ها</div><Badge tone="sky" className="shrink-0">3 فعال</Badge></CardHeader>
            <div className="space-y-2 p-4">
              {[
                { name: 'پاسارگاد اصلی', url: 'panel.outboundcenterpanel.ir', ok: true, latency: '42ms' },
                { name: 'مرزبان EU', url: 'eu.example.com', ok: true, latency: '89ms' },
                { name: 'X-UI Node 3', url: 'x3.example.com', ok: false, latency: '—' },
              ].map(p => (
                <div key={p.name} className="flex min-w-0 items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.04] px-3.5 py-3">
                  <span className={`h-2.5 w-2.5 shrink-0 rounded-full ${p.ok ? 'bg-emerald-500 shadow-[0_0_10px_#10b981]' : 'bg-red-500 shadow-[0_0_10px_#ef4444]'}`} />
                  <div className="min-w-0 flex-1"><div className="truncate text-sm font-bold text-white">{p.name}</div><div className="truncate font-mono text-xs text-zinc-500 break-all">{p.url}</div></div>
                  <span className="shrink-0 text-xs font-bold text-zinc-300">{p.latency}</span>
                </div>
              ))}
            </div>
          </Card>
        </div>
      </div>

      <div className="grid w-full min-w-0 grid-cols-1 gap-6 lg:grid-cols-2">
        <Card className="min-w-0 overflow-hidden">
          <CardHeader><div className="font-black text-white truncate">آخرین سفارش‌ها</div><a href="/orders" className="shrink-0 text-xs font-bold text-violet-300 hover:text-violet-200">همه →</a></CardHeader>
          <div className="divide-y divide-white/[0.06]">
            {[
              { id: 'ORD-9A3F21', user: '987654321', plan: 'Ava Test · 30GB', price: '89,000', status: 'موفق' },
              { id: 'ORD-1B8C44', user: '123456789', plan: 'Ava Max · 100GB', price: '245,000', status: 'در انتظار' },
              { id: 'ORD-K2P90Q', user: '555012399', plan: 'Ava Light · 10GB', price: '45,000', status: 'در انتظار تحویل' },
            ].map(r => (
              <div key={r.id} className="flex min-w-0 items-center gap-3 px-5 py-3.5 hover:bg-white/[0.04] transition-colors">
                <div className="shrink-0 font-mono text-xs font-bold text-violet-300">{r.id}</div>
                <div className="min-w-0 flex-1"><div className="truncate text-sm font-bold text-white">{r.plan}</div><div className="truncate text-xs text-zinc-500">{r.user}</div></div>
                <div className="shrink-0 text-left"><div className="text-sm font-black text-white whitespace-nowrap">{r.price} ت</div><Badge tone={r.status === 'موفق' ? 'emerald' : r.status === 'در انتظار' ? 'amber' : 'sky'} className="mt-1">{r.status}</Badge></div>
              </div>
            ))}
          </div>
        </Card>

        <Card className="min-w-0 overflow-hidden">
          <CardHeader><div className="font-black text-white truncate">تیکت‌های باز</div><a href="/tickets" className="shrink-0 text-xs font-bold text-violet-300">همه →</a></CardHeader>
          <div className="divide-y divide-white/[0.06]">
            {[
              { subject: 'اتصال قطع می‌شود', user: '@amirreza', time: '2 ساعت پیش', prio: 'فوری' },
              { subject: 'درخواست تمدید', user: '@sara_vpn', time: '5 ساعت پیش', prio: 'عادی' },
              { subject: 'لینک خالی است AVANET 0', user: '@vpn_user99', time: 'امروز', prio: 'بحرانی' },
            ].map(t => (
              <div key={t.subject} className="flex min-w-0 items-center gap-3 px-5 py-3.5 hover:bg-white/[0.04] transition-colors">
                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/5 text-zinc-400"><MessageCircleMore className="h-4 w-4" /></div>
                <div className="min-w-0 flex-1"><div className="truncate text-sm font-bold text-white">{t.subject}</div><div className="truncate text-xs text-zinc-500">{t.user} · {t.time}</div></div>
                <Badge tone={t.prio === 'بحرانی' ? 'red' : t.prio === 'فوری' ? 'amber' : 'zinc'} className="shrink-0">{t.prio}</Badge>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  )
}
