import { Card, CardHeader, Badge, Button, Input, Select, Label } from '../components/ui'
import { HardDrive, Activity, RefreshCw, Plus, Settings, Globe, Shield, Zap } from 'lucide-react'

const panels = [
  { id: 1, name: 'پاسارگاد اصلی', type: 'pasarguard', url: 'panel.outboundcenterpanel.ir', status: 'فعال', latency: '42ms', groups: 4, lastSync: '۲ دقیقه پیش' },
  { id: 2, name: 'مرزبان EU', type: 'marzban', url: 'eu.example.com', status: 'فعال', latency: '89ms', groups: 12, lastSync: '۱ ساعت پیش' },
  { id: 3, name: 'X-UI Node 3', type: 'xui_multi', url: 'x3.example.com', status: 'غیرفعال', latency: '—', groups: 0, lastSync: '—' },
]

export default function Panels() {
  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-black text-white">پنل‌های VPN</h1>
          <p className="text-sm text-zinc-500">اتصال چندپنلی · Pasarguard / Marzban / Marzneshin / X-UI · همگام‌سازی گروه‌ها</p>
        </div>
        <Button className="gap-2"><Plus className="h-4 w-4" /> افزودن پنل</Button>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {panels.map(p => (
          <Card key={p.id} className="group p-4 hover:border-violet-500/20 transition-colors">
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-[0_8px_20px_rgba(124,58,237,0.35)]">
                  <HardDrive className="h-5 w-5" />
                </div>
                <div>
                  <div className="text-sm font-black text-white">{p.name}</div>
                  <div className="font-mono text-xs text-zinc-500">{p.url}</div>
                </div>
              </div>
              <Badge tone={p.status==='فعال'?'emerald':'zinc'}>{p.status}</Badge>
            </div>
            <div className="mt-4 grid grid-cols-3 gap-2 text-center">
              <div className="rounded-xl bg-white/[0.04] py-2 border border-white/5">
                <div className="text-[11px] font-bold text-zinc-500">نوع</div>
                <div className="text-xs font-black text-white">{p.type}</div>
              </div>
              <div className="rounded-xl bg-white/[0.04] py-2 border border-white/5">
                <div className="text-[11px] font-bold text-zinc-500">گروه‌ها</div>
                <div className="text-xs font-black text-violet-300">{p.groups}</div>
              </div>
              <div className="rounded-xl bg-white/[0.04] py-2 border border-white/5">
                <div className="text-[11px] font-bold text-zinc-500">تاخیر</div>
                <div className="text-xs font-black text-white">{p.latency}</div>
              </div>
            </div>
            <div className="mt-3 flex items-center gap-2">
              <Button size="sm" variant="secondary" className="flex-1 gap-1.5"><Activity className="h-3.5 w-3.5" /> تست</Button>
              <Button size="sm" variant="ghost" className="flex-1 gap-1.5 border border-white/10"><RefreshCw className="h-3.5 w-3.5" /> همگام‌سازی</Button>
            </div>
            <div className="mt-2 text-center text-[11px] text-zinc-500">آخرین همگام: {p.lastSync}</div>
          </Card>
        ))}
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-2 font-black text-white"><Settings className="h-4 w-4 text-violet-400" /> افزودن / ویرایش پنل</div>
          <Badge tone="sky">API Key / Password</Badge>
        </CardHeader>
        <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
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
      </Card>
    </div>
  )
}
