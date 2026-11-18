import { Card, CardHeader, Badge, Button, Input } from '../components/ui'
import { Search, RefreshCw, Eye, Copy, QrCode, Filter } from 'lucide-react'
import { useState } from 'react'

const orders = [
  { id: 'ORD-9A3F21', username: 'Test987_482', telegram: '987654321', plan: 'Ava Test', volume: '30GB', price: 92000, status: 'completed', date: '۱۴۰۳/۰۸/۲۷' },
  { id: 'ORD-1B8C44', username: 'Test123_119', telegram: '123456789', plan: 'Ava Max', volume: '100GB', price: 248000, status: 'pending', date: '۱۴۰۳/۰۸/۲۷' },
  { id: 'ORD-K2P90Q', username: 'gift_555_170000', telegram: '555012399', plan: 'Ava Light', volume: '10GB', price: 45000, status: 'delivering', date: '۱۴۰۳/۰۸/۲۶' },
  { id: 'ORD-X7L01A', username: 'Test777_901', telegram: '777000111', plan: 'Ava Plus', volume: '50GB', price: 149000, status: 'failed', date: '۱۴۰۳/۰۸/۲۶' },
]

const statusMap: Record<string, { tone: any, label: string }> = {
  completed: { tone: 'emerald', label: 'موفق' },
  pending: { tone: 'amber', label: 'در انتظار' },
  delivering: { tone: 'sky', label: 'در انتظار تحویل' },
  failed: { tone: 'red', label: 'ناموفق' },
}

export default function Orders() {
  const [q, setQ] = useState('')
  const [status, setStatus] = useState<string>('all')

  const filtered = orders.filter(o => {
    if (status !== 'all' && o.status !== status) return false
    if (q && !o.id.includes(q) && !o.telegram.includes(q) && !o.username.includes(q)) return false
    return true
  })

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-black text-white">سفارش‌ها</h1>
          <p className="text-sm text-zinc-500">پیگیری پرداخت، تحویل و لاگ پنل — Idempotent delivery</p>
        </div>
        <Button variant="secondary" className="gap-2"><RefreshCw className="h-4 w-4" /> بروزرسانی</Button>
      </div>

      <Card>
        <div className="flex flex-wrap items-center gap-2 border-b border-white/[0.06] p-4">
          <div className="flex items-center gap-1.5">
            {[
              { k: 'all', l: 'همه' },
              { k: 'pending', l: 'در انتظار' },
              { k: 'completed', l: 'موفق' },
              { k: 'delivering', l: 'در انتظار تحویل' },
              { k: 'failed', l: 'ناموفق' },
            ].map(f => (
              <button
                key={f.k}
                onClick={() => setStatus(f.k)}
                className={`rounded-full px-3.5 py-1.5 text-xs font-bold transition-colors ${status === f.k ? 'bg-violet-600 text-white' : 'bg-white/5 text-zinc-400 hover:bg-white/10 hover:text-white'}`}
              >
                {f.l}
              </button>
            ))}
          </div>

          <div className="mr-auto flex items-center gap-2">
            <div className="relative">
              <Search className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
              <Input value={q} onChange={(e:any) => setQ(e.target.value)} placeholder="جستجو ORD یا آیدی تلگرام" className="w-64 pr-9" />
            </div>
            <Button variant="ghost" size="icon"><Filter className="h-4 w-4" /></Button>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold tracking-wide text-zinc-400">
              <tr>
                <th className="px-4 py-3 text-right">سفارش</th>
                <th className="px-4 py-3 text-right">کاربر</th>
                <th className="px-4 py-3 text-right">پلن / حجم</th>
                <th className="px-4 py-3 text-right">مبلغ نهایی</th>
                <th className="px-4 py-3 text-right">وضعیت</th>
                <th className="px-4 py-3 text-right">تاریخ</th>
                <th className="px-4 py-3 text-right">عملیات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/[0.06]">
              {filtered.map(o => (
                <tr key={o.id} className="hover:bg-white/[0.04] transition-colors">
                  <td className="px-4 py-3">
                    <div className="font-mono text-xs font-bold text-violet-300">{o.id}</div>
                    <div className="text-xs text-zinc-500">{o.username}</div>
                  </td>
                  <td className="px-4 py-3 font-mono text-white">{o.telegram}</td>
                  <td className="px-4 py-3">
                    <div className="font-bold text-white">{o.plan}</div>
                    <div className="text-xs text-zinc-500">{o.volume}</div>
                  </td>
                  <td className="px-4 py-3 font-black text-white">{o.price.toLocaleString('fa-IR')} ت</td>
                  <td className="px-4 py-3"><Badge tone={statusMap[o.status].tone}>{statusMap[o.status].label}</Badge></td>
                  <td className="px-4 py-3 text-zinc-400">{o.date}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1.5">
                      <Button size="sm" variant="secondary" className="h-7 px-2 text-xs"><Eye className="h-3.5 w-3.5" /></Button>
                      <Button size="sm" variant="ghost" className="h-7 px-2 text-xs"><Copy className="h-3.5 w-3.5" /></Button>
                      <Button size="sm" variant="ghost" className="h-7 px-2 text-xs"><QrCode className="h-3.5 w-3.5" /></Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="flex items-center justify-between border-t border-white/[0.06] px-4 py-3 text-xs text-zinc-500">
          <span>نمایش {filtered.length} از {orders.length} سفارش</span>
          <span className="rounded-full bg-emerald-500/15 px-2.5 py-1 font-bold text-emerald-300">Idempotent تحویل فعال</span>
        </div>
      </Card>
    </div>
  )
}
