import { Card, CardHeader, Badge, Button } from '../components/ui'
import { MessageCircle, Gift, Clock, Check, X } from 'lucide-react'

export default function Tickets(){
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex items-center justify-between"><div><h1 className="text-xl font-black text-white">تیکت‌ها & هدایا</h1><p className="text-sm text-zinc-500">پشتیبانی کاربران و درخواست‌های هدیه نقدی/توکن</p></div><div className="flex gap-2"><Badge tone="red">12 باز</Badge><Badge tone="emerald">4 هدیه در انتظار</Badge></div></div>
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><div className="font-black text-white flex items-center gap-2"><MessageCircle className="h-4 w-4 text-violet-400"/> تیکت‌های اخیر</div></CardHeader>
          <div className="divide-y divide-white/[0.06]">
            {[
              {id:101, user:'987654321', sub:'اتصال قطع می‌شود - V2Ray', status:'open', time:'2 ساعت پیش'},
              {id:102, user:'5550123', sub:'درخواست تمدید 30GB', status:'answered', time:'دیروز'},
              {id:103, user:'7770001', sub:'لینک خالی بود', status:'closed', time:'۳ روز پیش'},
            ].map(t=>(
              <div key={t.id} className="flex items-center gap-3 p-4 hover:bg-white/[0.04]">
                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/5 text-zinc-400">#{t.id}</div>
                <div className="min-w-0">
                  <div className="truncate font-bold text-white">{t.sub}</div>
                  <div className="text-xs text-zinc-500">{t.user} · {t.time}</div>
                </div>
                <Badge tone={t.status==='open'?'red':t.status==='answered'?'sky':'zinc'} className="mr-auto">{t.status}</Badge>
              </div>
            ))}
          </div>
        </Card>
        <Card>
          <CardHeader><div className="font-black text-white flex items-center gap-2"><Gift className="h-4 w-4 text-amber-400"/> درخواست هدیه نقدی</div></CardHeader>
          <div className="divide-y divide-white/[0.06]">
            {[
              {id:12, user:'987654321', type:'نقدی 45K', tokens:13, card:'6037 •• 1234', status:'pending'},
              {id:11, user:'1234567', type:'10GB', tokens:7, card:'—', status:'approved'},
            ].map(g=>(
              <div key={g.id} className="flex items-center gap-3 p-4 hover:bg-white/[0.04]">
                <div className="text-sm font-black text-white">#{g.id} · {g.type}</div>
                <div className="text-xs text-zinc-500">{g.user} · {g.tokens} توکن</div>
                <Badge tone={g.status==='pending'?'amber':'emerald'} className="mr-auto">{g.status}</Badge>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  )
}
