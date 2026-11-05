import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { renderToString } from 'react-dom/server';

const pages = import.meta.glob('./pages/**/*.tsx');

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, pages) as Promise<ResolvedComponent>,
        setup: ({ App, props }) => <App {...props} />,
    }),
);
