import '@vitejs/plugin-react/preamble';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = 'RACODE';
const pages = import.meta.glob('./pages/**/*.tsx');

createInertiaApp({
    title: (title) => (!title || title.includes('RACODE') || title.includes('راكود') ? (title || 'RACODE') : `${title} — ${appName}`),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, pages) as Promise<ResolvedComponent>,
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#F08A2A',
    },
});
