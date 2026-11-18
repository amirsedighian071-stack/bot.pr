import { Card, CardHeader, Button, Badge, Input, Select, Label, Textarea } from '../components/ui'
import { Plus, Pencil, Trash2, Layers, Package, Sparkles } from 'lucide-react'
import { useState } from 'react'

const mockPlans = [
  { id: 1, name: 'Ava Test · 30 روزه 30GB', service: 'Ava', group: 'Ava test', panelGroup: '5 (Ava test)', price: '89,000', period: '30 روز', volume: '30GB', active: true },
  { id: 2, name: 'Ava Max · 30 روزه 100GB', service: 'Ava', group: 'Ava max', panelGroup: '3 (Ava max)', price: '245,000', period: '30 روز', volume: '100GB', active: true },
  { id: 3, name: 'Ava Light · 10GB', service: 'Ava', group: 'Ava light', panelGroup: '— از گروه', price: '45,000', period: '30 روز', volume: '10GB', active: false },
]

export default function Plans() {
  const [editing, setEditing] = useState(false)

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-black text-white">پلن‌ها</h1>
          <p className="text-sm text-zinc-500">هر پلن به یک گروه کانفیگ + گروه پنل (group_ids) وصل است تا لینک خالی نماند</p>
        </div>
        <Button onClick={() => setEditing(!editing)} className="gap-2"><Plus className="h-4 w-4" /> افزودن پلن</Button>
      </div>

      {editing && (
        <Card className="border-violet-500/20">
          <CardHeader>
            <div className="flex items-center gap-2 font-black text-white"><Sparkles className="h-4 w-4 text-violet-400" /> افزودن / ویرایش پلن</div>
            <Badge tone="violet">Outbound Center</Badge>
          </CardHeader>
          <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
            <div>
              <Label>سرویس</Label>
              <Select defaultValue=""><option value="">انتخاب سرویس</option><option>Ava</option><option>EU Node</option></Select>
            </div>
            <div>
              <Label>گروه کانفیگ (پنل + inbound)</Label>
              <Select defaultValue=""><option>— انتخاب —</option><option>Ava test (panel outbound — 5)</option><option>Ava max (panel outbound — 3)</option></Select>
              <div className="mt-1 text-[11px] text-zinc-500">هر پلن باید به یک گروه کانفیگ وصل باشد</div>
            </div>
            <div>
              <Label>نام پلن</Label>
              <Input placeholder="مثلاً اقتصادی 30 روزه" />
            </div>

            <div className="md:col-span-2">
              <Label>گروه پنل (Override — برای PasarGuard / Outbound)</Label>
              <Select defaultValue=""><option>— استفاده از inbound گروه (پیش‌فرض) —</option><option>AVA — Ava test (5)</option><option>AVA — Ava max (3)</option></Select>
              <div className="mt-1.5 rounded-xl border border-amber-500/15 bg-amber-500/10 p-2.5 text-xs leading-relaxed text-amber-200">
                اگر پلن باید دقیقاً داخل یک گروه پنل ساخته شود (Ava test / Ava max)، آن را اینجا انتخاب کنید تا <code className="rounded bg-black/30 px-1">group_ids=[ID]</code> به پنل ارسال شود و سابسکرایب خالی (AVANET 0) نماند.
              </div>
            </div>

            <div className="rounded-2xl border border-sky-500/15 bg-sky-500/10 p-3 text-xs leading-relaxed text-sky-200">
              <div className="font-black text-sky-100">راهنما Outbound Center</div>
              مسیر پنل: <code className="rounded bg-black/30 px-1">ویرایش کاربر → گروه‌ها → Ava test ✔️</code> — هر گروه یک ID عددی دارد در <code className="rounded bg-black/30 px-1">/api/groups</code>. این ID را همین‌جا ست کنید.
            </div>

            <div><Label>قیمت (تومان)</Label><Input type="number" placeholder="89000" /></div>
            <div><Label>قیمت هر گیگ</Label><Input type="number" placeholder="0" /></div>
            <div><Label>مدت (روز)</Label><Input type="number" defaultValue={30} /></div>
            <div><Label>حجم (GB) 0=دلخواه</Label><Input type="number" placeholder="30" /></div>
            <div className="flex items-end gap-3">
              <label className="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" defaultChecked className="rounded" /> قابل تمدید</label>
              <label className="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" defaultChecked className="rounded" /> فعال</label>
            </div>

            <div className="md:col-span-3 flex gap-2">
              <Button>ذخیره پلن</Button>
              <Button variant="secondary" onClick={() => setEditing(false)}>انصراف</Button>
            </div>
          </div>
        </Card>
      )}

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold text-zinc-400">
              <tr>
                <th className="px-4 py-3 text-right">#</th>
                <th className="px-4 py-3 text-right">پلن</th>
                <th className="px-4 py-3 text-right">سرویس</th>
                <th className="px-4 py-3 text-right">گروه کانفیگ</th>
                <th className="px-4 py-3 text-right">گروه پنل</th>
                <th className="px-4 py-3 text-right">قیمت</th>
                <th className="px-4 py-3 text-right">وضعیت</th>
                <th className="px-4 py-3 text-right">عملیات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/[0.06]">
              {mockPlans.map(p => (
                <tr key={p.id} className="hover:bg-white/[0.04]">
                  <td className="px-4 py-3 font-mono text-zinc-400">{p.id}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2 font-bold text-white"><Package className="h-4 w-4 text-violet-400" /> {p.name}</div>
                    <div className="text-xs text-zinc-500">{p.period} · {p.volume}</div>
                  </td>
                  <td className="px-4 py-3"><span className="inline-flex items-center gap-1 rounded-full bg-white/5 px-2.5 py-1 text-xs font-bold text-zinc-300 border border-white/10"><Layers className="h-3 w-3" /> {p.service}</span></td>
                  <td className="px-4 py-3 text-zinc-300">{p.group}</td>
                  <td className="px-4 py-3">{p.panelGroup.includes('—') ? <span className="text-xs text-zinc-500">از گروه</span> : <Badge tone="violet">{p.panelGroup}</Badge>}</td>
                  <td className="px-4 py-3 font-black text-white">{p.price} ت</td>
                  <td className="px-4 py-3"><Badge tone={p.active ? 'emerald' : 'zinc'}>{p.active ? 'فعال' : 'غیرفعال'}</Badge></td>
                  <td className="px-4 py-3">
                    <div className="flex gap-1.5">
                      <Button size="sm" variant="secondary" className="h-7 gap-1"><Pencil className="h-3.5 w-3.5" /> ویرایش</Button>
                      <Button size="sm" variant="ghost" className="h-7"><Trash2 className="h-3.5 w-3.5" /></Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  )
}
