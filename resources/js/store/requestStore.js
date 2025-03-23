import { defineStore } from 'pinia'
import service from '@/utils/request' // Use the axios instance

export const useRequestStore = defineStore('requestStore', {
  state: () => ({
    requests: [],
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0
    },
    filters: {
      type: null,
      quantity: null,
      status: null,
      recipient_id: null,
      search: '',
      sort_by: 'created_at',
      sort_order: 'desc'
    }
  }),

  actions: {
    async fetchUsers({ role = null, fetchAll = false, status = null } = {}) {
      try {
        console.log('Fetching users with role and status filters...')

        const params = {
          page: fetchAll ? 1 : this.pagination?.current_page || 1,
          per_page: fetchAll ? 1000 : this.pagination?.per_page || 10,
          sortBy: this.sortOptions?.key || 'name',
          sortOrder: this.sortOptions?.order || 'asc'
        }

        if (role) params.role = role // Apply role filtering
        if (status) params.status = status // Apply status filtering

        const response = await service.get('/all-users', { params })

        this.users = response.data // Store API response (No `.data.data` needed)

        // ✅ Update pagination only when NOT fetching all users
        if (!fetchAll) {
          this.pagination = {
            total: response.total || 0,
            current_page: response.current_page || 1,
            per_page: response.per_page || 10
          }
        }

        console.log('Fetched users:', this.users)
      } catch (error) {
        console.error('Failed to fetch users:', error)
        ElMessage.error(error.response?.data?.message || 'Could not fetch users.')
      }
    },

    async fetchRequests() {
      this.loading = true
      try {
        console.log('%c[DEBUG] Fetching requests with filters and pagination...', 'color: #4CAF50; font-weight: bold;')

        // Build query params based on filters
        const params = {
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          ...this.filters
        }

        console.log('%c[DEBUG] Applied Filters:', 'color: #FFC107;', this.filters)
        console.log('%c[DEBUG] Applied Pagination:', 'color: #FFC107;', this.pagination)

        const response = await service.get('/requestsfb', { params })
        console.log('%c[DEBUG] Response received:', 'color: #4CAF50;', response.data)

        // Update state based on response
        this.requests = response.data
        this.pagination = {
          current_page: response.current_page,
          last_page: response.last_page,
          per_page: response.per_page,
          total: response.total
        }

        console.log('%c[DEBUG] Updated Requests:', 'color: #4CAF50;', this.requests)
        console.log('%c[DEBUG] Updated Pagination:', 'color: #4CAF50;', this.pagination)
      } catch (error) {
        console.error(
          '%c[ERROR] Failed to fetch requests:',
          'color: #F44336;',
          error.response ? error.response.data : error
        )
      } finally {
        this.loading = false
      }
    },

    // Method to update filters
    updateFilter(key, value) {
      console.log(`%c[DEBUG] Updating filter: ${key} = ${value}`, 'color: #FFC107;')
      this.filters[key] = value
      this.pagination.current_page = 1
      this.fetchRequests()
    },

    // Method to update sorting
    updateSorting(sortBy) {
      console.log(`%c[DEBUG] Sorting by: ${sortBy}`, 'color: #FFC107;')
      if (this.filters.sort_by === sortBy) {
        this.filters.sort_order = this.filters.sort_order === 'asc' ? 'desc' : 'asc'
      } else {
        this.filters.sort_by = sortBy
        this.filters.sort_order = 'asc'
      }
      console.log('%c[DEBUG] Updated Sorting:', 'color: #FFC107;', {
        sort_by: this.filters.sort_by,
        sort_order: this.filters.sort_order
      })
      this.fetchRequests()
    },

    // Create Request
    async createRequest(payload) {
      try {
        const response = await service.post('/requestsfb', payload) // Create API call
        this.requests.push(response.data)
        console.log('Request created successfully:', response.data)
      } catch (error) {
        console.error('Failed to create request:', error)
        ElMessage.error('Failed to create request.')
      }
    },

    // Update Request
    async updateRequest(payload) {
      try {
        const response = await service.put(`/requestsfb/${payload.id}`, payload) // Update API call
        const index = this.requests.findIndex((r) => r.id === payload.id)
        if (index !== -1) {
          this.requests[index] = response.data // Update local list
        }
        console.log('Request updated successfully:', response.data)
      } catch (error) {
        console.error('Failed to update request:', error)
        ElMessage.error('Failed to update request.')
      }
    },

    // New Action: Update Request Status
    async updateRequestStatus(id, status) {
      try {
        const response = await service.post(`/requestsfb/${id}/status`, { status })
        const updatedRequest = response.data.data

        // Find and update the request in the local state
        const index = this.requests.findIndex((r) => r.id === id)
        if (index !== -1) {
          this.requests[index] = updatedRequest
        }

        console.log('Request status updated successfully:', updatedRequest)

        // 🔄 Refresh the request list after status update
        await this.fetchRequests() // <-- Added this line to refresh the list
      } catch (error) {
        console.error('Failed to update request status:', error)
        ElMessage.error('Failed to update request status.')
      }
    }
  }
})
