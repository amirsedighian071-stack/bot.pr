import { Accordion, Button, Input, Label, Textarea, Badge } from '../components/ui'
import { Settings, Coins, Link2, MessageSquare, Shield, Wallet, Gift } from 'lucide-react'

export default function SettingsGeneral(){
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <div className="min-w-0">
        <h1 className="truncate text-xl font-black text-white">تنظیمات عمومی & پیام‌ها</h1>
        <p className="break-safe text-sm text-zinc-500">هر بخش به‌صورت آکاردئونی مجزا — باز/بسته کنید تا صفحه خلوت بماند</p>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
          <Accordion title="مالی و کارمزد" icon={<Wallet className="h-4 w-4"/>} badge={<Badge tone="violet">تومان</Badge>} defaultOpen>
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div><Label>کارمزد (تومان)</Label><Input defaultValue={3000} /></div>
              <div><Label>حداقل مبلغ سفارش</Label><Input defaultValue={10000} /></div>
              <div className="sm:col-span-2 rounded-xl border border-white/10 bg-[#0b0f19] p-3 text-xs leading-relaxed text-zinc-400 break-safe">کارمزد به مبلغ نهایی اضافه می‌شود و در پیش‌فاکتور نمایش داده می‌شود.</div>
            </div>
          </Accordion>

          <Accordion title="تست رایگان (Outbound حیاتی)" icon={<Shield className="h-4 w-4"/>} badge={<Badge tone="cyan">AVANET 0 fix</Badge>} defaultOpen>
            <div className="grid grid-cols-2 gap-3">
              <div><Label>حجم تست (MB)</Label><Input defaultValue={100} /></div>
              <div><Label>روز تست</Label><Input defaultValue={1} /></div>
              <div><Label>سقف تست روزانه</Label><Input placeholder="0=نامحدود" /></div>
              <div><Label>سقف کل تست</Label><Input placeholder="0=نامحدود" /></div>
              <div className="col-span-2">
                <Label>گروه پیش‌فرض تست رایگان</Label>
                <select className="h-10 w-full min-w-0 rounded-xl border border-white/10 bg-[#111727] px-3 text-sm text-white focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20">
                  <option>— پیش‌فرض: اولین گروه فعال —</option><option>Ava test — 5 (پاسارگاد اصلی)</option><option>Ava max — 3</option>
                </select>
                <div className="mt-1.5 rounded-xl border border-amber-500/15 bg-amber-500/10 p-2.5 text-xs leading-relaxed text-amber-200 break-safe">باید دقیقاً گروهی با Group ID پنل باشد، وگرنه تست با لینک خالی (AVANET 0) ساخته می‌شود.</div>
              </div>
            </div>
          </Accordion>

          <Accordion title="توکن و جوایز" icon={<Gift className="h-4 w-4"/>}>
            <div className="grid grid-cols-3 gap-3">
              <div><Label>توکن هر دعوت</Label><Input defaultValue={1} /></div>
              <div><Label>5GB توکن</Label><Input defaultValue={4} /></div>
              <div><Label>10GB توکن</Label><Input defaultValue={7} /></div>
              <div className="col-span-3 rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs text-zinc-500 break-safe">کاربر با دعوت و خرید توکن می‌گیرد و می‌تواند به گیگ یا نقدی تبدیل کند.</div>
            </div>
          </Accordion>
        </div>

        <div className="space-y-4 min-w-0">
          <Accordion title="لینک‌ها و پشتیبانی" icon={<Link2 className="h-4 w-4"/>} defaultOpen>
            <div className="space-y-3">
              <div><Label>لینک کانال پشتیبانی</Label><Input placeholder="https://t.me/..." dir="ltr" className="break-all" /></div>
              <div><Label>لینک آموزش</Label><Input placeholder="https://t.me/..." dir="ltr" /></div>
              <div><Label>لینک دانلود اپ</Label><Input placeholder="https://..." dir="ltr" /></div>
            </div>
          </Accordion>

          <Accordion title="متن‌های ربات" icon={<MessageSquare className="h-4 w-4"/>}>
            <div className="space-y-3">
              <div><Label>خوش‌آمدگویی (متغیر: {`{first_name}`})</Label><Textarea rows={3} defaultValue="سلام {first_name} عزیز 👋 به ربات خوش آمدید!" /></div>
              <div><Label>متن تست موفق</Label><Textarea rows={2} placeholder="تست شما فعال شد..." /></div>
              <div><Label>متن دعوت</Label><Textarea rows={2} placeholder="لینک دعوت شما: ..." /></div>
            </div>
          </Accordion>

          <Accordion title="پیشرفته" icon={<Settings className="h-4 w-4"/>}>
            <div className="space-y-3">
              <div><Label>متن سوالات متداول (FAQ)</Label><Textarea rows={4} placeholder="سوالات متداول..." /></div>
              <div className="rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs text-zinc-500 break-safe">متغیرهای پویا: <code className="rounded bg-black/40 px-1">{`{user_id} {username} {volume}`}</code></div>
            </div>
          </Accordion>
        </div>
      </div>

      <div className="flex gap-2"><Button>ذخیره همه تنظیمات</Button><Button variant="secondary">انصراف</Button></div>
    </div>
  )
}
