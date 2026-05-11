import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue';

const routes: Array<RouteRecordRaw> = [
  { path: '/', redirect: '/login' },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      { path: '', redirect: '/tabs/dashboard' },
      { path: 'dashboard', component: () => import('../views/DashboardPage.vue') },
      { path: 'finca', component: () => import('../views/FincaPage.vue') },
      { path: 'animales', component: () => import('../views/AnimalesPage.vue') },
      { path: 'pesajes', component: () => import('../views/PesajesPage.vue') },
      { path: 'perfil', component: () => import('../views/PerfilPage.vue') },
      { path: 'reportes', component: () => import('../views/ReportesPage.vue') },
      { path: 'ayuda', component: () => import('../views/AyudaPage.vue') }
    ]
  },
  { path: '/login', component: () => import('../views/LoginPage.vue') }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach((to, from, next) => {
  const user = localStorage.getItem('user');
  if (!user && to.path !== '/login') {
    next('/login');
  } else if (user && to.path === '/login') {
    next('/tabs/dashboard');
  } else {
    next();
  }
});

export default router; 