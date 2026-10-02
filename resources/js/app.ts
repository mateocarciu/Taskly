import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from './composables/useAppearance';

import '@fullcalendar/vue3/skeleton.css';
import '@fullcalendar/vue3/themes/forma/palettes/purple.css';
import '@fullcalendar/vue3/themes/forma/theme.css';

const appName = import.meta.env.VITE_APP_NAME || 'Taskly';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
