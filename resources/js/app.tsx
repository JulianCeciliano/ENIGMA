import { createInertiaApp, http } from '@inertiajs/react';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

http.onRequest((config) => {
    const xsrfCookie = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    if (xsrfCookie) {
        config.headers ??= {};
        config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie);
    }

    return config;
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});
