import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

<<<<<<< HEAD
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pinia = createPinia();

=======
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

>>>>>>> 0915937 (first commit after re-install and daisy ui failure.)
createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
<<<<<<< HEAD
            case name.startsWith('guest/'):
                return null;
=======
>>>>>>> 0915937 (first commit after re-install and daisy ui failure.)
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
<<<<<<< HEAD
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        app.use(plugin);
        app.use(pinia);
        
        // Fix: Check if el exists before mounting
        if (el) {
            app.mount(el);
        }
        
        return app;
    },
=======
>>>>>>> 0915937 (first commit after re-install and daisy ui failure.)
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
