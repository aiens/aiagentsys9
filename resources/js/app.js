import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m.js';
import { createPinia } from 'pinia';

// Global components
import AppLayout from '@/Layouts/AppLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

// Plugins
import ToastPlugin from '@/Plugins/toast.js';
import ModalPlugin from '@/Plugins/modal.js';
import LoadingPlugin from '@/Plugins/loading.js';

const appName = import.meta.env.VITE_APP_NAME || 'AI Agent System';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        );
        
        // Auto-assign layout based on page location
        page.then((module) => {
            if (!module.default.layout) {
                if (name.startsWith('Auth/') || name.startsWith('Guest/')) {
                    module.default.layout = GuestLayout;
                } else {
                    module.default.layout = AppLayout;
                }
            }
        });
        
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Create Pinia store
        const pinia = createPinia();
        
        app.use(plugin)
           .use(pinia)
           .use(ZiggyVue)
           .use(ToastPlugin)
           .use(ModalPlugin)
           .use(LoadingPlugin);

        // Global properties
        app.config.globalProperties.$appName = appName;
        
        // Global error handler
        app.config.errorHandler = (error, instance, info) => {
            console.error('Vue Error:', error);
            console.error('Component:', instance);
            console.error('Info:', info);
            
            // Send error to monitoring service in production
            if (import.meta.env.PROD) {
                // TODO: Send to error monitoring service
            }
        };

        return app.mount(el);
    },
    progress: {
        color: '#3B82F6',
        showSpinner: true,
    },
});
