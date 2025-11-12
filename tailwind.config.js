import colors from 'tailwindcss/colors'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

export default {
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/css/filament/**/*.css',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: [
        'fi-sidebar',
        'fi-sidebar-group',
        'fi-sidebar-item-icon',
        'fi-sidebar-item',
        'fi-sidebar-item-label',
        'fi-sidebar-item-active',
        'fi-main-ctn',
        'fi-header',
        'fi-button',
        'text-white',
    ],
    theme: {
        extend: {
            colors: {
                primary: colors.blue,
                gray: colors.gray,
                danger: colors.red,
                warning: colors.amber,
                success: colors.green,
                info: colors.blue,
                white: '#ffffff',
            },
        }
    },
    plugins: [
        forms,
        typography,
    ],
}
