import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatNumber(n: number | string) {
  const num = typeof n === 'string' ? parseFloat(n) : n
  if (isNaN(num)) return '0'
  return num.toLocaleString('fa-IR')
}

export function formatPrice(n: number) {
  return n.toLocaleString('fa-IR') + ' تومان'
}
