import { Card, CardHeader, Button, Input, Label, Textarea, Badge } from '../components/ui'
import { Settings, Coins, Link2, MessageSquare } from 'lucide-react'

export default function SettingsGeneral(){
  return (
    <div className="space-y-5">
      <h1 className="text-xl font-black text-white">تنظیمات عمومی & پیام‌ها</h1>
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card className="p-5">
          <div className="font-black text-white flex items-center gap-2"><Settings className="h-4 w-4 text-violet-400"/> مالی & توکن & تست</div>
          <div className="mt-4 grid grid-cols-2 gap-3">
            <div><Label>کارمزد (تومان)</Label><Input defaultValue={3000} /></div>
            <div><Label>حجم تست (MB)</Label><Input defaultValue={100} /></div>
            <div><Label>روز تست</Label><Input defaultValue={1} /></div>
            <div><Label>سقف تست روزانه</Label><Input placeholder="0=نامحدود" /></div>
            <div className="col-span-2"><Label>گروه پیش‌فرض تست رایگان (برای جلوگیری از AVANET 0)</Label><select className="h-10 w-full rounded-xl border border-white/10 bg-[#1a1a20] px-3 text-sm text-white"><option>— پیش‌فرض: اولین گروه فعال —</option><option>Ava test — 5</option><option>Ava max — 3</option></select><div className="mt-1 text-[11px] text-zinc-500">باید دقیقاً گروهی با ID پنل باشد</div></div>
            <div><Label>توکن هر دعوت</Label><Input defaultValue={1} /></div>
            <div><Label>5GB توکن</Label><Input defaultValue={4} /></div>
          </div>
        </Card>
        <Card className="p-5">
          <div className="font-black text-white flex items-center gap-2"><MessageSquare className="h-4 w-4 text-sky-400"/> متن‌های ربات</div>
          <div className="mt-4 space-y-3">
            <div><Label>خوش‌آمدگویی</Label><Textarea rows={3} defaultValue="سلام {first_name} عزیز 👋 به ربات خوش آمدید!" /></div>
            <div><Label>متن تست موفق</Label><Textarea rows={2} /></div>
            <div><Label>لینک کانال پشتیبانی</Label><Input placeholder="https://t.me/..." dir="ltr" /></div>
            <div><Label>لینک آموزش</Label><Input placeholder="https://t.me/..." dir="ltr" /></div>
          </div>
        </Card>
      </div>
      <Button>ذخیره همه</Button>
    </div>
  )
}
