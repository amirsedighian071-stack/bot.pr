/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Vazirmatn', 'system-ui', 'sans-serif'],
      },
      colors: {
        void: '#090d16',
        deep: '#0b0f19',
        abyss: '#070a12',
        slate: {
          950: '#020617',
          900: '#0f172a',
          850: '#0b0f19',
          800: '#1e293b',
          750: '#151d2f',
          700: '#334155',
        },
        neon: {
          500: '#8b5cf6',
          600: '#7c3aed',
          400: '#a78bfa',
          300: '#c4b5fd',
          cyan: '#06b6d4',
          blue: '#3b82f6',
        }
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
        '4xl': '2rem',
      },
      boxShadow: {
        'soft': '0 8px 32px rgba(0,0,0,0.42)',
        'neon': '0 0 24px rgba(139,92,246,0.38), 0 0 48px rgba(139,92,246,0.14)',
        'neon-cyan': '0 0 20px rgba(6,182,212,0.35), 0 0 40px rgba(6,182,212,0.12)',
        'glow': '0 0 30px rgba(139,92,246,0.28)',
      },
      animation: {
        'float': 'float 6s ease-in-out infinite',
        'pulse-slow': 'pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'shimmer': 'shimmer 2s linear infinite',
      },
      keyframes: {
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-4px)' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-100% 0' },
          '100%': { backgroundPosition: '200% 0' },
        }
      },
      backdropBlur: {
        xs: '2px',
      }
    }
  },
  plugins: [],
}
