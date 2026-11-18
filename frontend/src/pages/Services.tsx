import { Card, Badge, Button, Input, Label, Textarea } from '../components/ui'
import { Layers, Plus, Pencil, Trash2 } from 'lucide-react'

export default function Services(){
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex items-center justify-between"><div><h1 className="text-xl font-black text-white">سرویس‌ها</h1><p className="text-sm text-zinc-500">دسته‌بندی کلی سرویس‌ها (Ava , EU , ...)</p></div><Button className="gap-2"><Plus className="h-4 w-4"/> سرویس جدید</Button></div>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Card className="p-5">
          <div className="font-black text-white">افزودن سرویس</div>
          <div className="mt-4 space-y-3">
            <div><Label>نام سرویس</Label><Input placeholder="Ava Network" /></div>
            <div><Label>توضیحات</Label><Textarea placeholder="توضیح کوتاه..." /></div>
            <div className="flex gap-2"><Button>ذخیره</Button><Button variant="secondary">انصراف</Button></div>
          </div>
        </Card>
        <Card className="p-5">
          <div className="text-sm font-black text-white">لیست سرویس‌ها</div>
          <div className="mt-3 space-y-2">
            {[{n:'Ava Network',c:3},{n:'EU Premium',c:1},{n:'Test Zone',c:2}].map(s=>(
              <div key={s.n} className="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                <div className="flex items-center gap-3"><div className="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-500/15 text-violet-300"><Layers className="h-4 w-4"/></div><div className="font-bold text-white">{s.n}</div></div>
                <Badge tone="zinc">{s.c} پلن</Badge>
                <div className="flex gap-1"><Button size="sm" variant="ghost" className="h-7 w-7 p-0"><Pencil className="h-3.5 w-3.5"/></Button><Button size="sm" variant="ghost" className="h-7 w-7 p-0"><Trash2 className="h-3.5 w-3.5"/></Button></div>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  )
}
