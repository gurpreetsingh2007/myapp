import './assets/main.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { routes } from 'vue-router/auto-routes';
const app = createApp(App);
const router = createRouter({
    history: createWebHistory(),
    routes,
});
app.use(createPinia());
app.use(router);
app.mount('#app');
