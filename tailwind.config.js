/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
    ],
    theme: {
        extend: {
            colors: {
                'gc-yellow': '#e4ae22',
            },
        },
    },
    plugins: [],
}
