import { Card, Badge, Button, Input, Select, Label, Accordion, TableWrap } from '../components/ui'
import { Layers, AlertTriangle, CheckCircle2, Plus, HardDrive, Settings } from 'lucide-react'

export default function Groups(){
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="min-w-0"><h1 className="truncate text-xl font-black text-white">گروه‌های کانفیگ</h1><p className="break-safe text-sm text-zinc-500">هر گروه = یک پنل + یک Group ID واقعی (Ava test / Ava max) — برای جلوگیری از AVANET 0</p></div>
        <Button className="shrink-0 gap-2"><Plus className="h-4 w-4"/> افزودن گروه</Button>
      </div>

      <div className="rounded-2xl border border-amber-500/15 bg-amber-500/10 p-4 text-sm leading-relaxed text-amber-100 break-safe">
        <div className="flex items-center gap-2 font-black"><AlertTriangle className="h-4 w-4 shrink-0"/> نکته Outbound Center</div>
        برای اینکه لینک کاربر خالی نماند، هر گروه باید دقیقاً به یک <b>گروه پنل</b> (مثل Ava test) وصل باشد. تصویر پنل شما: <code className="rounded bg-black/30 px-1 break-all">ویرایش کاربر → گروه‌ها → Ava test ✔️</code> — همین گروه را اینجا انتخاب کنید. اگر لیست خالی است، پنل‌ها → همگام‌سازی بزنید.
      </div>

      <Accordion title="افزودن / ویرایش گروه کانفیگ" icon={<Settings className="h-4 w-4"/>} defaultOpen badge={<Badge tone="violet">همگام‌سازی</Badge>}>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div><Label>نام گروه (نمایشی)</Label><Input placeholder="مثلاً Ava test — تست" /></div>
          <div><Label>پنل</Label><Select><option>پاسارگاد اصلی (pasarguard)</option><option>مرزبان EU</option></Select></div>
          <div><Label>گروه پنل (Group ID)</Label><Select><option>— انتخاب گروه همگام‌شده —</option><option>Ava test (5)</option><option>Ava max (3)</option><option>Ava light (7)</option></Select><div className="mt-1 text-[11px] text-zinc-500 break-safe">اگر همگام نشده، شناسه عددی را دستی وارد کنید.</div></div>
          <div className="md:col-span-3 flex gap-2"><Button>ذخیره گروه</Button><Button variant="secondary">انصراف</Button></div>
        </div>
      </Accordion>

      <Card className="overflow-hidden">
        <div className="flex items-center gap-2 px-5 py-3 text-xs font-black text-zinc-400"><HardDrive className="h-3.5 w-3.5 text-violet-400"/> جدول گروه‌ها — ریسپانسیو کامل، بدون بیرون‌زدگی</div>
        <TableWrap>
          <table className="w-full min-w-[620px] text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold text-zinc-400"><tr><th className="px-4 py-3 text-right whitespace-nowrap">#</th><th className="px-4 py-3 text-right">نام گروه</th><th className="px-4 py-3 text-right whitespace-nowrap">پنل</th><th className="px-4 py-3 text-right whitespace-nowrap">Group ID</th><th className="px-4 py-3 text-right whitespace-nowrap">وضعیت</th><th className="px-4 py-3 text-right whitespace-nowrap">عملیات</th></tr></thead>
            <tbody className="divide-y divide-white/[0.06]">
              {[
                {id:1,name:'Ava test — تست',panel:'پاسارگاد اصلی',gid:'5',ok:true},
                {id:2,name:'Ava max — ویژه',panel:'پاسارگاد اصلی',gid:'3',ok:true},
                {id:3,name:'VIP بدون گروه',panel:'مرزبان EU',gid:'—',ok:false},
              ].map(g=>(
                <tr key={g.id} className="hover:bg-white/[0.04] transition-colors">
                  <td className="px-4 py-3 font-mono text-zinc-400">{g.id}</td>
                  <td className="px-4 py-3 font-bold text-white whitespace-nowrap">{g.name}</td>
                  <td className="px-4 py-3 text-zinc-300 whitespace-nowrap">{g.panel}</td>
                  <td className="px-4 py-3 whitespace-nowrap"><code className={`rounded px-2 py-1 text-xs ${g.ok?'bg-violet-500/15 text-violet-200 border border-violet-500/20':'bg-red-500/15 text-red-200 border border-red-500/20'}`}>{g.gid}</code> {!g.ok && <Badge tone="red" className="mr-2">خالی!</Badge>}</td>
                  <td className="px-4 py-3 whitespace-nowrap">{g.ok?<span className="inline-flex items-center gap-1 text-emerald-300 text-xs font-bold"><CheckCircle2 className="h-3.5 w-3.5"/> فعال</span>:<span className="text-zinc-500 text-xs">غیرفعال</span>}</td>
                  <td className="px-4 py-3 whitespace-nowrap"><div className="flex gap-1.5"><Button size="sm" variant="secondary" className="h-7 text-xs">ویرایش</Button><Button size="sm" variant="ghost" className="h-7 text-xs">حذف</Button></div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </TableWrap>
      </Card>
    </div>
  )
}
