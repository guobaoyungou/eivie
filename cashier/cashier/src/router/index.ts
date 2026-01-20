import { createRouter, createWebHashHistory } from 'vue-router'

const router = createRouter({
  history: createWebHashHistory(), // hash模式：createWebHashHistory，history模式：createWebHistory
  routes: [
    {
      path: '/',
      redirect: '/index/index'
    },
    {
      path: '/home',
      name: 'home',
      component: () => import(/* webpackChunkName: "home" */ '../views/home.vue'),
      children: [
        {
          path: "/index/index",
          component: () => import(/* webpackChunkName: "home" */ '../views/index/index.vue'),
          name: "index"
        },
        {
          path: "/order/index",
          component: () => import(/* webpackChunkName: "home" */ '../views/order/index.vue'),
          name: "order"
        }
      ]
    },
  ]
})

export default router