import terser from '@rollup/plugin-terser';

const config = {
    input: 'assets/js/cookie-consent.mjs',
    output: {
        file: 'public/js/cookie-consent.min.js',
        format: 'esm',
        compact: true,
        plugins: [terser()]
    }
};

export default config;