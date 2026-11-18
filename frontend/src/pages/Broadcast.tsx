import { Card, Button, Label, Textarea, Input } from '../components/ui'
import { Megaphone, Send, Image as ImgIcon } from 'lucide-react'

export default function Broadcast(){
  return (
    <div className="w-full min-w-0 max-w-3xl space-y-5 overflow-x-hidden">
      <h1 className="text-xl font-black text-white">پیام همگانی</h1>
      <Card className="p-5 space-y-4">
        <div className="flex items-center gap-2 text-sm font-black text-white"><Megaphone className="h-4 w-4 text-violet-400"/> ارسال به همه کاربران</div>
        <div><Label>متن پیام</Label><Textarea placeholder="سلام کاربران عزیز... " rows={5} /></div>
        <div className="grid grid-cols-2 gap-4">
          <div><Label>فیلتر مخاطب</Label><select className="h-10 w-full rounded-xl border border-white/10 bg-[#1a1a20] px-3 text-sm text-white"><option>همه</option><option>خریداران</option><option>بدون اشتراک</option></select></div>
          <div><Label>رسانه (اختیاری)</Label><div className="flex gap-2"><Input placeholder="آپلود فایل" /><Button variant="secondary" size="icon"><ImgIcon className="h-4 w-4"/></Button></div></div>
        </div>
        <Button className="w-full gap-2"><Send className="h-4 w-4"/> ارسال همگانی</Button>
        <div className="rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs text-zinc-500">ارسال به‌صورت صف (queue) انجام می‌شود و در <code className="rounded bg-black/40 px-1">logs</code> ثبت خواهد شد.</div>
      </Card>
    </div>
  )
}
