import { Card, CardHeader, Badge, Button, Input, Select, Label } from '../components/ui'
import { Layers, AlertTriangle, CheckCircle2, Plus } from 'lucide-react'

export default function Groups(){
  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div><h1 className="text-xl font-black text-white">گروه‌های کانفیگ</h1><p className="text-sm text-zinc-500">هر گروه = یک پنل + یک Group ID واقعی (Ava test / Ava max) — برای جلوگیری از AVANET 0</p></div>
        <Button className="gap-2"><Plus className="h-4 w-4"/> افزودن گروه</Button>
      </div>

      <div className="rounded-2xl border border-amber-500/15 bg-amber-500/10 p-4 text-sm leading-relaxed text-amber-100">
        <div className="flex items-center gap-2 font-black"><AlertTriangle className="h-4 w-4"/> نکته Outbound Center</div>
        برای اینکه لینک کاربر خالی نماند، هر گروه باید دقیقاً به یک <b>گروه پنل</b> (مثل Ava test) وصل باشد. تصویر پنل شما: <code className="rounded bg-black/30 px-1">ویرایش کاربر → گروه‌ها → Ava test ✔️</code> — همین گروه را اینجا انتخاب کنید.
      </div>

      <Card>
        <CardHeader><div className="font-black text-white">افزودن گروه کانفیگ</div><Badge tone="violet">همگام‌سازی از پنل</Badge></CardHeader>
        <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
          <div><Label>نام گروه (نمایشی)</Label><Input placeholder="مثلاً Ava test — تست" /></div>
          <div><Label>پنل</Label><Select><option>پاسارگاد اصلی (pasarguard)</option><option>مرزبان EU</option></Select></div>
          <div><Label>گروه پنل (Group ID)</Label><Select><option>— انتخاب گروه همگام‌شده —</option><option>Ava test (5)</option><option>Ava max (3)</option><option>Ava light (7)</option></Select></div>
          <div className="md:col-span-3 flex gap-2"><Button>ذخیره</Button><Button variant="secondary">انصراف</Button></div>
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-white/[0.04] text-xs font-bold text-zinc-400"><tr><th className="px-4 py-3 text-right">#</th><th className="px-4 py-3 text-right">نام گروه</th><th className="px-4 py-3 text-right">پنل</th><th className="px-4 py-3 text-right">Group ID</th><th className="px-4 py-3 text-right">وضعیت</th><th className="px-4 py-3 text-right">عملیات</th></tr></thead>
            <tbody className="divide-y divide-white/[0.06]">
              {[
                {id:1,name:'Ava test — تست',panel:'پاسارگاد اصلی',gid:'5',ok:true},
                {id:2,name:'Ava max — ویژه',panel:'پاسارگاد اصلی',gid:'3',ok:true},
                {id:3,name:'VIP بدون گروه',panel:'مرزبان EU',gid:'—',ok:false},
              ].map(g=>(
                <tr key={g.id} className="hover:bg-white/[0.04]">
                  <td className="px-4 py-3 font-mono text-zinc-400">{g.id}</td>
                  <td className="px-4 py-3 font-bold text-white">{g.name}</td>
                  <td className="px-4 py-3 text-zinc-300">{g.panel}</td>
                  <td className="px-4 py-3"><code className={`rounded px-2 py-1 text-xs ${g.ok?'bg-violet-500/15 text-violet-200 border border-violet-500/20':'bg-red-500/15 text-red-200 border border-red-500/20'}`}>{g.gid}</code> {!g.ok && <Badge tone="red" className="mr-2">خالی!</Badge>}</td>
                  <td className="px-4 py-3">{g.ok?<span className="inline-flex items-center gap-1 text-emerald-300 text-xs font-bold"><CheckCircle2 className="h-3.5 w-3.5"/> فعال</span>:<span className="text-zinc-500 text-xs">غیرفعال</span>}</td>
                  <td className="px-4 py-3"><div className="flex gap-1.5"><Button size="sm" variant="secondary" className="h-7 text-xs">ویرایش</Button><Button size="sm" variant="ghost" className="h-7 text-xs">حذف</Button></div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  )
}
