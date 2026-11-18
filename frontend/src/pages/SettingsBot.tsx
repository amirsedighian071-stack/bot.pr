import { Card, CardHeader, Button, Input, Label, Badge } from '../components/ui'
import { Bot, Globe, Shield, Link2, Copy, Rocket } from 'lucide-react'

export default function SettingsBot(){
  return (
    <div className="w-full min-w-0 max-w-4xl space-y-5 overflow-x-hidden">
      <h1 className="text-xl font-black text-white">تنظیمات ربات & وب‌هوک</h1>
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card className="p-5">
          <div className="flex items-center gap-2 font-black text-white"><Bot className="h-4 w-4 text-violet-400"/> تنظیم خودکار وب‌هوک</div>
          <div className="mt-4 space-y-3">
            <div><Label>توکن ربات *</Label><Input placeholder="123456789:ABC..." dir="ltr" /></div>
            <div><Label>آیدی عددی ادمین *</Label><Input placeholder="123456789" /></div>
            <div className="rounded-2xl border border-violet-500/15 bg-violet-500/10 p-3">
              <div className="text-xs font-bold text-violet-200">آدرس تشخیص خودکار</div>
              <div className="mt-1 break-all font-mono text-xs text-violet-300">https://example.com/public/webhook.php</div>
              <div className="mt-1 text-[11px] text-zinc-500">Secret خودکار: ••••••••</div>
            </div>
            <Button className="w-full gap-2"><Rocket className="h-4 w-4"/> تنظیم خودکار وب‌هوک</Button>
          </div>
        </Card>
        <Card className="p-5">
          <div className="font-black text-white flex items-center gap-2"><Shield className="h-4 w-4 text-emerald-400"/> وضعیت وب‌هوک</div>
          <div className="mt-4 space-y-2 text-sm">
            <div className="flex justify-between"><span className="text-zinc-500">URL</span><span className="font-mono text-xs text-white break-all">https://.../webhook.php</span></div>
            <div className="flex justify-between"><span className="text-zinc-500">پیام در صف</span><span className="font-black text-white">0</span></div>
            <div className="rounded-xl bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-200">✓ وب‌هوک متصل است</div>
            <div className="flex gap-2"><Button variant="secondary" className="flex-1 gap-2"><Copy className="h-3.5 w-3.5"/> کپی آدرس</Button><Button variant="ghost" className="flex-1 text-red-300">حذف وب‌هوک</Button></div>
          </div>
        </Card>
      </div>
    </div>
  )
}
