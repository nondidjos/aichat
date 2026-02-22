import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
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
        // Global fetch interceptor for subdirectory deployment
        // Rewrites same-origin URLs missing the /aichat prefix
        const baseUrl = (props.initialPage.props as Record<string, any>).baseUrl;
        if (baseUrl) {
            try {
                const prefix = new URL(baseUrl).pathname.replace(/\/$/, '');
                if (prefix && prefix !== '/') {
                    const origin = window.location.origin;
                    const originalFetch = window.fetch;
                    window.fetch = function (input: RequestInfo | URL, init?: RequestInit): Promise<Response> {
                        if (typeof input === 'string') {
                            try {
                                const url = new URL(input, origin);
                                if (url.origin === origin && !url.pathname.startsWith(prefix)) {
                                    url.pathname = prefix + url.pathname;
                                    return originalFetch.call(window, url.toString(), init);
                                }
                            } catch { /* not a URL, pass through */ }
                        } else if (input instanceof Request) {
                            try {
                                const url = new URL(input.url);
                                if (url.origin === origin && !url.pathname.startsWith(prefix)) {
                                    url.pathname = prefix + url.pathname;
                                    return originalFetch.call(window, new Request(url.toString(), input), init);
                                }
                            } catch { /* pass through */ }
                        } else if (input instanceof URL) {
                            if (input.origin === origin && !input.pathname.startsWith(prefix)) {
                                const newUrl = new URL(input.toString());
                                newUrl.pathname = prefix + newUrl.pathname;
                                return originalFetch.call(window, newUrl, init);
                            }
                        }
                        return originalFetch.call(window, input, init);
                    };
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
