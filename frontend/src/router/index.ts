import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/tabs/dashboard'
      },
      {
        path: 'dashboard',
        component: () => import('../views/DashboardPage.vue')
      },
      {
        path: 'finca',
        component: () => import('../views/FincaPage.vue')
      },
      {
        path: 'animales',
        component: () => import('../views/AnimalesPage.vue')
      },
      {
        path: 'pesajes',
        component: () => import('../views/PesajesPage.vue')
      },
      {
        path: 'perfil',
        component: () => import('../views/PerfilPage.vue')
      },
      {
        path: 'reportes',
        component: () => import('../views/ReportesPage.vue')
      },
      {
        path: 'ayuda',
        component: () => import('../views/AyudaPage.vue')
      },
      {
        path: 'recordatorios',
        component: () => import('../views/RecordatoriosPage.vue')
      },
      {
        path: 'pesaje-vivo',
        component: () => import('../views/PesajeVivoPage.vue')
      }
    ]
  },
  {
    path: '/login',
    component: () => import('../views/LoginPage.vue')
  },
  {
    path: '/registro',
    component: () => import('../views/RegistroPage.vue')
  },

  // ADMINISTRADOR
  {
    path: '/admin/usuarios',
    component: () => import('../views/admin/AdminUsuariosPage.vue'),
    meta: {
      requiresAdmin: true
    }
  },
  {
    path: '/admin/bitacoras',
    component: () => import('../views/admin/AdminBitacoraPage.vue'),
    meta: {
      requiresAdmin: true
    }
  },

  // VETERINARIO
  {
    path: '/veterinario',
    component: () => import('../views/veterinario/VeterinarioHomePage.vue'),
    meta: {
      requiresVeterinario: true
    }
  },
  {
    path: '/veterinario/animales/:id',
    component: () => import('../views/veterinario/VeterinarioAnimalDetallePage.vue'),
    meta: {
      requiresVeterinario: true
    }
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach((to, from, next) => {
  const userRaw = localStorage.getItem('user');

  const rutasPublicas = [
    '/login',
    '/registro'
  ];

  let user: any = null;

  if (userRaw) {
    try {
      user = JSON.parse(userRaw);
    } catch {
      localStorage.removeItem('user');
      localStorage.removeItem('token');
    }
  }

  if (!user && !rutasPublicas.includes(to.path)) {
    next('/login');
    return;
  }

  if (
    user &&
    (
      to.path === '/login' ||
      to.path === '/registro'
    )
  ) {
    if (user?.rol === 'admin') {
      next('/admin/usuarios');
      return;
    }

    if (user?.rol === 'veterinario') {
      next('/veterinario');
      return;
    }

    next('/tabs/dashboard');
    return;
  }

  if (
    to.meta.requiresAdmin &&
    user?.rol !== 'admin'
  ) {
    next('/tabs/dashboard');
    return;
  }

  if (
    to.meta.requiresVeterinario &&
    user?.rol !== 'veterinario'
  ) {
    next('/login');
    return;
  }

  if (
    user?.rol === 'admin' &&
    to.path.startsWith('/tabs')
  ) {
    next('/admin/usuarios');
    return;
  }

  if (
    user?.rol === 'veterinario' &&
    to.path.startsWith('/tabs')
  ) {
    next('/veterinario');
    return;
  }

  next();
});

export default router;