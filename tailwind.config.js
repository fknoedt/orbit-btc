import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/views/landing/**/*.blade.php',
        './resources/views/landing/*.blade.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
        './resources/css/**/*.css',
        './public/assets/**/*.css',
    ],
    theme: {
        extend: {
            colors: {
                'orange': {
                    400: '#ff611e', // Lighter gradient start
                    600: '#f54900', // Match demo's stronger orange
                    700: '#d63d00', // Darker hover state
                },
            },
            fontFamily: {
                'rubik': ['Rubik', 'sans-serif'],
            },
            screens: {
                'xs': '540px',
                'sm': '640px',
                'md': '720px',
                'lg': '960px',
                'lg_992': '992px',
                'xl': '1140px',
                '2xl': '1140px',
            },
            zIndex: {
                '1': '1',
                '2': '2',
                '999': '999',
            },
        },
    },
    important: true, // Forces custom styles to override preset
    plugins: [],
};
