import { useState } from 'react'
import { ShieldCheck, Lock, User, Sparkles, ArrowLeft } from 'lucide-react'
import { Button, Input, Label } from '../components/ui'

export default function Login(){
  const [loading,setLoading]=useState(false)
  return (
    <div className="min-h-screen grid place-items-center bg-[#050507] p-4 relative overflow-hidden">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(139,92,246,0.15),transparent_55%),radial-gradient(ellipse_at_bottom,rgba(124,58,237,0.10),transparent_50%)]" />
      <div className="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-violet-600/20 blur-[80px]" />
      <div className="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-indigo-600/15 blur-[80px]" />

      <div className="relative w-full max-w-[420px] overflow-hidden rounded-[28px] border border-white/[0.08] bg-[rgba(16,16,20,0.85)] shadow-[0_24px_60px_rgba(0,0,0,0.55),0_0_40px_rgba(139,92,246,0.15)] backdrop-blur-2xl">
        <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/40 to-transparent" />
        <div className="p-8">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-[0_12px_30px_rgba(124,58,237,0.45)]">
            <ShieldCheck className="h-7 w-7 text-white" />
          </div>
          <h1 className="mt-4 text-center text-[22px] font-black tracking-tight text-white">ورود به پنل AVA</h1>
          <p className="mt-1 text-center text-sm text-zinc-500">پنل هوشمند فروش و مدیریت VPN — Dark Luxe</p>

          <form onSubmit={e=>{e.preventDefault(); setLoading(true); setTimeout(()=>location.href='/', 800)}} className="mt-6 space-y-4">
            <div>
              <Label>نام کاربری</Label>
              <div className="relative">
                <User className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
                <Input placeholder="admin" className="pr-9" required />
              </div>
            </div>
            <div>
              <Label>رمز عبور</Label>
              <div className="relative">
                <Lock className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
                <Input type="password" placeholder="••••••••" className="pr-9" required />
              </div>
            </div>
            <Button type="submit" className="w-full gap-2" disabled={loading}>
              {loading ? 'در حال ورود...' : <><Sparkles className="h-4 w-4"/> ورود به پنل <ArrowLeft className="h-4 w-4" /></>}
            </Button>
            <div className="text-center text-xs text-zinc-500">نسخه 1.0.0 · PHP 8.2+ · <span className="text-violet-300">Dark Only</span></div>
          </form>
        </div>
        <div className="border-t border-white/[0.06] bg-black/20 px-6 py-3 text-center text-[11px] text-zinc-500">محافظت‌شده · Glass · Neon</div>
      </div>
    </div>
  )
}
