import type { Config } from 'tailwindcss'

const config: Config = {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Sora', 'ui-sans-serif', 'system-ui'],
      },
      colors: {
        primary: '#2563eb',
        'primary-hover': '#1d4ed8',
        muted: '#6b7280',
      },
      borderRadius: {
        DEFAULT: '12px',
      },
    },
  },
  plugins: [],
}

export default config
