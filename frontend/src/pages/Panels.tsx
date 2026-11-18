import { Card, Badge, Button, Input, Select, Label, Accordion, TableWrap } from '../components/ui'
import { HardDrive, Activity, RefreshCw, Plus, Settings, Globe, Shield, Zap, Server, KeyRound } from 'lucide-react'

const panels = [
  { id: 1, name: 'پاسارگاد اصلی', type: 'pasarguard', url: 'panel.outboundcenterpanel.ir', status: 'فعال', latency: '42ms', groups: 4, lastSync: '۲ دقیقه پیش' },
  { id: 2, name: 'مرزبان EU', type: 'marzban', url: 'eu.example.com', status: 'فعال', latency: '89ms', groups: 12, lastSync: '۱ ساعت پیش' },
  { id: 3, name: 'X-UI Node 3', type: 'xui_multi', url: 'x3.example.com', status: 'غیرفعال', latency: '—', groups: 0, lastSync: '—' },
]

export default function Panels() {
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="min-w-0"><h1 className="truncate text-xl font-black text-white">پنل‌های VPN</h1><p className="break-safe text-sm text-zinc-500">اتصال چندپنلی · Pasarguard / Marzban / X-UI · همگام‌سازی گروه‌ها — بدون بیرون‌زدگی</p></div>
        <Button className="shrink-0 gap-2"><Plus className="h-4 w-4" /> افزودن پنل</Button>
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        {panels.map(p => (
          <Card key={p.id} className="group p-4 hover:border-violet-500/20 transition-colors min-w-0">
            <div className="flex items-start justify-between gap-2 min-w-0">
              <div className="flex min-w-0 items-center gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-[0_8px_20px_rgba(124,58,237,0.35)] ring-1 ring-white/10">
                  <HardDrive className="h-5 w-5" />
                </div>
                <div className="min-w-0">
                  <div className="truncate text-sm font-black text-white">{p.name}</div>
                  <div className="truncate font-mono text-xs text-zinc-500 break-all">{p.url}</div>
                </div>
              </div>
              <Badge tone={p.status==='فعال'?'emerald':'zinc'} className="shrink-0">{p.status}</Badge>
            </div>
            <div className="mt-4 grid grid-cols-3 gap-2 text-center">
              <div className="rounded-xl bg-white/[0.04] py-2 border border-white/5 min-w-0"><div className="text-[11px] font-bold text-zinc-500 truncate">نوع</div><div className="truncate text-xs font-black text-white">{p.type}</div></div>
              <div className="rounded-xl bg-white/[0.04] py-2 border border-white/5 min-w-0"><div className="text-[11px] font-bold text-zinc-500">گروه‌ها</div><div className="text-xs font-black text-violet-300">{p.groups}</div></div>
              <div className="rounded-xl bg-white/[0.04] py-2 border border-white/5 min-w-0"><div className="text-[11px] font-bold text-zinc-500">تاخیر</div><div className="text-xs font-black text-white">{p.latency}</div></div>
            </div>
            <div className="mt-3 flex items-center gap-2">
              <Button size="sm" variant="secondary" className="flex-1 gap-1.5 min-w-0"><Activity className="h-3.5 w-3.5 shrink-0" /> تست</Button>
              <Button size="sm" variant="ghost" className="flex-1 gap-1.5 min-w-0 border border-white/10"><RefreshCw className="h-3.5 w-3.5 shrink-0" /> همگام</Button>
            </div>
            <div className="mt-2 truncate text-center text-[11px] text-zinc-500">آخرین همگام: {p.lastSync}</div>
          </Card>
        ))}
      </div>

      <Accordion title="افزودن / ویرایش پنل" icon={<Settings className="h-4 w-4"/>} badge={<Badge tone="sky">API Key / Password</Badge>}>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div><Label>نام دلخواه پنل</Label><Input placeholder="مثلاً پاسارگاد اصلی" /></div>
          <div><Label>نوع پنل</Label><Select><option>pasarguard — پاسارگاد</option><option>marzban</option><option>marzneshin</option><option>xui_multi</option></Select></div>
          <div><Label>آدرس URL</Label><Input placeholder="https://panel.example.com" dir="ltr" /></div>
          <div><Label>نام کاربری</Label><Input /></div>
          <div><Label>رمز عبور</Label><Input type="password" /></div>
          <div><Label>API Key / Token</Label><Input placeholder="••••••••" /></div>
          <div className="md:col-span-2"><Label>proxy_settings (JSON)</Label><Input dir="ltr" placeholder='{"vless":{},"vmess":{},"trojan":{}}' /></div>
          <div><Label>inbounds / group_ids</Label><Input dir="ltr" placeholder="[1,2]" /></div>
          <div className="md:col-span-3 flex gap-2"><Button>ذخیره پنل</Button><Button variant="secondary">انصراف</Button></div>
        </div>
      </Accordion>

      <Accordion title="راهنمای اتصال Outbound" icon={<Server className="h-4 w-4"/>}>
        <div className="text-sm leading-relaxed text-zinc-300 break-safe">
          <div className="rounded-xl border border-violet-500/15 bg-violet-500/10 p-3 text-xs leading-relaxed text-violet-200">
            برای پاسارگاد: <code className="rounded bg-black/30 px-1 break-all">X-API-Key</code> یا <code className="rounded bg-black/30 px-1">username/password → /api/admin/token</code> را پر کنید، سپس «همگام‌سازی» بزنید تا گروه‌های Ava ... با ID بیایند.
          </div>
          <div className="text-xs text-zinc-500">هر پنل می‌تواند چند گروه داشته باشد؛ گروه‌های همگام‌شده در <code className="rounded bg-black/30 px-1">panels.capabilities_json</code> ذخیره می‌شود و در پلن‌ها/گروه‌ها نمایش داده می‌شود.</div>
        </div>
      </Accordion>
    </div>
  )
}
