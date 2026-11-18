import {Card, Badge, Button, Input, TableWrap} from '../components/ui'
import { Search, Copy, QrCode, Power, Trash2 } from 'lucide-react'

const subs = [
  {id:412, user:'987654321', username:'Test987_482', panel:'پاسارگاد اصلی', volume:'30GB', expire:'۱۴۰۳/۰۹/۲۷', status:'فعال', url:'https://panel.example.com/sub/abc123'},
  {id:411, user:'123456789', username:'Test123_119', panel:'پاسارگاد اصلی', volume:'100GB', expire:'۱۴۰۳/۰۹/۲۶', status:'منقضی', url:'https://panel.example.com/sub/def456'},
]

export default function Subscriptions(){
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex items-center justify-between"><div><h1 className="text-xl font-black text-white">اشتراک‌ها</h1><p className="text-sm text-zinc-500">مدیریت لینک‌ها، تمدید و نمایش QR</p></div><div className="relative"><Search className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500"/><Input placeholder="یوزرنیم یا آیدی" className="pr-9 w-64"/></div></div>
      <Card className="overflow-hidden">
        <TableWrap>
          <table className="w-full min-w-[640px] text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold text-zinc-400"><tr><th className="px-4 py-3 text-right">#</th><th className="px-4 py-3 text-right">کاربر</th><th className="px-4 py-3 text-right">یوزرنیم</th><th className="px-4 py-3 text-right">پنل</th><th className="px-4 py-3 text-right">حجم</th><th className="px-4 py-3 text-right">انقضا</th><th className="px-4 py-3 text-right">وضعیت</th><th className="px-4 py-3 text-right">لینک</th><th className="px-4 py-3 text-right">عملیات</th></tr></thead>
            <tbody className="divide-y divide-white/[0.06]">
              {subs.map(s=>(
                <tr key={s.id} className="hover:bg-white/[0.04]">
                  <td className="px-4 py-3 font-mono text-zinc-400">{s.id}</td>
                  <td className="px-4 py-3 font-mono text-white">{s.user}</td>
                  <td className="px-4 py-3 font-bold text-white">{s.username}</td>
                  <td className="px-4 py-3 text-zinc-300">{s.panel}</td>
                  <td className="px-4 py-3 font-bold text-white">{s.volume}</td>
                  <td className="px-4 py-3 text-zinc-400 text-xs">{s.expire}</td>
                  <td className="px-4 py-3"><Badge tone={s.status==='فعال'?'emerald':'amber'}>{s.status}</Badge></td>
                  <td className="px-4 py-3"><div className="flex gap-1"><Button size="sm" variant="secondary" className="h-7 gap-1"><Copy className="h-3 w-3"/> کپی</Button><Button size="sm" variant="ghost" className="h-7"><QrCode className="h-3.5 w-3.5"/></Button></div></td>
                  <td className="px-4 py-3"><div className="flex gap-1"><Button size="sm" variant="ghost" className="h-7"><Power className="h-3.5 w-3.5"/></Button><Button size="sm" variant="ghost" className="h-7 text-red-400"><Trash2 className="h-3.5 w-3.5"/></Button></div></td>
                </tr>
              ))}
            </tbody>
          </table></TableWrap>
      </Card>
    </div>
  )
}
