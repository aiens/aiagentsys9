import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter var', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                    950: '#172554',
                },
                secondary: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                    950: '#020617',
                },
                success: {
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
                    950: '#052e16',
                },
                warning: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
                danger: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d',
                    950: '#450a0a',
                },
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem',
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-in-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'bounce-slow': 'bounce 2s infinite',
                'typing': 'typing 1s steps(20, end)',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                slideDown: {
                    '0%': { transform: 'translateY(-10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                typing: {
                    '0%': { width: '0' },
                    '100%': { width: '100%' },
                },
            },
            boxShadow: {
                'soft': '0 2px 15px 0 rgba(0, 0, 0, 0.1)',
                'medium': '0 4px 25px 0 rgba(0, 0, 0, 0.15)',
                'hard': '0 10px 40px 0 rgba(0, 0, 0, 0.2)',
            },
            backdropBlur: {
                xs: '2px',
            },
            typography: {
                DEFAULT: {
                    css: {
                        maxWidth: 'none',
                        color: '#374151',
                        '[class~="lead"]': {
                            color: '#4b5563',
                        },
                        a: {
                            color: '#3b82f6',
                            textDecoration: 'none',
                            fontWeight: '500',
                            '&:hover': {
                                color: '#1d4ed8',
                                textDecoration: 'underline',
                            },
                        },
                        strong: {
                            color: '#111827',
                            fontWeight: '600',
                        },
                        'ol[type="A"]': {
                            '--list-counter-style': 'upper-alpha',
                        },
                        'ol[type="a"]': {
                            '--list-counter-style': 'lower-alpha',
                        },
                        'ol[type="A" s]': {
                            '--list-counter-style': 'upper-alpha',
                        },
                        'ol[type="a" s]': {
                            '--list-counter-style': 'lower-alpha',
                        },
                        'ol[type="I"]': {
                            '--list-counter-style': 'upper-roman',
                        },
                        'ol[type="i"]': {
                            '--list-counter-style': 'lower-roman',
                        },
                        'ol[type="I" s]': {
                            '--list-counter-style': 'upper-roman',
                        },
                        'ol[type="i" s]': {
                            '--list-counter-style': 'lower-roman',
                        },
                        'ol[type="1"]': {
                            '--list-counter-style': 'decimal',
                        },
                        'ul > li::marker': {
                            color: '#6b7280',
                        },
                        'ol > li::marker': {
                            color: '#6b7280',
                        },
                        hr: {
                            borderColor: '#e5e7eb',
                            marginTop: '3em',
                            marginBottom: '3em',
                        },
                        blockquote: {
                            fontWeight: '500',
                            fontStyle: 'italic',
                            color: '#374151',
                            borderLeftWidth: '0.25rem',
                            borderLeftColor: '#e5e7eb',
                            quotes: '"\\201C""\\201D""\\2018""\\2019"',
                            marginTop: '1.6em',
                            marginBottom: '1.6em',
                            paddingLeft: '1em',
                        },
                        h1: {
                            color: '#111827',
                            fontWeight: '800',
                            fontSize: '2.25em',
                            marginTop: '0',
                            marginBottom: '0.8888889em',
                            lineHeight: '1.1111111',
                        },
                        h2: {
                            color: '#111827',
                            fontWeight: '700',
                            fontSize: '1.5em',
                            marginTop: '2em',
                            marginBottom: '1em',
                            lineHeight: '1.3333333',
                        },
                        h3: {
                            color: '#111827',
                            fontWeight: '600',
                            fontSize: '1.25em',
                            marginTop: '1.6em',
                            marginBottom: '0.6em',
                            lineHeight: '1.6',
                        },
                        h4: {
                            color: '#111827',
                            fontWeight: '600',
                            marginTop: '1.5em',
                            marginBottom: '0.5em',
                            lineHeight: '1.5',
                        },
                        code: {
                            color: '#111827',
                            fontWeight: '600',
                            fontSize: '0.875em',
                        },
                        'code::before': {
                            content: '"`"',
                        },
                        'code::after': {
                            content: '"`"',
                        },
                        pre: {
                            color: '#e5e7eb',
                            backgroundColor: '#1f2937',
                            overflowX: 'auto',
                            fontWeight: '400',
                            fontSize: '0.875em',
                            lineHeight: '1.7142857',
                            marginTop: '1.7142857em',
                            marginBottom: '1.7142857em',
                            borderRadius: '0.375rem',
                            paddingTop: '0.8571429em',
                            paddingRight: '1.1428571em',
                            paddingBottom: '0.8571429em',
                            paddingLeft: '1.1428571em',
                        },
                        'pre code': {
                            backgroundColor: 'transparent',
                            borderWidth: '0',
                            borderRadius: '0',
                            padding: '0',
                            fontWeight: 'inherit',
                            color: 'inherit',
                            fontSize: 'inherit',
                            fontFamily: 'inherit',
                            lineHeight: 'inherit',
                        },
                        'pre code::before': {
                            content: 'none',
                        },
                        'pre code::after': {
                            content: 'none',
                        },
                    },
                },
            },
        },
    },

    plugins: [
        forms,
        typography,
    ],
};
