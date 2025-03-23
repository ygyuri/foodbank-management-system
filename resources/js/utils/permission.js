import { userStore } from '@/store/user'

/**
 * @param {Array} value
 * @returns {Boolean}
 * @example see @/views/permission/Directive.vue
 */
export default function checkPermission(value) {
  const useUserStore = userStore()
  if (value && value instanceof Array && value.length > 0) {
    const permissions = useUserStore.permissions
    const requiredPermissions = value
    console.log('User Permissions:', permissions)
    console.log('Required Permissions:', requiredPermissions)

    return permissions.some((permission) => {
      console.log('Checking permission:', permission)
      return requiredPermissions.includes(permission)
    })
  } else {
    console.error(`Need permissions! Like v-permission="['manage permission','edit article']"`)
    return false
  }
}
