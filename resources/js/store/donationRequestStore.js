import { defineStore } from 'pinia'
import service from '@/utils/request'
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'

export const useDonationRequestStore = defineStore(
  'donationRequest',
  () => {
    // State
    const donationRequests = ref([])
    //   const donors = ref([]);
    const notifications = ref([])
    const totalRequests = ref(0)
    const loading = ref(false)
    const error = ref(null)

    // Pagination & Filters
    const pagination = reactive({
      page: 1,
      pageSize: 10,
      total: 0
    })

    const filters = reactive({
      status: '', // e.g., 'pending', 'approved', 'rejected'
      type: '', // e.g., 'food', 'clothes'
      donorId: '' // Specific donor filter
    })

    const sort = reactive({
      field: 'created_at', // Default sort by date
      order: 'desc' // or 'asc'
    })

    // Actions
    const fetchDonationRequests = async () => {
      loading.value = true
      error.value = null

      try {
        const params = {
          page: pagination.page,
          pageSize: pagination.pageSize,
          status: filters.status || undefined,
          type: filters.type || undefined,
          donor_id: filters.donorId || undefined,
          sortField: sort.field,
          sortOrder: sort.order
        }

        const response = await service.get('/donation-requests', { params })
        console.log('API Response:', response)

        donationRequests.value = response.data
        console.log(donationRequests.value)
        pagination.total = response.meta.total
        totalRequests.value = response.meta.total
      } catch (err) {
        error.value = err.message
        ElMessage.error(`Failed to fetch donation requests: ${err.message}`)
      } finally {
        loading.value = false
      }
    }

    const createDonationRequest = async (payload) => {
      loading.value = true
      error.value = null

      try {
        const response = await service.post('/donation-requests', payload)
        donationRequests.value.unshift(response) // Add to the top of the list
        await fetchDonationRequests()
        ElMessage.success('Donation request created successfully!')
      } catch (err) {
        error.value = err.message
        ElMessage.error(`Failed to create donation request: ${err.message}`)
      } finally {
        loading.value = false
      }
    }

    const updateDonationRequestStatus = async (id, status) => {
      loading.value = true
      error.value = null

      try {
        const response = await service.put(`/donation-requests/${id}/status`, { status })
        const index = donationRequests.value.findIndex((req) => req.id === id)
        if (index !== -1) donationRequests.value[index] = response
        await fetchDonationRequests()
        ElMessage.success(`Donation request status updated to ${status}!`)
      } catch (err) {
        error.value = err.message
        ElMessage.error(`Failed to update status: ${err.message}`)
      } finally {
        loading.value = false
      }
    }

    const editDonationRequest = async (form) => {
      // ✅ Use form directly
      loading.value = true
      error.value = null

      try {
        // Prepare payload with only expected fields
        const payload = {
          donor_id: form.donor_id,
          foodbank_id: form.foodbank_id,
          type: form.type,
          quantity: form.quantity,
          status: form.status,
          description: form.description // Include if your backend expects this field
        }
        console.log('Payload for PUT Request:', payload)

        const response = await service.put(`/donation-requests/${form.id}`, payload)
        const index = donationRequests.value.findIndex((req) => req.id === form.id)
        if (index !== -1) donationRequests.value[index] = response.data

        // 🟢 Refresh the list after editing
        await fetchDonationRequests() // ✅ Add this line to refresh the list

        ElMessage.success('Donation request updated successfully!')
      } catch (err) {
        error.value = err.message
        ElMessage.error(`Failed to update donation request: ${err.message}`)
      } finally {
        loading.value = false
      }
    }

    //   const fetchDonors = async () => {
    //     loading.value = true;
    //     error.value = null;

    //     try {
    //       const response = await service.get('/donors');
    //       donors.value = response;
    //     } catch (err) {
    //       error.value = err.message;
    //       ElMessage.error(`Failed to fetch donors: ${err.message}`);
    //     } finally {
    //       loading.value = false;
    //     }
    //   };

    const fetchNotifications = async () => {
      loading.value = true
      error.value = null

      try {
        const response = await service.get('/notifications')
        notifications.value = response
      } catch (err) {
        error.value = err.message
        ElMessage.error(`Failed to fetch notifications: ${err.message}`)
      } finally {
        loading.value = false
      }
    }

    // Methods to handle filters, sorting, and pagination
    const setFilter = (field, value) => {
      filters[field] = value
      pagination.page = 1 // Reset to first page when filter changes
      fetchDonationRequests()
    }

    const setSort = (field) => {
      if (sort.field === field) {
        sort.order = sort.order === 'asc' ? 'desc' : 'asc' // Toggle sort order
      } else {
        sort.field = field
        sort.order = 'asc' // Default to ascending if changing field
      }
      fetchDonationRequests()
    }

    const setPage = (page) => {
      pagination.page = page
      fetchDonationRequests()
    }

    const setPageSize = (size) => {
      pagination.pageSize = size
      pagination.page = 1 // Reset to first page when page size changes
      fetchDonationRequests()
    }

    // Persist store state
    return {
      donationRequests,
      notifications,
      totalRequests,
      loading,
      error,
      pagination,
      filters,
      sort,
      fetchDonationRequests,
      createDonationRequest,
      updateDonationRequestStatus,
      setFilter,
      setSort,
      setPage,
      setPageSize
    }
  },
  {
    persist: {
      key: 'donationRequestStore', // Custom key for local storage
      storage: localStorage, // Use localStorage (default)
      paths: ['donationRequests', 'pagination', 'filters', 'sort'] // Specify which parts to persist
    }
  }
)
