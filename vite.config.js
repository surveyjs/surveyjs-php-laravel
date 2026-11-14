import { readdirSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Each step's client code is a plain ES module in shared/client/, the same file on every platform.
// They are entry points as they are: the step page loads the file the manifest lists.
const stepModules = readdirSync('shared/client')
    .filter((file) => file.endsWith('.js'))
    .map((file) => `shared/client/${file}`);

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                // The page UI every platform shares: the tabs and the side panel, and the Code panel's highlighting
                'shared/ui/page-tabs.js',
                'shared/ui/code-highlight.js',
                'node_modules/highlight.js/styles/github-dark.css',
                // SurveyJS themes: the form, Creator and Dashboard pages each load one
                'resources/css/survey.css',
                'resources/css/creator.css',
                'resources/css/dashboard.css',
                ...stepModules,
            ],
            refresh: true,
            // Lato 2.0, Latin, the four weights surveyjs.io serves (Google and Bunny have no 500 or 600).
            // SIL Open Font License: shared/ui/fonts/lato/OFL.txt. Other platforms load shared/ui/fonts.css instead.
            fonts: [
                local('Lato', {
                    variants: [
                        { src: 'shared/ui/fonts/lato/LatoLatin-Regular.woff2', weight: 400 },
                        { src: 'shared/ui/fonts/lato/LatoLatin-Medium.woff2', weight: 500 },
                        { src: 'shared/ui/fonts/lato/LatoLatin-Semibold.woff2', weight: 600 },
                        { src: 'shared/ui/fonts/lato/LatoLatin-Bold.woff2', weight: 700 },
                    ],
                    optimizedFallbacks: false,  // needs the optional fontaine package; the pages never had metric fallbacks
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        chunkSizeWarningLimit: 5000,    // Survey Creator and the Dashboard are large; each page loads only its own module
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
