import { PropsWithChildren, HTMLAttributes } from 'react'
import { motion } from 'framer-motion'
import { cn } from '../lib/utils'

export function Card({ className, children, ...props }: PropsWithChildren<HTMLAttributes<HTMLDivElement>>) {
  return (
    <div
      className={cn(
        'relative overflow-hidden rounded-[20px] border border-white/[0.07] bg-[rgba(22,22,26,0.78)] shadow-[0_8px_32px_rgba(0,0,0,0.35)] backdrop-blur-[18px]',
        'before:pointer-events-none before:absolute before:inset-0 before:rounded-[20px] before:p-px before:bg-gradient-to-br before:from-violet-500/20 before:via-transparent before:to-violet-500/10 before:[mask:linear-gradient(#fff_0_0)_content-box,linear-gradient(#fff_0_0)] before:[mask-composite:exclude]',
        className
      )}
      {...props}
    >
      {children}
    </div>
  )
}

export function CardHeader({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('flex items-center justify-between border-b border-white/[0.06] px-5 py-4', className)} {...props} />
}

export function CardBody({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('p-5', className)} {...props} />
}

export function Button({ variant = 'primary', size = 'md', className, ...props }: any) {
  const variants: Record<string, string> = {
    primary: 'bg-gradient-to-l from-violet-600 to-indigo-600 text-white shadow-[0_8px_20px_rgba(124,58,237,0.35)] hover:shadow-[0_12px_28px_rgba(124,58,237,0.45)] hover:-translate-y-px border border-white/10',
    secondary: 'bg-white/[0.06] text-white border border-white/[0.08] hover:bg-white/[0.10]',
    ghost: 'bg-transparent text-zinc-400 hover:text-white hover:bg-white/[0.06] border border-transparent',
    danger: 'bg-gradient-to-l from-red-600 to-rose-600 text-white shadow-[0_8px_20px_rgba(239,68,68,0.3)] border border-white/10',
    success: 'bg-gradient-to-l from-emerald-600 to-teal-600 text-white border border-white/10',
  }
  const sizes: Record<string, string> = {
    sm: 'h-8 px-3 text-xs rounded-xl',
    md: 'h-10 px-4 text-sm rounded-xl',
    lg: 'h-11 px-5 text-sm rounded-2xl',
    icon: 'h-9 w-9 rounded-xl',
  }
  return (
    <motion.button
      whileTap={{ scale: 0.97 }}
      whileHover={{ scale: variant === 'primary' ? 1.01 : 1 }}
      transition={{ type: 'spring', stiffness: 400, damping: 18 }}
      className={cn('inline-flex items-center justify-center gap-2 font-bold transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/40 disabled:opacity-50', variants[variant], sizes[size], className)}
      {...props}
    />
  )
}

export function Badge({ tone = 'zinc', className, ...props }: any) {
  const tones: Record<string, string> = {
    zinc: 'bg-white/10 text-zinc-300 border-white/10',
    violet: 'bg-violet-500/15 text-violet-200 border-violet-500/20',
    emerald: 'bg-emerald-500/15 text-emerald-200 border-emerald-500/20',
    amber: 'bg-amber-500/15 text-amber-200 border-amber-500/20',
    red: 'bg-red-500/15 text-red-200 border-red-500/20',
    sky: 'bg-sky-500/15 text-sky-200 border-sky-500/20',
  }
  return <span className={cn('inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold', tones[tone], className)} {...props} />
}

export function Input(props: any) {
  return <input {...props} className={cn('h-10 w-full rounded-xl border border-white/10 bg-white/[0.06] px-3 text-sm text-white placeholder:text-zinc-500 focus:border-violet-500/40 focus:bg-white/[0.08] focus:outline-none focus:ring-2 focus:ring-violet-500/20 transition-colors', props.className)} />
}

export function Select(props: any) {
  return <select {...props} className={cn('h-10 w-full rounded-xl border border-white/10 bg-[#1a1a20] px-3 text-sm text-white focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20', props.className)} />
}

export function Textarea(props: any) {
  return <textarea {...props} className={cn('min-h-[96px] w-full rounded-xl border border-white/10 bg-white/[0.06] px-3 py-2.5 text-sm text-white placeholder:text-zinc-500 focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20', props.className)} />
}

export function Label(props: any) {
  return <label {...props} className={cn('mb-1.5 block text-xs font-bold tracking-wide text-zinc-400', props.className)} />
}

export function Empty({ title = 'موردی یافت نشد', desc }: { title?: string, desc?: string }) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 py-12 text-center">
      <div className="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-zinc-500">∅</div>
      <div className="text-sm font-bold text-zinc-300">{title}</div>
      {desc && <div className="max-w-sm text-xs leading-relaxed text-zinc-500">{desc}</div>}
    </div>
  )
}
