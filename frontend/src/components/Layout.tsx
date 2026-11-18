import { useState, useEffect } from 'react'
import { Outlet, NavLink, useLocation } from 'react-router-dom'
import { motion, AnimatePresence } from 'framer-motion'
import {
  LayoutDashboard, Bot, Settings, Megaphone, HardDrive,
  ShieldCheck, Layers, Package, Ticket, Users, Receipt,
  Archive, CreditCard, BarChart3, Database, Gift, Menu, X,
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

  // auto expand section of current route
  useEffect(() => {
    navSections.forEach(s => {
      if (s.items.some(i => location.pathname.startsWith(i.to))) {
        setExpanded(prev => ({ ...prev, [s.title]: true }))
      }
    })
  }, [location.pathname])

  return (
    <>
      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
            onClick={onClose}
          />
        )}
      </AnimatePresence>

      <motion.aside
        initial={false}
        animate={{ x: 0 }}
        className={`
          fixed inset-y-0 right-0 z-50 flex w-[300px] flex-col
          border-l border-white/[0.06] bg-[#08080c] 
          lg:sticky lg:top-0 lg:h-screen lg:shrink-0
          ${open ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'}
          transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]
          shadow-[8px_0_40px_rgba(0,0,0,0.5)]
        `}
      >
        {/* Brand */}
        <div className="flex items-center gap-3 border-b border-white/[0.06] px-5 py-5">
          <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-[0_0_20px_rgba(124,58,237,0.45)]">
            <ShieldCheck className="h-6 w-6 text-white" />
          </div>
          <div className="min-w-0">
            <div className="text-[15px] font-black leading-none text-white tracking-tight">AVA PANEL</div>
            <div className="mt-1 flex items-center gap-1.5 text-[11px] font-medium text-zinc-500">
              <span className="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981] animate-pulse" />
              پنل هوشمند فروش VPN
            </div>
          </div>
          <button onClick={onClose} className="mr-auto flex h-8 w-8 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-zinc-400 lg:hidden">
            <X className="h-4 w-4" />
          </button>
        </div>

        <nav className="flex-1 overflow-y-auto px-3 py-3 [scrollbar-width:thin]">
          <NavLink
            to="/"
            onClick={() => window.innerWidth < 1024 && onClose()}
            className={({ isActive }) =>
              `mb-2 flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-bold transition-all ${isActive
                ? 'bg-gradient-to-l from-violet-600 to-indigo-600 text-white shadow-[0_8px_20px_rgba(124,58,237,0.35)]'
                : 'text-zinc-400 hover:bg-white/[0.06] hover:text-white'}`
            }
          >
            <LayoutDashboard className="h-[18px] w-[18px]" />
            داشبورد
            <Sparkles className="mr-auto h-4 w-4 opacity-60" />
          </NavLink>

          {navSections.map(section => {
            const isOpen = !!expanded[section.title]
            return (
              <div key={section.title} className="mb-1">
                <button
                  onClick={() => setExpanded(p => ({ ...p, [section.title]: !p[section.title] }))}
                  className={`flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-[11px] font-black tracking-widest transition-colors ${isOpen ? 'text-violet-300 bg-white/[0.04]' : 'text-zinc-500 hover:text-zinc-300'}`}
                >
                  <span className="flex items-center gap-2">
                    <section.icon className="h-3.5 w-3.5" />
                    {section.title}
                  </span>
                  <ChevronDown className={`h-3 w-3 transition-transform duration-300 ${isOpen ? 'rotate-180' : ''}`} />
                </button>

                <AnimatePresence initial={false}>
                  {isOpen && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: 'auto', opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{ duration: 0.22, ease: [0.32, 0.72, 0, 1] }}
                      className="overflow-hidden"
                    >
                      <div className="space-y-0.5 pb-2 pt-1">
                        {section.items.map(item => (
                          <NavLink
                            key={item.to}
                            to={item.to}
                            onClick={() => window.innerWidth < 1024 && onClose()}
                            className={({ isActive }) =>
                              `flex items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-[13px] font-medium transition-all ${isActive
                                ? 'bg-white/[0.08] text-white border border-white/[0.06] shadow-sm'
                                : 'text-zinc-500 hover:bg-white/[0.05] hover:text-zinc-200 border border-transparent'}`
                            }
                          >
                            <item.icon className="h-4 w-4 shrink-0 opacity-80" />
                            {item.label}
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

        {/* footer */}
        <div className="border-t border-white/[0.06] bg-black/20 px-4 py-4">
          <div className="rounded-2xl border border-violet-500/15 bg-gradient-to-br from-violet-500/[0.08] to-indigo-500/[0.06] p-3.5">
            <div className="flex items-center gap-2 text-xs font-bold text-violet-200">
              <Zap className="h-3.5 w-3.5" />
              نسخه 1.0.0 — PHP 8.2+
            </div>
            <div className="mt-1 text-[11px] text-zinc-500">Dark · Neon · Glass · 60fps</div>
          </div>

          {/* Creator card mini */}
          <div className="mt-3 overflow-hidden rounded-2xl border border-indigo-500/20 bg-[#0f172a]/80 p-3 backdrop-blur">
            <div className="flex items-center gap-2.5">
              <img src="https://avatars.githubusercontent.com/u/1?v=4" alt="" className="h-8 w-8 rounded-xl object-cover opacity-60" />
              <div>
                <div className="text-xs font-bold text-white">Crafted with ♥</div>
                <div className="text-[10px] text-zinc-500">AVA · Luxury Dark</div>
              </div>
              <div className="mr-auto h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]" />
            </div>
          </div>
        </div>
      </motion.aside>
    </>
  )
}

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const location = useLocation()

  return (
    <div className="min-h-screen bg-[#050507] text-zinc-100">
      <div className="flex">
        <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

        <div className="min-w-0 flex-1">
          {/* Topbar */}
          <header className="sticky top-0 z-30 flex h-[64px] items-center justify-between gap-3 border-b border-white/[0.06] bg-[#0a0a0f]/70 px-4 backdrop-blur-xl lg:px-6">
            <div className="flex items-center gap-3">
              <button
                onClick={() => setSidebarOpen(true)}
                className="flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/[0.06] text-white lg:hidden"
              >
                <Menu className="h-5 w-5" />
              </button>

              <div className="hidden items-center gap-2 lg:flex">
                <div className="h-6 w-px bg-white/10" />
                <div className="text-sm font-black tracking-tight text-white">پنل مدیریت</div>
                <span className="rounded-full border border-violet-500/20 bg-violet-500/10 px-2.5 py-1 text-[11px] font-bold text-violet-300">Dark Luxe</span>
              </div>

              {/* search mock */}
              <div className="hidden items-center gap-2 rounded-full border border-white/[0.06] bg-white/[0.04] px-3 py-2 text-sm text-zinc-500 md:flex">
                <Search className="h-4 w-4" />
                <input placeholder="جستجو سفارش، کاربر..." className="w-48 bg-transparent text-sm text-white placeholder:text-zinc-500 focus:outline-none" />
                <span className="rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-bold text-zinc-400">⌘K</span>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <button className="relative flex h-9 w-9 items-center justify-center rounded-xl border border-white/[0.06] bg-white/[0.04] text-zinc-400 hover:text-white hover:bg-white/[0.08] transition-colors">
                <Bell className="h-4.5 w-4.5" />
                <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-gradient-to-br from-violet-600 to-indigo-600 text-[10px] font-black text-white shadow">3</span>
              </button>

              <div className="hidden items-center gap-3 rounded-full border border-white/[0.06] bg-white/[0.04] py-1 pl-1 pr-3 md:flex">
                <img src={`https://i.pravatar.cc/100?img=15`} className="h-7 w-7 rounded-full" alt="" />
                <div className="text-xs leading-none">
                  <div className="font-bold text-white">ادمین اصلی</div>
                  <div className="text-[11px] text-zinc-500">super_admin</div>
                </div>
              </div>

              <a href="/admin/logout.php" className="flex h-9 w-9 items-center justify-center rounded-xl border border-red-500/15 bg-red-500/10 text-red-300 hover:bg-red-500/15 transition-colors">
                <LogOut className="h-4 w-4" />
              </a>
            </div>
          </header>

          {/* Page content */}
          <main className="p-4 lg:p-6">
            <AnimatePresence mode="wait">
              <motion.div
                key={location.pathname}
                initial={{ opacity: 0, y: 8 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -6 }}
                transition={{ duration: 0.22, ease: [0.32, 0.72, 0, 1] }}
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
