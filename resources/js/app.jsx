import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { PopupProvider } from './contexts/PopupContext';
import { AuthProvider } from './contexts/AuthContext';

const appName = import.meta.env.VITE_APP_NAME || 'The Cosmic';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <AuthProvider>
                <PopupProvider>
                    <App {...props} />
                </PopupProvider>
            </AuthProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
