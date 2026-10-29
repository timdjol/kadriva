import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { compileScript, parse } from '@vue/compiler-sfc';

function vueSfc() {
    return {
        name: 'local-vue-sfc',
        transform(source, id) {
            if (!id.endsWith('.vue')) return null;
            const { descriptor, errors } = parse(source, { filename: id });
            if (errors.length) throw errors[0];
            const compiled = compileScript(descriptor, {
                id,
                inlineTemplate: true,
            });
            return { code: compiled.content, map: null };
        },
    };
}

export default defineConfig({
    plugins: [
        vueSfc(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
