import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
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
            }
        },
    },

    plugins: [forms],
};
