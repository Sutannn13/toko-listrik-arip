import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                body: ['Inter', ...defaultTheme.fontFamily.sans],
                sans: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Storefront brand (green — kept for backwards compat)
                primary: {
                    50: '#f2fdf4',
                    100: '#dcfce4',
                    200: '#baf7c8',
                    300: '#84ee98',
                    400: '#43de67',
                    500: '#03ac0e',
                    600: '#028f0c',
                    700: '#02730b',
                    800: '#055c0f',
                    900: '#064b10',
                    950: '#022909',
                },
                // TailAdmin brand (blue-indigo)
                brand: {
                    25:  '#F5F8FF',
                    50:  '#EFF4FF',
                    100: '#D1E0FF',
                    200: '#B2CCFF',
                    300: '#84ADFF',
                    400: '#528BFF',
                    500: '#3B82F6',
                    600: '#2563EB',
                    700: '#1D4ED8',
                    800: '#1E40AF',
                    900: '#1E3A8A',
                    950: '#172554',
                },
                // TailAdmin gray scale
                gray: {
                    50:  '#F9FAFB',
                    100: '#F3F4F6',
                    200: '#E5E7EB',
                    300: '#D1D5DB',
                    400: '#9CA3AF',
                    500: '#6B7280',
                    600: '#4B5563',
                    700: '#374151',
                    800: '#1F2937',
                    900: '#111928',
                    950: '#030712',
                },
                // TailAdmin semantic colors
                success: {
                    50:  '#F0FDF4',
                    100: '#DCFCE7',
                    200: '#BBF7D0',
                    300: '#86EFAC',
                    400: '#4ADE80',
                    500: '#22C55E',
                    600: '#16A34A',
                    700: '#15803D',
                },
                warning: {
                    50:  '#FFFBEB',
                    100: '#FEF3C7',
                    200: '#FDE68A',
                    300: '#FCD34D',
                    400: '#FBBF24',
                    500: '#F59E0B',
                    600: '#D97706',
                    700: '#B45309',
                },
                error: {
                    50:  '#FEF2F2',
                    100: '#FEE2E2',
                    200: '#FECACA',
                    300: '#FCA5A5',
                    400: '#F87171',
                    500: '#EF4444',
                    600: '#DC2626',
                    700: '#B91C1C',
                },
                // Dark mode surface colors (TailAdmin)
                dark: {
                    bg:      '#1A2332',
                    sidebar: '#1C2A3A',
                    card:    '#1F2A3C',
                    border:  '#2D3A4A',
                    hover:   '#253243',
                    input:   '#2D3A4A',
                },
            },
            boxShadow: {
                'tailadmin':   '0px 1px 3px rgba(0, 0, 0, 0.08)',
                'tailadmin-md': '0px 4px 6px -1px rgba(0, 0, 0, 0.06)',
                'tailadmin-lg': '0px 10px 15px -3px rgba(0, 0, 0, 0.08)',
                'sidebar':     '4px 0 20px rgba(0, 0, 0, 0.08)',
            },
            zIndex: {
                'sidebar':    40,
                'header':     30,
                'overlay':    50,
                'dropdown':   60,
                'modal':      70,
                'toast':      80,
            },
        },
    },

    plugins: [forms],
};
