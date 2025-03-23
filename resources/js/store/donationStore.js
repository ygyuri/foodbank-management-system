// src/stores/donationStore.js

import { defineStore } from 'pinia'
import service from '@/utils/request' // Axios instance
import { ElMessage } from 'element-plus'
import { ref } from 'vue'

export const useDonationStore = defineStore('donationStore', {
  state: () => ({
    donations: [],
    donation: null,
    totalDonations: 0,
    loading: false,
    error: null,
    pagination: {
      currentPage: 1,
      perPage: 10
    }
  }),
  actions: {
    // // Fetch list of donations with pagination and optional filters
    async fetchDonations(filters = {}) {
      this.loading = true
      this.error = null
      const params = {
        page: this.pagination.currentPage,
        perPage: this.pagination.perPage,
        ...filters
      }

      try {
        console.log('Sending request to /donations with params:', params)
        const response = await service.get('/donations', { params })
        console.log('Received response:', response)

        // Correct destructuring based on nested response structure
        const { data, total, current_page, per_page } = response.data

        this.donations = response.data // Use nested data array
        this.totalDonations = response.total
        this.pagination.currentPage = response.current_page
        this.pagination.perPage = response.per_page

        // Additional console logs for assigned values
        console.log('Assigned donations:', this.donations)
        console.log('Total donations count:', this.totalDonations)
        console.log('Updated Pagination:', this.pagination)

        ElMessage.success('Donations fetched successfully!')
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch donations'
        ElMessage.error(this.error)
      } finally {
        this.loading = false
      }
    },

    // Fetch a specific donation by ID
    async fetchDonationById(id) {
      this.loading = true
      this.error = null

      try {
        const response = await service.get(`/donations/${id}`)
        this.donation = response.data
        ElMessage.success('Donation details fetched successfully!')
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch donation'
        ElMessage.error(this.error)
      } finally {
        this.loading = false
      }
    },

    // Create a new donation
    async createDonation(donationData) {
      this.loading = true
      this.error = null

      try {
        const response = await service.post('/donations', donationData)
        this.donations.unshift(response.data)
        ElMessage.success('Donation created successfully!')
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create donation'
        ElMessage.error(this.error)
      } finally {
        this.loading = false
      }
    },

    // Update an existing donation
    async updateDonation(id, donationData) {
      this.loading = true
      this.error = null

      try {
        const response = await service.put(`/donations/${id}`, donationData)
        const index = this.donations.findIndex((d) => d.id === id)
        if (index !== -1) this.donations.splice(index, 1, response.data)
        ElMessage.success('Donation updated successfully!')
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update donation'
        ElMessage.error(this.error)
      } finally {
        this.loading = false
      }
    },

    // Delete a donation
    async deleteDonation(id) {
      this.loading = true
      this.error = null

      try {
        await service.delete(`/donations/${id}`)
        this.donations = this.donations.filter((d) => d.id !== id)
        ElMessage.success('Donation deleted successfully!')
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete donation'
        ElMessage.error(this.error)
      } finally {
        this.loading = false
      }
    },

    // Assign a foodbank to a donation
    async assignFoodbank(donationId, foodbankId, router) {
      this.loading = true
      this.error = null

      try {
        const response = await service.post(`/donations/${donationId}/assign-foodbank/${foodbankId}`)
        console.log('API Response:', response) // <-- Log full response
        console.log('Full Response:', response) // Add this line

        const updatedDonation = response.donation
        console.log('Updated Donation:', updatedDonation) // <-- Log donation data

        if (!updatedDonation) throw new Error('Donation data is missing!')

        // Update the specific donation in the store
        const index = this.donations.findIndex((d) => d.id === donationId)
        console.log('Donation Index:', index) // <-- Log index

        if (index !== -1) this.donations.splice(index, 1, updatedDonation)

        // Show success message
        ElMessage.success('Foodbank assigned successfully!')

        // Refresh the list
        await this.fetchDonations()
      } catch (error) {
        console.error('Error during assignment:', error)
        this.error = error?.message || error.response?.data?.message || 'An unexpected error occurred'

        ElMessage.error(this.error)
      } finally {
        this.loading = false
      }
    },
    async updateDonationStatus(donationId, newStatus) {
      console.log('Attempting to update status for Donation ID:', donationId, 'to:', newStatus)
      try {
        const response = await service.post(`/donations/${donationId}/update-status`, {
          status: newStatus.toString()
        })
        console.log('API Response:', response.donation)
        return response.donation // Return response to handle it in the component
      } catch (error) {
        console.error('Error updating donation status:', error)
        throw error // Re-throw error for further handling
      }
    },

    // Mark a donation as completed
    async markAsCompleted(donationId) {
      this.loading = true
      this.error = null

      try {
        const response = await service.post(`/api/donations/${donationId}/complete`)
        const updatedDonation = response.data.donation
        const index = this.donations.findIndex((d) => d.id === donationId)
        if (index !== -1) this.donations.splice(index, 1, updatedDonation)
        ElMessage.success('Donation marked as completed!')
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to complete donation'
        ElMessage.error(this.error)
      } finally {
        this.loading = false
      }
    },

    // Handle pagination
    setPage(page) {
      this.pagination.currentPage = page
      this.fetchDonations()
    }
  }
})
