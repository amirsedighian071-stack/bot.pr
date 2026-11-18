import { Card, Button, Badge } from '../components/ui'
import { Database, Download, Upload, Clock } from 'lucide-react'
export default function Backups(){
  return (
    <div className="w-full min-w-0 max-w-3xl space-y-5 overflow-x-hidden">
      <h1 className="text-xl font-black text-white">بکاپ & بازیابی</h1>
      <Card className="p-5">
        <div className="flex items-center gap-2 font-black text-white"><Database className="h-4 w-4 text-violet-400"/> بکاپ دیتابیس</div>
        <div className="mt-4 flex gap-2"><Button className="gap-2"><Download className="h-4 w-4"/> دانلود بکاپ SQL</Button><Button variant="secondary" className="gap-2"><Upload className="h-4 w-4"/> بازگردانی</Button></div>
        <div className="mt-4 rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs text-zinc-500 flex items-center gap-2"><Clock className="h-3.5 w-3.5"/> آخرین بکاپ: امروز 14:32 — حجم 2.4MB</div>
      </Card>
    </div>
  )
}
