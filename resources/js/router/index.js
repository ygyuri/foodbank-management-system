import { createRouter, createWebHashHistory } from 'vue-router'

/* Layout */
import Layout from '@/layout/Layout.vue'

/* Router for modules */

import adminRoutes from './modules/admin'
import errorRoutes from './modules/error'

export const constantRoutes = [
  {
    path: '/login',
    component: () => import('@/views/login/index.vue'),
    hidden: false
  },
  {
    path: '/auth-redirect',
    component: () => import('@/views/login/AuthRedirect.vue'),
    hidden: false
  },
  {
    path: '/404',
    redirect: { name: 'Page404' },
    component: () => import('@/views/error-page/404.vue'),
    hidden: false
  },
  {
    path: '/401',
    component: () => import('@/views/error-page/401.vue'),
    hidden: false
  },
  {
    path: '/',
    component: Layout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        name: 'Dashboard',
        meta: { title: 'dashboard', bootstrapIcon: 'house-fill', noCache: false }
      }
    ]
  },
    // ✅ Add User Management Route Here
    {
      path: '/user-management',
      component: Layout,
      redirect: '/user-management/list',
      children: [
        {
          path: 'list',
          component: () => import('@/views/users/NewUserManagement.vue'),
          name: 'NewUserManagement',
          meta: { title: 'User Management', bootstrapIcon: 'person-badge' }
        }
      ]
    },

    {
      path: '/users/edit/:id(\\d+)',
      component: Layout,
      children: [
        {
          path: '',
          component: () => import('@/views/users/UserProfile.vue'),
          name: 'UserProfile',
          meta: { title: 'User Profile', noCache: true },
          hidden: true
        }
      ]
   },
  {
    path: '/profile',
    component: Layout,
    redirect: '/profile/edit',
    children: [
      {
        path: 'edit',
        component: () => import('@/views/users/SelfProfile.vue'),
        name: 'SelfProfile',
        meta: { title: 'userProfile', bootstrapIcon: 'person-circle', noCache: true }
      }
    ]
  },

  {
    path: '/requests',
    component: Layout,
    redirect: '/requests/list',
    name: 'RequestsManagement',
    meta: { title: 'Requests Management', bootstrapIcon: 'file-earmark-text', permissions: ['manage requests'] },
    children: [
      {
        path: 'list',
        component: () => import('@/views/requests/Requestsfb.vue'),
        name: 'Requests',
        meta: { title: 'Recipient Requests', bootstrapIcon: 'file-earmark-text', permissions: ['manage requests'] }
      }
    ]
  },

  {
    path: '/donations',
    component: Layout,
    redirect: '/donations/list',
    name: 'DonationsManagement',
    meta: { title: 'Donations Management', bootstrapIcon: 'gift', permissions: ['manage donations'] },
    children: [
      {
        path: 'list',
        component: () => import('@/views/donation/DonationManagement.vue'),
        name: 'Donations',
        meta: { title: 'Donations Management', bootstrapIcon: 'gift', permissions: ['manage donations'] }
      }
    ]
  },

  // 🆕 New Routes for Donation Requests
  {
    path: '/donation-request',
    component: Layout,
    redirect: '/donation-request/list',
    name: 'DonationRequest',
    meta: { title: 'Donation Requests', bootstrapIcon: 'gift', permissions: ['manage donations'] },
    children: [
      {
        path: 'list',
        component: () => import('@/views/donation/DonationRequest.vue'),
        name: 'DonationRequestList',
        meta: { title: 'Donation Requests ', bootstrapIcon: 'gift', permissions: ['manage donations'] }
      }
    ]
  },

  {
    path: '/feedback',
    component: Layout,
    redirect: '/feedback/list',
    name: 'FeedbackManagement',
    meta: { title: 'Feedback Management', bootstrapIcon: 'chat-left-text', permissions: ['manage feedback'] },
    children: [
      {
        path: 'list',
        component: () => import('@/views/feedback/FeedbackManagement.vue'),
        name: 'FeedbackList',
        meta: { title: 'Feedback List', bootstrapIcon: 'chat-left-text', permissions: ['manage feedback'] }
      }
    ]
  },

  // 🆕 New Routes for Report
  {
    path: '/reports',
    component: Layout,
    redirect: '/reports/donor-transactions',
    name: 'ReportsManagement',
    meta: { title: 'Reports Management', bootstrapIcon: 'file-earmark-bar-graph' },
    children: [
      {
        path: 'donor-transactions',
        component: () => import('@/components/Reports/DonorTransactionsReport.vue'),
        name: 'DonorTransactionsReport',
        meta: { title: 'Donor Transactions ', bootstrapIcon: 'person-badge' }
      },
      {
        path: 'foodbank-activity',
        component: () => import('@/components/Reports/FoodbankActivityReport.vue'),
        name: 'FoodbankActivityReport',
        meta: { title: 'Foodbank Activity ', bootstrapIcon: 'building' }
      },
      {
        path: 'recipient-requests',
        component: () => import('@/components/Reports/RecipientRequestsReport.vue'),
        name: 'RecipientRequests',
        meta: { title: 'Recipient Requests ', bootstrapIcon: 'file-earmark-text' }
      },
      {
        path: 'system-summary',
        component: () => import('@/components/Reports/SystemSummaryReport.vue'),
        name: 'SystemSummaryReport',
        meta: { title: 'System Summary ', bootstrapIcon: 'bar-chart-line' }
      }
    ]
  }
]

export const asyncRoutes = [

  adminRoutes,
  errorRoutes,
  { path: '/:pathMatch(.*)*', name: 'NotFound', redirect: '/404', hidden: true }
]

const router = createRouter({
  routes: constantRoutes,
  scrollBehavior: () => ({ top: 0 }),
  history: createWebHashHistory()
})

// Log all registered routes
console.log(router.getRoutes());

export function resetRouter() {
  const asyncRouterNameArr = asyncRoutes.map((mItem) => mItem.name)
  asyncRouterNameArr.forEach((name) => {
    if (router.hasRoute(name)) {
      router.removeRoute(name)
    }
  })
}

export default router
