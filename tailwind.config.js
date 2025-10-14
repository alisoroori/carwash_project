/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./backend/**/*.php",
    "./frontend/**/*.{html,js}",
    "./src/**/*.{html,js,php}",
    "./*.php",
    "./*.html"
  ],
  theme: {
    extend: {
      colors: {
        // Custom color palette for CarWash project
        'carwash-blue': {
          50: '#f0f5ff',
          100: '#e0eaff',
          200: '#c7d8ff',
          300: '#a5bbff',
          400: '#8094ff',
          500: '#6366f1',
          600: '#4f46e5',
          700: '#4338ca',
          800: '#3730a3',
          900: '#312e81',
        },
        'carwash-green': {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
        },
        'carwash-primary': '#2563eb',
        'carwash-secondary': '#7c3aed',
        'carwash-accent': '#f59e0b',
      },
      fontFamily: {
        'car-wash': ['Inter', 'Arial', 'sans-serif'],
      },
      animation: {
        'fade-in-up': 'fadeInUp 0.6s ease-out',
        'slide-in': 'slideIn 0.8s ease-out',
        'bounce-slow': 'bounce 2s infinite',
        'pulse-slow': 'pulse 3s infinite',
      },
      keyframes: {
        fadeInUp: {
          '0%': {
            opacity: '0',
            transform: 'translateY(30px)'
          },
          '100%': {
            opacity: '1',
            transform: 'translateY(0)'
          }
        },
        slideIn: {
          '0%': {
            opacity: '0',
            transform: 'translateX(30px)'
          },
          '100%': {
            opacity: '1',
            transform: 'translateX(0)'
          }
        }
      },
      spacing: {
        '128': '32rem',
        '144': '36rem',
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
      },
      boxShadow: {
        'carwash': '0 10px 25px -5px rgba(37, 99, 235, 0.1), 0 10px 10px -5px rgba(37, 99, 235, 0.04)',
        'carwash-lg': '0 25px 50px -12px rgba(37, 99, 235, 0.25)',
      }
    },
  },
  plugins: [],
}
