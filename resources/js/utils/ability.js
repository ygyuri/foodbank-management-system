import { AbilityBuilder, Ability } from '@casl/ability'

/**
 * Defines abilities based on the user's role and permissions.
 * @param {Array} roles - The user's roles.
 * @param {Array} permissions - The user's permissions.
 * @returns {Ability} - CASL Ability instance.
 */
export function defineAbilitiesFor(roles, permissions) {
  const { can, cannot, rules } = new AbilityBuilder(Ability)

  if (!roles || roles.length === 0) {
    return new Ability([]) // Return empty abilities if no roles are defined
  }

  // General access
  can('view', 'dashboard')

  roles.forEach((role) => {
    switch (role) {
      case 'admin':
        can('manage', 'all') // Admin has unrestricted access
        can("view", "filters"); // ✅ Allow admins to see filters
        can("view", "donations");
        can("edit", "donations");
        can("delete", "donations");
        can("approve", "donations");
        can("reject", "donations");
        can('view', 'users') // Admin can view all users
        can('create', 'users') // Admin can create users
        can('edit', 'users') // Admin can edit any user
        can('delete', 'users') // Admin can delete any user
        can('changeStatus', 'users') // Admin can change user status
        break

      case 'donor':
        can('manage', 'donationManagement')
        can('manage', 'donationRequests')
        cannot('manage', 'recipientRequests') // ❌ Donors cannot manage recipient requests
        can('view', 'donations')
        can('edit', 'donations', { donor_id: 'own' });
        can('delete', 'donations', { donor_id: 'own' });
        cannot('approve', 'donations');
        cannot('reject', 'donations');
        can("view", "filters"); // ✅ Allow donors to see filters
        can('view', 'users', { user_id: 'own' }) // Donors can view their own user record
        can('create', 'users') // Donors can create users
        can('edit', 'users', { user_id: 'own' }) // Donors can edit their own user record
        can('delete', 'users', { user_id: 'own' }) // Donors can delete their own user record
        cannot('changeStatus', 'users') // Donors cannot change user status
        break

      case 'foodbank':
        can('manage', 'recipientRequests')
        can('manage', 'donationRequests')
        cannot('approve', 'donationRequests')
        cannot('reject', 'donationRequests')
        can("view", "filters"); // ✅ Allow foodbanks to see filters
        can('view', 'donations', { user_id: 'own' });
        can("approve", "donations"); // If foodbanks can approve
        can("reject", "donations");
        can('view', 'users', { user_id: 'own' }) // Foodbanks can view their own user record
        can('create', 'users') // Foodbanks can create users
        can('edit', 'users', { user_id: 'own' }) // Foodbanks can edit their own user record
        can('delete', 'users', { user_id: 'own' }) // Foodbanks can delete their own user record
        cannot('changeStatus', 'users') // Foodbanks cannot change user status
        
        break

      case 'recipient':
        can('manage', 'recipientRequests')
        cannot('approve', 'recipientRequests')
        cannot('reject', 'recipientRequests')
        can("view", "filters"); // ✅ Allow recipients to see filters
        can('view', 'users', { user_id: 'own' }) // Recipients can view their own user record
        can('create', 'users') // Recipients can create users
        can('edit', 'users', { user_id: 'own' }) // Recipients can edit their own user record
        cannot('delete', 'users') // Recipients cannot delete users
        cannot('changeStatus', 'users') // Recipients cannot change user status
        break

      default:
        cannot('manage', 'all')
        break
    }
  })

  // Common permissions across roles
  can('send', 'feedback')
  can('edit', 'feedback')
  can('view', 'feedback')
  can('delete', 'feedback')

  // Backend-defined permissions
  if (permissions && Array.isArray(permissions)) {
    permissions.forEach(({ action, subject }) => {
      if (action && subject) { // ✅ Ensure both values are present
        can(action, subject)
      } else {
        console.warn('Skipping invalid permission:', { action, subject }) // Debug log
      }
    })
  }
  

  return new Ability(rules)
}

// ✅ Create a reactive ability instance
export const ability = new Ability([])


// **Function to dynamically update abilities when roles change**
export const updateAbilities = () => {
    const user = userStore();
    const { roleList, permissionList } = user;
    ability.update(defineAbilitiesFor(roleList, permissionList).rules);
  };
  
  // **Watch for role/permission changes and update abilities**
  watch(
    () => [userStore().roleList, userStore().permissionList],
    () => {
      updateAbilities();
    },
    { deep: true }
  );
