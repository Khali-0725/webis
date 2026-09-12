/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        // Palette lifted from the approved WEBIS mockups.
        navy: {
          50: '#EEF2FB',
          100: '#D6DFF3',
          200: '#AEBFE7',
          300: '#7F97D6',
          400: '#4E6CBE',
          500: '#2C4A9E',
          600: '#1B3A7A',
          700: '#12295C',
          800: '#0F2557',
          900: '#0B1E45',
          950: '#07152F',
        },
        brand: {
          DEFAULT: '#F5811F',
          50: '#FEF3E8',
          100: '#FDE3C9',
          200: '#FBC792',
          300: '#F9A95A',
          400: '#F7933A',
          500: '#F5811F',
          600: '#DC6D0C',
          700: '#B0570A',
          800: '#834107',
          900: '#572B05',
        },
        canvas: '#F1F3F9',
        line: '#E3E7F0',
        ink: {
          DEFAULT: '#1B2437',
          muted: '#6B7793',
          soft: '#98A2B7',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
        display: ['Poppins', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        card: '12px',
      },
      boxShadow: {
        card: '0 1px 3px rgba(16, 24, 40, 0.08)',
        'card-hover': '0 6px 18px rgba(16, 24, 40, 0.10)',
        sidebar: '2px 0 12px rgba(11, 30, 69, 0.06)',
      },
      keyframes: {
        'fade-in': {
          from: { opacity: '0', transform: 'translateY(4px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
        shimmer: {
          '100%': { transform: 'translateX(100%)' },
        },
      },
      animation: {
        'fade-in': 'fade-in 160ms ease-out',
      },
    },
  },
  plugins: [],
};
