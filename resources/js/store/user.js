import { Ability } from '@casl/ability';

import { login, logout, getInfo } from '@/api/auth'
import { isLogged, setToken, removeToken } from '@/utils/auth'
import router, { resetRouter } from '../router'
import { defineStore } from 'pinia'
import { permissionStore } from '@/store/permission'
import RoleResource from '@/api/role' // ✅ Import RoleResource
import { ElMessage } from 'element-plus'
import UserResource from '@/api/user' // Import User API Resource
import request from '@/utils/request'

import { defineAbility } from '@casl/ability'
import { defineAbilitiesFor } from '@/utils/ability'
import { ability } from '@/utils/ability' // ✅ Import ability instance

import { reactive, computed } from 'vue'



export const userStore = defineStore('user', {
  state: () => {
    return {
      id: null,
      user: null,
      token: null,
      name: '',
      avatar: '',
      introduction: '',
      roles: [],
      permissions: [],
      users: [], // Store user list
      roleList: [], // ✅ Store list of roles
      ability: new Ability([]),
      // Explicit Type // ✅ Initialize CASL Ability with an empty array
      pagination: reactive({
        current_page: 1,
        per_page: 10,
        total: 0
      }),
      filters: {
        name: '',
        email: '',
        role: '',
        status: '',
        location: ''
      },
      sortOptions: {
        key: 'name',
        order: 'asc'
      }
    }
  },

  
  getters: {
    paginatedUsers(state) {
      return state.users // ✅ Directly return users from API
    },

    // 🔍 **Filtered Users**
    filteredUsers(state) {
      return state.users.filter((user) => {
        return (
          (!state.filters.name || user.name.toLowerCase().includes(state.filters.name.toLowerCase())) &&
          (!state.filters.email || user.email?.toLowerCase().includes(state.filters.email.toLowerCase())) &&
          (!state.filters.role || user.role?.toLowerCase() === state.filters.role.toLowerCase()) &&
          (!state.filters.status || user.status?.toLowerCase() === state.filters.status.toLowerCase()) &&
          (!state.filters.location || user.location?.toLowerCase().includes(state.filters.location.toLowerCase()))
        )
      })
    },

    // 🔄 **Sorted Users**
    sortedUsers(state) {
      return [...state.filteredUsers].sort((a, b) => {
        let modifier = state.sortOptions.order === 'asc' ? 1 : -1
        if (a[state.sortOptions.key] < b[state.sortOptions.key]) return -1 * modifier
        if (a[state.sortOptions.key] > b[state.sortOptions.key]) return 1 * modifier
        return 0
      })
    }
  },

  actions: {
    async approveUser(id) {
      try {
        const response = await request({
          url: `/users/${id}/approve`,
          method: 'post'
        })
        return response.data
      } catch (error) {
        console.error('Error approving user:', error)
        throw error
      }
    },

    async rejectUser(id) {
      try {
        const response = await request({
          url: `/users/${id}/reject`,
          method: 'post'
        })
        return response.data
      } catch (error) {
        console.error('Error rejecting user:', error)
        throw error
      }
    },

    async resetStatus(id) {
      try {
        const response = await request({
          url: `/users/${id}/reset-status`,
          method: 'post'
        })
        return response.data
      } catch (error) {
        console.error('Error resetting user status:', error)
        throw error
      }
    },

    // ✅ Fetch Roles
    async fetchRoles() {
      try {
        const response = await new RoleResource().list() // Ensure API exists
        this.roleList = response.data || []
      } catch (error) {
        console.error('Failed to fetch roles:', error)
        ElMessage.error('Could not load roles.')
      }
    },

    // ✅ Fetch Role Permissions
    async fetchRolePermissions(roleId) {
      try {
        const response = await new RoleResource().permissions(roleId)
        return response.data || []
      } catch (error) {
        console.error('Failed to fetch role permissions:', error)
        ElMessage.error('Could not load role permissions.')
      }
    },

    // ✅ Assign Role (Optional)
    async assignRoleToUser(userId, roleId) {
      try {
        const response = await new RoleResource().update(userId, { role_id: roleId })
        ElMessage.success('Role assigned successfully!')
        return response
      } catch (error) {
        console.error('Failed to assign role:', error)
        ElMessage.error('Could not assign role.')
        throw error
      }
    },

    // ✅ Create new user
    // Create a new user
    async createUser(userData) {
      try {
        this.loading = true
        const response = await new UserResource().createUser(userData)
        ElMessage.success('User created successfully!')
        this.fetchUsers() // Refresh user list
        return response
      } catch (error) {
        ElMessage.error(error.response?.data?.message || 'User creation failed')
        throw error
      } finally {
        this.loading = false
      }
    },

    // Edit an existing user
    async editUser(userId, userData) {
      try {
        this.loading = true
        const response = await new UserResource().update(userId, userData)
        ElMessage.success('User updated successfully!')
        this.fetchUsers() // Refresh user list
        return response
      } catch (error) {
        ElMessage.error(error.response?.data?.message || 'User update failed')
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchUsers() {
      try {
        console.log('Fetching users with filters, sorting, and pagination...')

        const response = await new UserResource().list({
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          sortBy: this.sortOptions.key,
          sortOrder: this.sortOptions.order,
          ...this.filters
        })

        this.users = response.data // API returns paginated data under `data`

        // ✅ Update Pagination State using the correct structure
        this.pagination.total = response.meta.total
        this.pagination.current_page = response.meta.current_page
        this.pagination.per_page = response.meta.per_page

        console.log('Users fetched:', this.users)
        console.log('Updated Pagination:', this.pagination)
      } catch (error) {
        console.error('Failed to fetch users:', error)
        ElMessage.error('Could not fetch users.')
      }
    },

    // ✅ Set Current Page and Fetch Data
    setCurrentPage(page) {
      console.log('setCurrentPage called with page:', page) // Debugging
      if (page !== this.pagination.current_page) {
        this.pagination.current_page = page
        this.fetchUsers()
      }
    },
    // ✅ Set Items Per Page and Fetch Data
    setPerPage(perPage) {
      if (perPage !== this.pagination.per_page) {
        this.pagination.per_page = perPage
        this.pagination.current_page = 1 // Reset to first page
        this.fetchUsers()
      }
    },

    // ✅ Set Filters and Fetch Data
    setFilter(key, value) {
      this.filters[key] = value
      this.pagination.current_page = 1 // Reset to first page when filtering
      this.fetchUsers()
    },

    // ✅ Set Sorting Options and Fetch Data
    setSortOptions(key, order) {
      this.sortOptions = { key, order }
      this.fetchUsers()
    },

   
  // User login
  login(userInfo) {
    const { email, password } = userInfo
    return new Promise((resolve, reject) => {
      console.log(`Attempting login for: ${email}`) // Debug log
      login({ email: email.trim(), password })
        .then((response) => {
          console.log('Login successful:', response) // Debug log
          setToken(response.data.token)
          resolve()
        })
        .catch((error) => {
          console.log('Login failed:', error) // Debug log
          reject(error)
        })
    })
  },

  // Fetch user info and update CASL abilities
  // Fetch user info and update CASL abilities
  async getInfo() {
    try {
      console.log('Fetching user info...')

      const response = await getInfo() // API call
      const { data } = response

      if (!data) throw new Error('Verification failed, please login again.')

      const { roles, name, avatar, introduction, permissions, id } = data

      if (!roles || roles.length === 0) {
        throw new Error('getInfo: roles must be a non-null array!')
      }

      console.log(`User info retrieved - Name: ${name}, Roles: ${roles}, Permissions: ${permissions}`)

      // ✅ Update store state
      this.$patch({
        id,
        name,
        avatar,
        introduction,
        roles,
        permissions
      })
      console.log('User store updated:', { id, name, roles, permissions }) // Confirm store update

      // ✅ Define and update CASL Ability dynamically
      const userAbilities = defineAbilitiesFor(roles, permissions)
      console.log('Generated abilities:', JSON.stringify(userAbilities.rules, null, 2)); // Pretty print the abilities

      ability.update(userAbilities.rules) // ✅ Update CASL Ability

      console.log('CASL Abilities updated successfully:', userAbilities.rules) // Confirm ability update

      return data
    } catch (error) {
      console.error('Error fetching user info:', error)
      throw error
    }
  },

  // User logout
  logout() {
    return new Promise((resolve, reject) => {
      console.log('Logging out...') // Debug log

      logout()
        .then(() => {
          console.log('Clearing user data and permissions...') // Confirm clearing data

          this.$patch((state) => {
            state.token = ''
            state.roles = []
            state.permissions = []
          })

          ability.update([]) // ✅ Reset CASL Ability on logout
          console.log('CASL Abilities reset') // Confirm ability reset

          removeToken()
          resetRouter()

          console.log('Logout successful') // Debug log
          resolve()
        })
        .catch((error) => {
          console.error('Logout failed:', error) // Debug log
          reject(error)
        })
    })
  },

    // remove token
    resetToken() {
      return new Promise((resolve) => {
        console.log('Resetting token') // Debug log
        this.$patch((state) => {
          state.token = ''
          state.roles = []
        })
        removeToken()
        resolve()
      })
    },
    // Dynamically modify permissions
    changeRoles(role) {
      return new Promise(async (resolve) => {
        console.log('Changing roles to:', role.name) // Debug log
        const roles = [role.name]
        const permissions = role.permissions.map((permission) => permission.name)
        console.log('New permissions:', permissions) // Debug log
        this.$patch((state) => {
          state.permissions = permissions
          state.roles = roles
        })
        resetRouter()

        // generate accessible routes map based on roles
        const usePermissionStore = permissionStore()
        console.log('Generating routes for roles:', roles) // Debug log
        const accessRoutes = await usePermissionStore.generateRoutes(roles, permissions)
        console.log('Access routes generated:', accessRoutes) // Debug log

        // dynamically add accessible routes
        accessRoutes.forEach((item) => {
          router.addRoute(item)
        })

        resolve()
      })
      
    }
  }
})
