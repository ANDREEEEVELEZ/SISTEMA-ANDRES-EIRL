export default {
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/css/filament/dashboard/**/*.css',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: [
        'text-white',
        'bg-[#8a2e4a]',
        'bg-[#a53e5d]',
        'bg-[#9b2c4d]',
        'bg-[#7d243f]',
        'bg-[#f9f9f9]',
        'text-[#f7c3d1]',
        'text-[#9b2c4d]',
        'text-[#7d243f]',
    ],
    theme: {
        extend: {
            colors: {
                white: '#ffffff',
            },
        }
    }
}