import {Card, Badge, Button, Input, TableWrap} from '../components/ui'
import { Search, Ban, Coins, Gift, Shield, UserPlus } from 'lucide-react'

const users = [
  { id: '987654321', name: 'امیررضا', username: 'amirreza_vpn', tokens: 12, invited: 4, status: 'فعال', joined: '۱۴۰۳/۰۸/۲۰' },
  { id: '123456789', name: 'سارا', username: 'sara_vpn', tokens: 3, invited: 1, status: 'فعال', joined: '۱۴۰۳/۰۸/۱۸' },
  { id: '555012399', name: '—', username: 'vpn_user99', tokens: 0, invited: 0, status: 'مسدود', joined: '۱۴۰۳/۰۸/۱۰' },
]

export default function Users() {
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-black text-white">کاربران</h1>
          <p className="text-sm text-zinc-500">مدیریت توکن، دعوت و مسدودسازی — همگام با ربات تلگرام</p>
        </div>
        <div className="flex items-center gap-2">
          <div className="relative">
            <Search className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
            <Input placeholder="آیدی یا @یوزرنیم" className="w-64 pr-9" />
          </div>
          <Button className="gap-2"><UserPlus className="h-4 w-4" /> کاربر جدید</Button>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        {[
          { k: 'کل کاربران', v: '12,847', icon: Shield },
          { k: 'توکن در گردش', v: '42,193', icon: Coins },
          { k: 'دعوت موفق', v: '2,104', icon: Gift },
        ].map(s => (
          <Card key={s.k} className="p-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-500/15 text-violet-300 border border-violet-500/20"><s.icon className="h-5 w-5" /></div>
              <div>
                <div className="text-xs font-bold text-zinc-500">{s.k}</div>
                <div className="text-lg font-black text-white">{s.v}</div>
              </div>
            </div>
          </Card>
        ))}
      </div>

      <Card className="overflow-hidden">
        <TableWrap>
          <table className="w-full min-w-[640px] text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold text-zinc-400">
              <tr>
                <th className="px-4 py-3 text-right">کاربر</th>
                <th className="px-4 py-3 text-right">توکن</th>
                <th className="px-4 py-3 text-right">دعوت‌شده</th>
                <th className="px-4 py-3 text-right">وضعیت</th>
                <th className="px-4 py-3 text-right">عضویت</th>
                <th className="px-4 py-3 text-right">عملیات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/[0.06]">
              {users.map(u => (
                <tr key={u.id} className="hover:bg-white/[0.04]">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <img src={`https://i.pravatar.cc/100?img=${parseInt(u.id.slice(-2)) % 70}`} className="h-9 w-9 rounded-xl object-cover" alt="" />
                      <div>
                        <div className="font-bold text-white">{u.name} <span className="font-mono text-xs text-zinc-500">· {u.id}</span></div>
                        <div className="text-xs text-violet-300">@{u.username}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3"><Badge tone="amber" className="gap-1"><Coins className="h-3 w-3" /> {u.tokens}</Badge></td>
                  <td className="px-4 py-3 text-white font-bold">{u.invited}</td>
                  <td className="px-4 py-3"><Badge tone={u.status === 'فعال' ? 'emerald' : 'red'}>{u.status}</Badge></td>
                  <td className="px-4 py-3 text-zinc-400">{u.joined}</td>
                  <td className="px-4 py-3">
                    <div className="flex gap-1.5">
                      <Button size="sm" variant="secondary" className="h-7 gap-1 text-xs"><Coins className="h-3.5 w-3.5" /> توکن</Button>
                      <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs"><Ban className="h-3.5 w-3.5" /> {u.status === 'فعال' ? 'مسدود' : 'رفع'}</Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table></TableWrap>
      </Card>
    </div>
  )
}
