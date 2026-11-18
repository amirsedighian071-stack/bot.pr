import { Card, Button, Badge, Input, Select, Label, Accordion, TableWrap } from '../components/ui'
import { Plus, Pencil, Trash2, Layers, Package, Sparkles, DollarSign, HardDrive, Settings2 } from 'lucide-react'
import { useState } from 'react'

const mockPlans = [
  { id: 1, name: 'Ava Test · 30 روزه 30GB', service: 'Ava', group: 'Ava test', panelGroup: '5 (Ava test)', price: '89,000', period: '30 روز', volume: '30GB', active: true },
  { id: 2, name: 'Ava Max · 30 روزه 100GB', service: 'Ava', group: 'Ava max', panelGroup: '3 (Ava max)', price: '245,000', period: '30 روز', volume: '100GB', active: true },
  { id: 3, name: 'Ava Light · 10GB', service: 'Ava', group: 'Ava light', panelGroup: '— از گروه', price: '45,000', period: '30 روز', volume: '10GB', active: false },
]

export default function Plans() {
  const [editing, setEditing] = useState(false)
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="min-w-0">
          <h1 className="truncate text-xl font-black text-white">پلن‌ها</h1>
          <p className="break-safe text-sm text-zinc-500">هر پلن به گروه کانفیگ + گروه پنل (group_ids) وصل است تا لینک خالی نماند</p>
        </div>
        <Button onClick={() => setEditing(!editing)} className="shrink-0 gap-2"><Plus className="h-4 w-4" /> {editing ? 'بستن فرم' : 'افزودن پلن'}</Button>
      </div>

      {editing && (
        <div className="space-y-3">
          <Accordion title="اطلاعات اصلی پلن" icon={<Package className="h-4 w-4" />} badge={<Badge tone="violet">Outbound Center</Badge>} defaultOpen>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
              <div><Label>سرویس</Label><Select defaultValue=""><option value="">انتخاب سرویس</option><option>Ava</option><option>EU Node</option></Select></div>
              <div><Label>گروه کانفیگ (پنل + inbound)</Label><Select defaultValue=""><option>— انتخاب —</option><option>Ava test (panel outbound — 5)</option><option>Ava max (panel outbound — 3)</option></Select><div className="mt-1 text-[11px] text-zinc-500 break-safe">هر پلن باید به یک گروه کانفیگ وصل باشد</div></div>
              <div><Label>نام پلن</Label><Input placeholder="مثلاً اقتصادی 30 روزه" /></div>
            </div>
          </Accordion>
          <Accordion title="اتصال گروه پنل (حیاتی برای Outbound)" icon={<HardDrive className="h-4 w-4" />} defaultOpen>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
              <div className="md:col-span-2">
                <Label>گروه پنل (Override — PasarGuard / Outbound)</Label>
                <Select defaultValue=""><option>— استفاده از inbound گروه (پیش‌فرض) —</option><option>AVA — Ava test (5)</option><option>AVA — Ava max (3)</option></Select>
                <div className="mt-2 rounded-xl border border-amber-500/15 bg-amber-500/10 p-3 text-xs leading-relaxed text-amber-200 break-safe">
                  اگر پلن باید دقیقاً داخل یک گروه پنل ساخته شود (Ava test / Ava max)، آن را اینجا انتخاب کنید تا <code className="rounded bg-black/30 px-1 break-all">group_ids=[ID]</code> به پنل ارسال شود و سابسکرایب خالی (AVANET 0) نماند.
                </div>
              </div>
              <div className="rounded-2xl border border-sky-500/15 bg-sky-500/10 p-3 text-xs leading-relaxed text-sky-200 break-safe">
                <div className="font-black text-sky-100 flex items-center gap-1.5"><Settings2 className="h-3.5 w-3.5"/> راهنما Outbound Center</div>
                مسیر پنل: <code className="rounded bg-black/30 px-1 break-all">ویرایش کاربر → گروه‌ها → Ava test ✔️</code> — هر گروه یک ID عددی دارد در <code className="rounded bg-black/30 px-1">/api/groups</code>. این ID را همین‌جا ست کنید.
              </div>
            </div>
          </Accordion>
          <Accordion title="قیمت و حجم" icon={<DollarSign className="h-4 w-4" />}>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
              <div><Label>قیمت (تومان)</Label><Input type="number" placeholder="89000" /></div>
              <div><Label>قیمت هر گیگ</Label><Input type="number" placeholder="0" /></div>
              <div><Label>مدت (روز)</Label><Input type="number" defaultValue={30} /></div>
              <div><Label>حجم (GB) 0=دلخواه</Label><Input type="number" placeholder="30" /></div>
              <div className="md:col-span-4 flex flex-wrap items-center gap-4">
                <label className="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" defaultChecked className="rounded border-white/20 bg-white/5" /> قابل تمدید</label>
                <label className="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" defaultChecked className="rounded border-white/20 bg-white/5" /> فعال</label>
                <div className="mr-auto flex gap-2"><Button>ذخیره پلن</Button><Button variant="secondary" onClick={() => setEditing(false)}>انصراف</Button></div>
              </div>
            </div>
          </Accordion>
        </div>
      )}

      <Card className="overflow-hidden">
        <div className="flex items-center gap-2 px-5 py-3 text-xs font-black text-zinc-400"><Layers className="h-3.5 w-3.5 text-violet-400"/> لیست پلن‌ها — جدول ریسپانسیو بدون بیرون‌زدگی</div>
        <TableWrap>
          <table className="w-full min-w-[720px] text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold text-zinc-400">
              <tr><th className="px-4 py-3 text-right whitespace-nowrap">#</th><th className="px-4 py-3 text-right">پلن</th><th className="px-4 py-3 text-right whitespace-nowrap">سرویس</th><th className="px-4 py-3 text-right whitespace-nowrap">گروه کانفیگ</th><th className="px-4 py-3 text-right whitespace-nowrap">گروه پنل</th><th className="px-4 py-3 text-right whitespace-nowrap">قیمت</th><th className="px-4 py-3 text-right whitespace-nowrap">وضعیت</th><th className="px-4 py-3 text-right whitespace-nowrap">عملیات</th></tr>
            </thead>
            <tbody className="divide-y divide-white/[0.06]">
              {mockPlans.map(p => (
                <tr key={p.id} className="hover:bg-white/[0.04] transition-colors">
                  <td className="px-4 py-3 font-mono text-zinc-400">{p.id}</td>
                  <td className="px-4 py-3 min-w-[220px]"><div className="flex items-center gap-2 font-bold text-white"><Package className="h-4 w-4 shrink-0 text-violet-400" /> <span className="truncate">{p.name}</span></div><div className="text-xs text-zinc-500">{p.period} · {p.volume}</div></td>
                  <td className="px-4 py-3 whitespace-nowrap"><span className="inline-flex items-center gap-1 rounded-full bg-white/5 px-2.5 py-1 text-xs font-bold text-zinc-300 border border-white/10"><Layers className="h-3 w-3" /> {p.service}</span></td>
                  <td className="px-4 py-3 text-zinc-300 whitespace-nowrap">{p.group}</td>
                  <td className="px-4 py-3 whitespace-nowrap">{p.panelGroup.includes('—') ? <span className="text-xs text-zinc-500">از گروه</span> : <Badge tone="violet">{p.panelGroup}</Badge>}</td>
                  <td className="px-4 py-3 font-black text-white whitespace-nowrap">{p.price} ت</td>
                  <td className="px-4 py-3 whitespace-nowrap"><Badge tone={p.active ? 'emerald' : 'zinc'}>{p.active ? 'فعال' : 'غیرفعال'}</Badge></td>
                  <td className="px-4 py-3 whitespace-nowrap"><div className="flex gap-1.5"><Button size="sm" variant="secondary" className="h-7 gap-1"><Pencil className="h-3.5 w-3.5" /> ویرایش</Button><Button size="sm" variant="ghost" className="h-7 w-7 p-0"><Trash2 className="h-3.5 w-3.5" /></Button></div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </TableWrap>
      </Card>
    </div>
  )
}
