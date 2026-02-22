import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Eagerly load ALL wayfinder route modules so we can patch them
const routeModules = import.meta.glob('./routes/**/index.ts', { eager: true });

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Patch ALL wayfinder route definitions for subdirectory deployment.
        // This modifies definition.url on every exported route object so that
        // .url() and .form() return paths prefixed with /aichat.
        const baseUrl = (props.initialPage.props as Record<string, any>).baseUrl;
        if (baseUrl) {
            try {
                const prefix = new URL(baseUrl).pathname.replace(/\/$/, '');
                if (prefix && prefix !== '/') {
                    for (const [, mod] of Object.entries(routeModules)) {
                        for (const [, exp] of Object.entries(mod as Record<string, any>)) {
                            if (
                                exp?.definition?.url &&
                                typeof exp.definition.url === 'string' &&
                                !exp.definition.url.startsWith(prefix)
                            ) {
                                exp.definition.url = prefix + exp.definition.url;
                            }
                        }
                    }
                }
            } catch {
                /* ignore — runs fine without prefix when not in subdirectory */
            }
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
