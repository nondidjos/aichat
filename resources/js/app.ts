import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Intercept all Inertia requests to prepend subdirectory prefix
        // This catches wayfinder auto-generated routes (e.g. /login, /settings/profile)
        // that don't include the /aichat prefix. Manually-prefixed paths won't be affected
        // because we check startsWith before prepending.
        const baseUrl = (props.initialPage.props as Record<string, any>).baseUrl;
        if (baseUrl) {
            try {
                const prefix = new URL(baseUrl).pathname.replace(/\/$/, '');
                if (prefix && prefix !== '/') {
                    router.on('before', (event) => {
                        const url = event.detail.visit.url;
                        if (!url.pathname.startsWith(prefix)) {
                            url.pathname = prefix + url.pathname;
                        }
                    });
                }
            } catch { /* ignore */ }
        }

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

