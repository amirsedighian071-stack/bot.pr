import { PropsWithChildren, HTMLAttributes, ReactNode, useState } from 'react'
import { motion } from 'framer-motion'
import { cn } from '../lib/utils'
import { ChevronDown } from 'lucide-react'

export function Card({ className, children, ...props }: PropsWithChildren<HTMLAttributes<HTMLDivElement>>) {
  return (
    <div
      className={cn(
        'relative overflow-hidden rounded-[22px] border border-white/[0.08] bg-[rgba(16,20,32,0.78)] shadow-[0_10px_36px_rgba(0,0,0,0.45)] backdrop-blur-[18px]',
        className
      )}
      {...props}
    >
      <div className="pointer-events-none absolute inset-0 rounded-[22px] bg-gradient-to-br from-violet-500/[0.07] via-transparent to-cyan-500/[0.05]" />
      <div className="relative">{children}</div>
    </div>
  )
}

export function CardHeader({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('flex min-w-0 items-center justify-between gap-3 border-b border-white/[0.07] px-5 py-4', className)} {...props} />
}

export function CardBody({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('min-w-0 p-5', className)} {...props} />
}

export function Button({ variant = 'primary', size = 'md', className, children, ...props }: any) {
  const variants: Record<string, string> = {
    primary: 'bg-gradient-to-l from-violet-600 via-violet-600 to-indigo-600 text-white shadow-[0_8px_22px_rgba(124,58,237,0.38)] hover:shadow-[0_12px_30px_rgba(124,58,237,0.48)] hover:-translate-y-[1px] border border-white/10 active:translate-y-0',
    secondary: 'bg-white/[0.07] text-white border border-white/[0.10] hover:bg-white/[0.11] hover:border-white/15 backdrop-blur',
    ghost: 'bg-transparent text-zinc-400 hover:text-white hover:bg-white/[0.07] border border-transparent',
    danger: 'bg-gradient-to-l from-red-600 to-rose-600 text-white shadow-[0_8px_20px_rgba(239,68,68,0.32)] border border-white/10',
    success: 'bg-gradient-to-l from-emerald-600 to-teal-600 text-white border border-white/10 shadow-[0_8px_20px_rgba(16,185,129,0.28)]',
    neon: 'bg-[#0b0f19] text-violet-200 border border-violet-500/30 hover:border-violet-400/50 hover:bg-violet-500/10 shadow-[0_0_14px_rgba(139,92,246,0.25)]',
  }
  const sizes: Record<string, string> = {
    sm: 'h-8 px-3 text-xs rounded-xl',
    md: 'h-10 px-4 text-sm rounded-xl',
    lg: 'h-11 px-5 text-sm rounded-2xl',
    icon: 'h-9 w-9 rounded-xl p-0',
  }
  return (
    <motion.button
      whileTap={{ scale: 0.97 }}
      whileHover={{ scale: variant === 'primary' ? 1.015 : 1 }}
      transition={{ type: 'spring', stiffness: 420, damping: 20 }}
      className={cn('inline-flex shrink-0 items-center justify-center gap-2 font-bold transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/40 disabled:opacity-50 whitespace-nowrap', variants[variant], sizes[size], className)}
      {...props}
    >
      {children}
    </motion.button>
  )
}

export function Badge({ tone = 'zinc', className, ...props }: any) {
  const tones: Record<string, string> = {
    zinc: 'bg-white/10 text-zinc-300 border-white/10',
    violet: 'bg-violet-500/15 text-violet-200 border-violet-500/25 shadow-[0_0_10px_rgba(139,92,246,0.18)]',
    emerald: 'bg-emerald-500/15 text-emerald-200 border-emerald-500/20',
    amber: 'bg-amber-500/15 text-amber-200 border-amber-500/20',
    red: 'bg-red-500/15 text-red-200 border-red-500/20',
    sky: 'bg-sky-500/15 text-sky-200 border-sky-500/20',
    cyan: 'bg-cyan-500/15 text-cyan-200 border-cyan-500/20 shadow-[0_0_10px_rgba(6,182,212,0.15)]',
  }
  return <span className={cn('inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-bold leading-none', tones[tone], className)} {...props} />
}

export function Input(props: any) {
  const { className, ...rest } = props
  return <input {...rest} className={cn('h-10 w-full min-w-0 rounded-xl border border-white/10 bg-white/[0.06] px-3 text-sm text-white placeholder:text-zinc-500 focus:border-violet-500/40 focus:bg-white/[0.08] focus:outline-none focus:ring-2 focus:ring-violet-500/20 transition-all', className)} />
}

export function Select(props: any) {
  const { className, children, ...rest } = props
  return <select {...rest} className={cn('h-10 w-full min-w-0 rounded-xl border border-white/10 bg-[#111727] px-3 text-sm text-white focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20', className)}>{children}</select>
}

export function Textarea(props: any) {
  const { className, ...rest } = props
  return <textarea {...rest} className={cn('min-h-[96px] w-full min-w-0 rounded-xl border border-white/10 bg-white/[0.06] px-3 py-2.5 text-sm text-white placeholder:text-zinc-500 focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20', className)} />
}

export function Label(props: any) {
  return <label {...props} className={cn('mb-1.5 block text-xs font-bold tracking-wide text-zinc-400', props.className)} />
}

export function Empty({ title = 'موردی یافت نشد', desc }: { title?: string, desc?: string }) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 py-12 text-center">
      <div className="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-zinc-500">∅</div>
      <div className="text-sm font-bold text-zinc-300">{title}</div>
      {desc && <div className="max-w-sm text-xs leading-relaxed text-zinc-500 break-safe">{desc}</div>}
    </div>
  )
}

/* Table wrapper that guarantees no overflow */
export function TableWrap({ children }: { children: ReactNode }) {
  return (
    <div className="w-full overflow-hidden rounded-[18px] border border-white/[0.06] bg-[#0b0f19]/40">
      <div className="w-full overflow-x-auto [scrollbar-width:thin] [scrollbar-color:#7c3aed_#0b0f19]">
        {children}
      </div>
    </div>
  )
}

/* Accordion — separate, rounded, distinct */
export function Accordion({ title, icon, badge, defaultOpen = false, children }: { title: string, icon?: ReactNode, badge?: ReactNode, defaultOpen?: boolean, children: ReactNode }) {
  const [open, setOpen] = useState(defaultOpen)
  return (
    <Card className="overflow-hidden">
      <button
        onClick={() => setOpen(!open)}
        className="flex w-full items-center gap-3 px-5 py-4 text-right transition-colors hover:bg-white/[0.03]"
      >
        {icon && <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-violet-500/15 text-violet-300 border border-violet-500/20">{icon}</span>}
        <span className="min-w-0 flex-1 text-sm font-black text-white truncate">{title}</span>
        {badge && <span className="shrink-0">{badge}</span>}
        <span className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-zinc-400 transition-transform duration-300 ${open ? 'rotate-180 bg-violet-500/15 text-violet-200 border-violet-500/20' : ''}`}>
          <ChevronDown className="h-4 w-4" />
        </span>
      </button>
      <motion.div
        initial={false}
        animate={{ height: open ? 'auto' : 0, opacity: open ? 1 : 0 }}
        transition={{ duration: 0.28, ease: [0.32, 0.72, 0, 1] }}
        className="overflow-hidden"
      >
        <div className="border-t border-white/[0.07] p-5 bg-white/[0.015]">{children}</div>
      </motion.div>
    </Card>
  )
}

export function Tabs({ tabs, value, onChange }: { tabs: { id: string, label: string, icon?: ReactNode }[], value: string, onChange: (v: string) => void }) {
  return (
    <div className="flex w-full gap-1.5 overflow-x-auto rounded-2xl border border-white/10 bg-[#0b0f19] p-1.5 no-scrollbar">
      {tabs.map(t => (
        <button
          key={t.id}
          onClick={() => onChange(t.id)}
          className={`flex shrink-0 items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-black transition-all whitespace-nowrap ${value === t.id ? 'bg-gradient-to-l from-violet-600 to-indigo-600 text-white shadow-[0_6px_16px_rgba(124,58,237,0.35)]' : 'text-zinc-500 hover:text-white hover:bg-white/5'}`}
        >
          {t.icon}{t.label}
        </button>
      ))}
    </div>
  )
}
