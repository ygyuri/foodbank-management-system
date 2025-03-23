/** When your routing table is too long, you can split it into small modules**/
import Layout from '@/layout/Layout.vue'

const adminRoutes = {
  path: '/administrator',
  component: Layout,
  redirect: '/administrator/users',
  name: 'Administrator',
  alwaysShow: true,
  meta: {
    title: 'administrator',
    bootstrapIcon: 'person-workspace',
    permissions: ['view menu administrator']
  },
  children: [
    /** User managements */
    {
      path: 'users/edit/:id(\\d+)',
      component: () => import('@/views/users/UserProfile.vue'),
      name: 'UserProfile',
      meta: { title: 'userProfile', noCache: true, permissions: ['manage user'] },
      hidden: true
    },
    /** New User Management Route (Added Here) */
    // {
    //   path: 'new-user-management',
    //   component: () => import('@/views/users/NewUserManagement.vue'),
    //   name: 'NewUserManagement',
    //   meta: { title: 'User Management', bootstrapIcon: 'person-badge', permissions: ['manage user'] }
    // },
    // {
    //   path: 'users',
    //   component: () => import('@/views/users/List.vue'),
    //   name: 'UserList',
    //   meta: {title: 'users', bootstrapIcon: 'people', permissions: ['manage user']},
    // },
    /** Role and permission */
    // {
    //   path: 'roles',
    //   component: () => import('@/views/role-permission/List.vue'),
    //   name: 'RoleList',
    //   meta: { title: 'rolePermission', bootstrapIcon: 'person-lines-fill', permissions: ['manage permission'] }
    // },
    

      /** Role Permissions Manager Route (Added Here) */
      {
        path: 'role-permissions',
        component: () => import('@/views/role-permission/RolePermissionsManager.vue'),
        name: 'RolePermissionsManager',
        meta: { title: 'Role Permissions', bootstrapIcon: 'shield-lock', permissions: ['manage permission'] },
      },

    //  /** Requests Management */
    //  {
    //   path: 'requests',
    //   component: () => import('@/views/requests/Requestsfb.vue'),
    //   name: 'Requests',
    //   meta: { title: 'Requests', bootstrapIcon: 'file-earmark-text', permissions: ['manage requests'] },
    // },
  ]
}

export default adminRoutes
