import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import '../css/app.css';

const pages = import.meta.glob('./Pages/*.vue', { eager: true });

createInertiaApp({
    title: (title) => (title ? `${title} · Log Cleaner` : 'Log Cleaner'),
    resolve: (name) => pages[`./Pages/${name}.vue`],
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
    progress: { color: '#A3261C', showSpinner: false },
});
