import { motion } from 'framer-motion'
import { Users, CreditCard, Receipt, MessageCircleMore, HardDrive, TrendingUp, ArrowUpRight, Zap, Activity } from 'lucide-react'
import { Card, CardHeader, Badge, Button } from '../components/ui'
import { AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'

const stats = [
  { label: 'کاربران کل', value: '12,847', sub: '+342 این هفته', icon: Users, gradient: 'from-violet-600 to-indigo-600', glow: 'shadow-[0_12px_30px_rgba(124,58,237,0.35)]' },
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
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-[22px] font-black tracking-tight text-white">داشبورد</h1>
          <p className="mt-1 text-sm text-zinc-500">نمای کلی فروش، کاربران و سلامت پنل‌ها — بروزرسانی زنده</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge tone="violet" className="gap-1.5"><span className="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]" /> آنلاین · Dark Luxe</Badge>
          <Badge tone="zinc">PHP 8.2+</Badge>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {stats.map((s, i) => (
          <motion.div
            key={s.label}
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.06, duration: 0.4, ease: [0.32, 0.72, 0, 1] }}
            whileHover={{ y: -3 }}
            className={`group relative overflow-hidden rounded-[22px] bg-gradient-to-br ${s.gradient} p-[1px] ${s.glow}`}
          >
            <div className="relative rounded-[21px] bg-gradient-to-br from-black/15 to-black/5 p-5 backdrop-blur">
              <div className="absolute -left-6 -top-6 h-20 w-20 rounded-full bg-white/10 blur-2xl group-hover:bg-white/15 transition-colors" />
              <div className="flex items-start justify-between">
                <div>
                  <div className="text-xs font-bold tracking-wide text-white/70">{s.label}</div>
                  <div className="mt-2 text-[22px] font-black leading-none text-white">{s.value}</div>
                  <div className="mt-2 inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold text-white">
                    <TrendingUp className="h-3 w-3" /> {s.sub}
                  </div>
                </div>
                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur">
                  <s.icon className="h-5 w-5" />
                </div>
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {/* Chart */}
        <Card className="xl:col-span-8">
          <CardHeader>
            <div className="flex items-center gap-2 font-black text-white">
              <Activity className="h-4 w-4 text-violet-400" />
              نمودار فروش ۷ روز اخیر
            </div>
            <Badge tone="emerald" className="gap-1"><ArrowUpRight className="h-3 w-3" /> +12.4%</Badge>
          </CardHeader>
          <div className="h-[260px] p-2">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData}>
                <defs>
                  <linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#8b5cf6" stopOpacity={0.35} />
                    <stop offset="100%" stopColor="#8b5cf6" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <XAxis dataKey="name" tick={{ fill: '#71717a', fontSize: 11 }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fill: '#71717a', fontSize: 11 }} axisLine={false} tickLine={false} tickFormatter={(v: number) => `${(v/1000000).toFixed(0)}M`} width={38} />
                <Tooltip
                  contentStyle={{ background: 'rgba(24,24,27,0.9)', border: '1px solid rgba(255,255,255,0.08)', borderRadius: 16, backdropFilter: 'blur(12px)', color: '#fff' }}
                  formatter={(v: any) => [`${(v as number).toLocaleString('fa-IR')} تومان`, 'فروش']}
                />
                <Area type="monotone" dataKey="v" stroke="#8b5cf6" strokeWidth={2.5} fill="url(#g)" dot={false} activeDot={{ r: 5, fill: '#8b5cf6', stroke: '#fff', strokeWidth: 2 }} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </Card>

        {/* Quick actions + system health */}
        <div className="space-y-6 xl:col-span-4">
          <Card>
            <CardHeader>
              <div className="flex items-center gap-2 font-black text-white"><Zap className="h-4 w-4 text-amber-400" /> دسترسی سریع</div>
            </CardHeader>
            <div className="grid grid-cols-2 gap-2 p-4">
              {[
                { label: 'ست وب‌هوک', desc: 'Telegram', href: '/settings/bot' },
                { label: 'افزودن پنل', desc: 'Outbound', href: '/panels' },
                { label: 'پلن جدید', desc: 'Ava Test', href: '/plans' },
                { label: 'اجرای کرون', desc: 'Scheduler', href: '#' },
              ].map(a => (
                <a key={a.label} href={a.href} className="group rounded-2xl border border-white/10 bg-white/[0.04] p-3.5 hover:bg-white/[0.07] hover:border-violet-500/20 transition-colors">
                  <div className="text-sm font-black text-white group-hover:text-violet-200">{a.label}</div>
                  <div className="text-xs text-zinc-500">{a.desc}</div>
                </a>
              ))}
            </div>
            <div className="mx-4 mb-4 rounded-2xl border border-white/10 bg-[#0f172a] p-3">
              <div className="text-xs font-bold text-zinc-400">آدرس وب‌هوک</div>
              <div className="mt-1 break-all font-mono text-xs text-violet-300">https://example.com/public/webhook.php</div>
              <Button size="sm" variant="secondary" className="mt-2 w-full">کپی آدرس</Button>
            </div>
          </Card>

          <Card>
            <CardHeader>
              <div className="flex items-center gap-2 font-black text-white"><HardDrive className="h-4 w-4 text-sky-400" /> سلامت پنل‌ها</div>
              <Badge tone="sky">3 فعال</Badge>
            </CardHeader>
            <div className="space-y-2 p-4">
              {[
                { name: 'پاسارگاد اصلی', url: 'panel.outboundcenterpanel.ir', ok: true, latency: '42ms' },
                { name: 'مرزبان EU', url: 'eu.example.com', ok: true, latency: '89ms' },
                { name: 'X-UI Node 3', url: 'x3.example.com', ok: false, latency: '—' },
              ].map(p => (
                <div key={p.name} className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.04] px-3.5 py-3">
                  <span className={`h-2.5 w-2.5 rounded-full ${p.ok ? 'bg-emerald-500 shadow-[0_0_10px_#10b981]' : 'bg-red-500 shadow-[0_0_10px_#ef4444]'}`} />
                  <div className="min-w-0">
                    <div className="text-sm font-bold text-white">{p.name}</div>
                    <div className="truncate font-mono text-xs text-zinc-500">{p.url}</div>
                  </div>
                  <span className="mr-auto text-xs font-bold text-zinc-300">{p.latency}</span>
                </div>
              ))}
            </div>
          </Card>
        </div>
      </div>

      {/* Recent tables */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <div className="font-black text-white">آخرین سفارش‌ها</div>
            <a href="/orders" className="text-xs font-bold text-violet-300 hover:text-violet-200">همه →</a>
          </CardHeader>
          <div className="divide-y divide-white/[0.06]">
            {[
              { id: 'ORD-9A3F21', user: '987654321', plan: 'Ava Test · 30GB', price: '89,000', status: 'موفق' },
              { id: 'ORD-1B8C44', user: '123456789', plan: 'Ava Max · 100GB', price: '245,000', status: 'در انتظار' },
              { id: 'ORD-K2P90Q', user: '555012399', plan: 'Ava Light · 10GB', price: '45,000', status: 'در انتظار تحویل' },
            ].map(r => (
              <div key={r.id} className="flex items-center gap-3 px-5 py-3.5 hover:bg-white/[0.04] transition-colors">
                <div className="font-mono text-xs font-bold text-violet-300">{r.id}</div>
                <div className="min-w-0">
                  <div className="text-sm font-bold text-white">{r.plan}</div>
                  <div className="text-xs text-zinc-500">{r.user}</div>
                </div>
                <div className="mr-auto text-left">
                  <div className="text-sm font-black text-white">{r.price} ت</div>
                  <Badge tone={r.status === 'موفق' ? 'emerald' : r.status === 'در انتظار' ? 'amber' : 'sky'} className="mt-1">{r.status}</Badge>
                </div>
              </div>
            ))}
          </div>
        </Card>

        <Card>
          <CardHeader>
            <div className="font-black text-white">تیکت‌های باز</div>
            <a href="/tickets" className="text-xs font-bold text-violet-300">همه →</a>
          </CardHeader>
          <div className="divide-y divide-white/[0.06]">
            {[
              { subject: 'اتصال قطع می‌شود', user: '@amirreza', time: '2 ساعت پیش', prio: 'فوری' },
              { subject: 'درخواست تمدید', user: '@sara_vpn', time: '5 ساعت پیش', prio: 'عادی' },
              { subject: 'لینک خالی است AVANET 0', user: '@vpn_user99', time: 'امروز', prio: 'بحرانی' },
            ].map(t => (
              <div key={t.subject} className="flex items-center gap-3 px-5 py-3.5 hover:bg-white/[0.04] transition-colors">
                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/5 text-zinc-400">
                  <MessageCircleMore className="h-4 w-4" />
                </div>
                <div className="min-w-0">
                  <div className="truncate text-sm font-bold text-white">{t.subject}</div>
                  <div className="text-xs text-zinc-500">{t.user} · {t.time}</div>
                </div>
                <Badge tone={t.prio === 'بحرانی' ? 'red' : t.prio === 'فوری' ? 'amber' : 'zinc'} className="mr-auto">{t.prio}</Badge>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  )
}
