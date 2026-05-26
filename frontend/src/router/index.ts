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
      { path: 'ayuda', component: () => import('../views/AyudaPage.vue') },
      /*{path: "pesaje-vivo",component: () => import("../views/PesajesVivoPage.vue")}
         Falta esta vista                                */
    ]
  },
  { path: '/login', component: () => import('../views/LoginPage.vue') },
  {
  path: '/registro',
  component: () => import('../views/RegistroPage.vue')
}
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach((to, from, next) => {

  const user = localStorage.getItem('user');

  /*
    RUTAS PÚBLICAS
  */

  const rutasPublicas = [
    '/login',
    '/registro'
  ];

  /*
    SI NO HAY USUARIO
  */

  if (!user && !rutasPublicas.includes(to.path)) {

    next('/login');

  }

  /*
    SI YA ESTÁ LOGUEADO
  */

  else if (
    user &&
    (
      to.path === '/login' ||
      to.path === '/registro'
    )
  ) {

    next('/tabs/dashboard');

  }

  /*
    TODO OK
  */

  else {

    next();

  }

});

export default router; 