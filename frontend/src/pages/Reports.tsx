import { Card, CardHeader, Badge } from '../components/ui'
import { BarChart3, TrendingUp, Wallet } from 'lucide-react'
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'

const data = [{m:'فروردین',v:42000000},{m:'اردیبهشت',v:58000000},{m:'خرداد',v:61000000},{m:'تیر',v:72000000},{m:'مرداد',v:54000000},{m:'شهریور',v:83000000}]

export default function Reports(){
  return (
    <div className="space-y-5">
      <h1 className="text-xl font-black text-white">گزارش مالی</h1>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {[{k:'درآمد امروز',v:'12,400,000 ت'},{k:'درآمد ماه',v:'342,000,000 ت'},{k:'میانگین سبد',v:'92,400 ت'}].map(s=>(
          <Card key={s.k} className="p-5"><div className="text-xs font-bold text-zinc-500">{s.k}</div><div className="mt-1 text-lg font-black text-white">{s.v}</div></Card>
        ))}
      </div>
      <Card>
        <CardHeader><div className="font-black text-white flex items-center gap-2"><BarChart3 className="h-4 w-4 text-violet-400"/> درآمد ماهانه</div><Badge tone="emerald" className="gap-1"><TrendingUp className="h-3 w-3"/> +18%</Badge></CardHeader>
        <div className="h-[300px] p-4"><ResponsiveContainer width="100%" height="100%"><BarChart data={data}><XAxis dataKey="m" tick={{fill:'#71717a',fontSize:11}} axisLine={false} tickLine={false}/><YAxis tick={{fill:'#71717a',fontSize:11}} axisLine={false} tickLine={false} tickFormatter={v=>`${v/1000000}M`}/><Tooltip contentStyle={{background:'rgba(24,24,27,0.9)',border:'1px solid rgba(255,255,255,0.08)',borderRadius:16,color:'#fff'}}/><Bar dataKey="v" fill="#8b5cf6" radius={[10,10,0,0]} /></BarChart></ResponsiveContainer></div>
      </Card>
    </div>
  )
}
