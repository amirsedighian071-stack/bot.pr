import {Card, CardHeader, Badge, Button, Input, Select, Label, TableWrap} from '../components/ui'
import { Ticket, Plus, Pencil, Trash2, Percent, Calendar } from 'lucide-react'

export default function Discounts(){
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex items-center justify-between"><div><h1 className="text-xl font-black text-white">کدهای تخفیف</h1><p className="text-sm text-zinc-500">درصدی یا مبلغ ثابت — محدود به پلن یا عمومی</p></div><Button className="gap-2"><Plus className="h-4 w-4"/> کد جدید</Button></div>
      <Card>
        <CardHeader><div className="font-black text-white">افزودن کد تخفیف</div></CardHeader>
        <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-4">
          <div><Label>کد</Label><Input placeholder="WELCOME10" /></div>
          <div><Label>نوع</Label><Select><option>درصدی</option><option>مبلغ ثابت</option></Select></div>
          <div><Label>مقدار</Label><Input type="number" placeholder="10" /></div>
          <div><Label>سقف استفاده</Label><Input type="number" placeholder="0 = نامحدود" /></div>
          <div className="md:col-span-4 flex gap-2"><Button>ذخیره</Button><Button variant="secondary">انصراف</Button></div>
        </div>
      </Card>
      <Card className="overflow-hidden">
        <TableWrap>
          <table className="w-full min-w-[640px] text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold text-zinc-400"><tr><th className="px-4 py-3 text-right">کد</th><th className="px-4 py-3 text-right">نوع/مقدار</th><th className="px-4 py-3 text-right">استفاده</th><th className="px-4 py-3 text-right">پلن</th><th className="px-4 py-3 text-right">وضعیت</th><th className="px-4 py-3 text-right">عملیات</th></tr></thead>
            <tbody className="divide-y divide-white/[0.06]">
              {[{code:'WELCOME10',t:'10%',u:'42/100',p:'همه',a:true},{code:'AVA50',t:'50,000 ت',u:'7/20',p:'Ava Max',a:true}].map(d=>(
                <tr key={d.code} className="hover:bg-white/[0.04]"><td className="px-4 py-3"><code className="rounded bg-violet-500/15 px-2 py-1 text-violet-200 border border-violet-500/20">{d.code}</code></td><td className="px-4 py-3 font-bold text-white">{d.t}</td><td className="px-4 py-3 text-white">{d.u}</td><td className="px-4 py-3 text-zinc-300">{d.p}</td><td className="px-4 py-3"><Badge tone={d.a?'emerald':'zinc'}>{d.a?'فعال':'غیرفعال'}</Badge></td><td className="px-4 py-3"><div className="flex gap-1"><Button size="sm" variant="secondary" className="h-7"><Pencil className="h-3.5 w-3.5"/></Button><Button size="sm" variant="ghost" className="h-7"><Trash2 className="h-3.5 w-3.5"/></Button></div></td></tr>
              ))}
            </tbody>
          </table></TableWrap>
      </Card>
    </div>
  )
}
