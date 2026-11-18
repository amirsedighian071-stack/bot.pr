import { useState, useEffect } from 'react'
import { Outlet, NavLink, useLocation } from 'react-router-dom'
import { motion, AnimatePresence } from 'framer-motion'
import {
  LayoutDashboard, Bot, Settings, Megaphone, HardDrive,
  ShieldCheck, Layers, Package, Ticket, Users, Receipt,
  Archive, BarChart3, Database, Gift, Menu, X,
  LogOut, ChevronDown, Sparkles, Zap, Globe, Cpu, Search, Bell
} from 'lucide-react'

const navSections = [
  {
    title: 'ربات',
    icon: Bot,
    items: [
      { to: '/settings/bot', label: 'ربات & وب‌هوک', icon: Globe },
      { to: '/settings/general', label: 'تنظیمات عمومی', icon: Settings },
      { to: '/broadcast', label: 'پیام همگانی', icon: Megaphone },
    ]
  },
  {
    title: 'اتصالات',
    icon: HardDrive,
    items: [
      { to: '/panels', label: 'پنل‌های VPN', icon: HardDrive },
      { to: '/groups', label: 'گروه‌های کانفیگ', icon: Layers },
    ]
  },
  {
    title: 'فروش',
    icon: Package,
    items: [
      { to: '/services', label: 'سرویس‌ها', icon: Layers },
      { to: '/plans', label: 'پلن‌ها', icon: Package },
      { to: '/discounts', label: 'کد تخفیف', icon: Ticket },
      { to: '/orders', label: 'سفارش‌ها', icon: Receipt },
      { to: '/subscriptions', label: 'اشتراک‌ها', icon: ShieldCheck },
      { to: '/warehouse', label: 'انبار دستی', icon: Archive },
      { to: '/volumes', label: 'حجم‌ها', icon: Database },
    ]
  },
  {
    title: 'کاربران',
    icon: Users,
    items: [
      { to: '/users', label: 'کاربران', icon: Users },
      { to: '/tickets', label: 'تیکت‌ها & هدایا', icon: Gift },
    ]
  },
  {
    title: 'سیستم',
    icon: Cpu,
    items: [
      { to: '/reports', label: 'گزارش مالی', icon: BarChart3 },
      { to: '/backups', label: 'بکاپ', icon: Database },
    ]
  }
]

function Sidebar({ open, onClose }: { open: boolean, onClose: () => void }) {
  const [expanded, setExpanded] = useState<Record<string, boolean>>({ 'ربات': true })
  const location = useLocation()

  useEffect(() => {
    navSections.forEach(s => {
      if (s.items.some(i => location.pathname.startsWith(i.to))) {
        setExpanded(prev => ({ ...prev, [s.title]: true }))
      }
    })
  }, [location.pathname])

  // lock scroll on mobile when open
  useEffect(() => {
    if (open && window.innerWidth < 1024) {
      document.body.style.overflow = 'hidden'
      document.documentElement.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
      document.documentElement.style.overflow = ''
    }
    return () => {
      document.body.style.overflow = ''
      document.documentElement.style.overflow = ''
    }
  }, [open])

  return (
    <>
      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.22 }}
            className="fixed inset-0 z-40 bg-[#090d16]/55 backdrop-blur-[8px] lg:hidden"
            onClick={onClose}
          />
        )}
      </AnimatePresence>

      <motion.aside
        initial={false}
        animate={{ x: 0 }}
        className={`
          fixed inset-y-0 right-0 z-50 flex h-[100dvh] w-[300px] max-w-[86vw] flex-col
          border-l border-white/[0.08] bg-[#0b0f19]/95 backdrop-blur-[20px]
          lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:max-w-none
          ${open ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'}
          transition-transform duration-[340ms] ease-[cubic-bezier(0.32,0.72,0,1)]
          shadow-[-12px_0_48px_rgba(0,0,0,0.55)]
        `}
        style={{ willChange: 'transform' }}
      >
        {/* Brand — isolated card */}
        <div className="shrink-0 border-b border-white/[0.07] bg-gradient-to-br from-violet-500/[0.07] via-transparent to-cyan-500/[0.04] px-5 py-5">
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 via-violet-600 to-indigo-600 shadow-[0_0_22px_rgba(124,58,237,0.45),0_0_40px_rgba(6,182,212,0.15)] ring-1 ring-white/10">
              <ShieldCheck className="h-6 w-6 text-white" />
            </div>
            <div className="min-w-0 flex-1">
              <div className="truncate text-[15px] font-black leading-none tracking-tight text-white">AVA PANEL</div>
              <div className="mt-1 flex items-center gap-1.5 text-[11px] font-medium text-zinc-500">
                <span className="inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981] animate-pulse" />
                <span className="truncate">پنل هوشمند فروش VPN</span>
              </div>
            </div>
            <button onClick={onClose} aria-label="بستن منو" className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.06] text-zinc-300 backdrop-blur hover:bg-white/10 hover:text-white lg:hidden transition-colors">
              <X className="h-4 w-4" />
            </button>
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto overflow-x-hidden px-3 py-3 [scrollbar-width:thin] [scrollbar-color:#7c3aed_#0b0f19]">
          <NavLink
            to="/"
            onClick={() => window.innerWidth < 1024 && onClose()}
            className={({ isActive }) =>
              `mb-3 flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-black transition-all ${isActive
                ? 'bg-gradient-to-l from-violet-600 via-violet-600 to-indigo-600 text-white shadow-[0_10px_24px_rgba(124,58,237,0.38)] ring-1 ring-white/10'
                : 'text-zinc-400 hover:bg-white/[0.06] hover:text-white border border-transparent'}`
            }
          >
            <LayoutDashboard className="h-[18px] w-[18px] shrink-0" />
            <span className="truncate">داشبورد</span>
            <Sparkles className="mr-auto h-4 w-4 shrink-0 opacity-60" />
          </NavLink>

          {navSections.map(section => {
            const isOpen = !!expanded[section.title]
            return (
              <div key={section.title} className="mb-2 overflow-hidden rounded-2xl border border-transparent has-[.is-open]:border-white/[0.06] has-[.is-open]:bg-white/[0.02]">
                <button
                  onClick={() => setExpanded(p => ({ ...p, [section.title]: !p[section.title] }))}
                  className={`flex w-full items-center justify-between gap-2 rounded-2xl px-3 py-2.5 text-[11px] font-black tracking-[0.08em] transition-colors ${isOpen ? 'is-open text-violet-200 bg-white/[0.04]' : 'text-zinc-500 hover:bg-white/[0.04] hover:text-zinc-300'}`}
                >
                  <span className="flex min-w-0 items-center gap-2">
                    <section.icon className="h-3.5 w-3.5 shrink-0" />
                    <span className="truncate">{section.title}</span>
                  </span>
                  <ChevronDown className={`h-3 w-3 shrink-0 transition-transform duration-300 ${isOpen ? 'rotate-180 text-violet-300' : 'text-zinc-500'}`} />
                </button>

                <AnimatePresence initial={false}>
                  {isOpen && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: 'auto', opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{ duration: 0.26, ease: [0.32, 0.72, 0, 1] }}
                      className="overflow-hidden"
                    >
                      <div className="space-y-1 px-1.5 pb-2 pt-1">
                        {section.items.map(item => (
                          <NavLink
                            key={item.to}
                            to={item.to}
                            onClick={() => window.innerWidth < 1024 && onClose()}
                            className={({ isActive }) =>
                              `flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-[13px] font-medium transition-all ${isActive
                                ? 'bg-white/[0.08] text-white border-white/[0.08] shadow-[0_4px_14px_rgba(0,0,0,0.22)]'
                                : 'text-zinc-500 hover:bg-white/[0.05] hover:text-zinc-100 border-transparent hover:border-white/[0.04]'}`
                            }
                          >
                            <item.icon className="h-4 w-4 shrink-0 opacity-80" />
                            <span className="min-w-0 truncate">{item.label}</span>
                          </NavLink>
                        ))}
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
            )
          })}
        </nav>

        {/* footer — separate glass card */}
        <div className="shrink-0 border-t border-white/[0.07] bg-[#090d16]/60 p-3 backdrop-blur">
          <div className="rounded-2xl border border-violet-500/15 bg-gradient-to-br from-violet-500/[0.09] to-cyan-500/[0.06] p-3.5">
            <div className="flex items-center gap-2 text-xs font-black text-violet-200">
              <Zap className="h-3.5 w-3.5 shrink-0 text-violet-300" />
              <span className="truncate">نسخه 1.0.0 — PHP 8.2+</span>
            </div>
            <div className="mt-1 text-[11px] font-medium text-zinc-500">Dark Neon · Glass · 60fps · #0b0f19</div>
          </div>
        </div>
      </motion.aside>
    </>
  )
}

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const location = useLocation()

  // close on resize to desktop
  useEffect(() => {
    const onResize = () => {
      if (window.innerWidth >= 1024 && sidebarOpen) setSidebarOpen(false)
    }
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [sidebarOpen])

  return (
    <div className="min-h-screen w-full max-w-[100vw] overflow-x-hidden bg-[#090d16] text-zinc-100">
      <div className="flex min-h-screen w-full max-w-[100vw] overflow-x-hidden">
        <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

        <div className="flex min-w-0 flex-1 flex-col overflow-x-hidden">
          {/* Topbar — glass, isolated, no overflow */}
          <header className="sticky top-0 z-30 flex h-[64px] w-full shrink-0 items-center justify-between gap-2 border-b border-white/[0.07] bg-[#0b0f19]/75 px-3 backdrop-blur-[18px] supports-[backdrop-filter]:bg-[#0b0f19]/60 lg:px-6">
            <div className="flex min-w-0 items-center gap-2.5">
              <button
                onClick={() => setSidebarOpen(true)}
                aria-label="باز کردن منو"
                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.07] text-white shadow-[0_4px_14px_rgba(0,0,0,0.25)] backdrop-blur hover:bg-white/10 hover:border-violet-500/20 transition-colors lg:hidden"
              >
                <Menu className="h-5 w-5" />
              </button>

              <div className="hidden min-w-0 items-center gap-2.5 lg:flex">
                <div className="h-6 w-px shrink-0 bg-white/10" />
                <div className="shrink-0 text-sm font-black tracking-tight text-white">پنل مدیریت</div>
                <span className="hidden shrink-0 rounded-full border border-violet-500/25 bg-violet-500/10 px-2.5 py-1 text-[11px] font-black text-violet-200 shadow-[0_0_12px_rgba(139,92,246,0.22)] xl:inline-flex">Dark Neon · #0b0f19</span>
              </div>

              {/* search — hidden on small, truncated */}
              <div className="hidden min-w-0 items-center gap-2 rounded-full border border-white/[0.07] bg-white/[0.05] px-3 py-2 text-sm text-zinc-500 backdrop-blur md:flex">
                <Search className="h-4 w-4 shrink-0 text-zinc-500" />
                <input placeholder="جستجو سفارش، کاربر…" className="w-36 min-w-0 bg-transparent text-sm text-white placeholder:text-zinc-500 focus:outline-none lg:w-52" />
                <span className="hidden shrink-0 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-bold text-zinc-400 lg:inline">⌘K</span>
              </div>
            </div>

            <div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
              <button className="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/[0.07] bg-white/[0.05] text-zinc-400 backdrop-blur hover:bg-white/[0.08] hover:text-white transition-colors">
                <Bell className="h-[18px] w-[18px]" />
                <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-gradient-to-br from-violet-600 to-indigo-600 text-[10px] font-black text-white shadow-[0_4px_10px_rgba(124,58,237,0.45)] ring-1 ring-[#0b0f19]">3</span>
              </button>

              <div className="hidden items-center gap-3 rounded-full border border-white/[0.07] bg-white/[0.05] py-1 pl-1 pr-3 backdrop-blur md:flex">
                <img src={`https://i.pravatar.cc/100?img=15`} className="h-7 w-7 shrink-0 rounded-full object-cover ring-1 ring-white/10" alt="" />
                <div className="min-w-0 text-xs leading-none">
                  <div className="truncate font-bold text-white">ادمین اصلی</div>
                  <div className="truncate text-[11px] text-zinc-500">super_admin</div>
                </div>
              </div>

              <a href="/admin/logout.php" className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-300 backdrop-blur hover:bg-red-500/15 hover:border-red-500/30 transition-colors">
                <LogOut className="h-4 w-4" />
              </a>
            </div>
          </header>

          {/* Page content — overflow-safe */}
          <main className="w-full min-w-0 flex-1 overflow-x-hidden p-3 sm:p-4 lg:p-6">
            <AnimatePresence mode="wait">
              <motion.div
                key={location.pathname}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -8 }}
                transition={{ duration: 0.24, ease: [0.32, 0.72, 0, 1] }}
                className="w-full min-w-0 max-w-full overflow-x-hidden"
              >
                <Outlet />
              </motion.div>
            </AnimatePresence>
          </main>
        </div>
      </div>
    </div>
  )
}
