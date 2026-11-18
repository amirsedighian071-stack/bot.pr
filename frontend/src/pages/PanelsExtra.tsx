import { Card, Badge } from '../components/ui'
export function Placeholder({ title }: { title: string }) {
  return (
    <div className="w-full min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <h1 className="text-xl font-black text-white">{title}</h1>
      <Card className="p-8">
        <div className="rounded-2xl border border-dashed border-white/10 bg-white/[0.03] p-8 text-center">
          <div className="text-sm font-bold text-zinc-300">این بخش در نسخه React با همان منطق PHP پیاده‌سازی می‌شود</div>
          <div className="mt-1 text-xs text-zinc-500">API: <code className="rounded bg-black/30 px-1.5 py-0.5">/admin/api/{title}</code> — دیتابیس: MySQL — احراز: PHP Session</div>
          <Badge tone="violet" className="mt-3">Dark Luxe · Glass · Framer Motion</Badge>
        </div>
      </Card>
    </div>
  )
}
