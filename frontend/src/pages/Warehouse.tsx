import { Card, Button, Badge, Textarea, Label, Select } from '../components/ui'
import { Archive, Plus } from 'lucide-react'
export default function Warehouse(){
  return (
    <div className="space-y-5">
      <h1 className="text-xl font-black text-white">انبار دستی</h1>
      <Card className="p-5">
        <div className="font-black text-white flex items-center gap-2"><Archive className="h-4 w-4 text-amber-400"/> افزودن کانفیگ دستی</div>
        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><Label>گروه کانفیگ</Label><Select><option>Ava test</option><option>Ava max</option></Select></div>
          <div className="md:col-span-2"><Label>متن کانفیگ (VLESS / VMess ...)</Label><Textarea rows={4} placeholder="vless://..." dir="ltr"/></div>
          <div><Button className="gap-2"><Plus className="h-4 w-4"/> ذخیره در انبار</Button></div>
          <div className="text-xs text-zinc-500">موجودی انبار: <Badge tone="amber">12 عدد</Badge> · استفاده‌شده: 34</div>
        </div>
      </Card>
    </div>
  )
}
