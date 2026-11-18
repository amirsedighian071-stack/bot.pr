import { Card, Button, Badge, Input, Label } from '../components/ui'
import { Database, Plus, Pencil, Trash2 } from 'lucide-react'
export default function Volumes(){
  return (
    <div className="w-full min-w-0 max-w-2xl space-y-5 overflow-x-hidden">
      <h1 className="text-xl font-black text-white">حجم‌های پیش‌فرض</h1>
      <Card className="p-5">
        <div className="font-black text-white">افزودن حجم</div>
        <div className="mt-3 grid grid-cols-3 gap-3">
          <div><Label>برچسب</Label><Input placeholder="10 گیگ" /></div>
          <div><Label>GB</Label><Input type="number" placeholder="10" /></div>
          <div className="flex items-end"><Button className="w-full gap-2"><Plus className="h-4 w-4"/> افزودن</Button></div>
        </div>
      </Card>
      <Card className="overflow-hidden">
        <div className="divide-y divide-white/[0.06]">
          {[{l:'10 گیگ',g:10},{l:'30 گیگ',g:30},{l:'50 گیگ',g:50},{l:'100 گیگ',g:100}].map(v=>(
            <div key={v.l} className="flex items-center justify-between px-5 py-3 hover:bg-white/[0.04]"><div className="flex items-center gap-3"><div className="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-500/15 text-violet-300"><Database className="h-4 w-4"/></div><div className="font-bold text-white">{v.l}</div><Badge tone="zinc">{v.g} GB</Badge></div><div className="flex gap-1"><Button size="sm" variant="ghost" className="h-7 w-7 p-0"><Pencil className="h-3.5 w-3.5"/></Button><Button size="sm" variant="ghost" className="h-7 w-7 p-0 text-red-400"><Trash2 className="h-3.5 w-3.5"/></Button></div></div>
          ))}
        </div>
      </Card>
    </div>
  )
}
