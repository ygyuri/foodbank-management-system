import { defineStore } from 'pinia'
import service from '@/utils/request' // Axios instance
import { ElMessage } from 'element-plus';


export const useFeedbackStore = defineStore('feedback', {
  state: () => ({
    feedbackList: [],
    feedbackDetails: null,
    pagination: {
      currentPage: 1,
      perPage: 10,
      totalPages: 0,
      totalItems: 0
    },
    filters: {
      receiver_id: null,
      sender_id: null,
      rating: null
    },
    loading: false,
    error: null
  }),

  actions: {

  
    async fetchFeedback(page = 1) {
      this.loading = true;
      this.error = null;
    
      try {
        console.log('Fetching feedback...');
        console.log('Applied Filters:', this.filters);
        console.log('Fetching Page:', page);
    
        const response = await service.get('/feedback', {
          params: { ...this.filters, page }
        });
    
        // Since response.data is already processed by the interceptor, access properties directly
        console.log('Response Data:', response);
    
        this.feedbackList = response.data; // `data` is at the root level
    
        this.pagination = {
          currentPage: response.current_page,
          perPage: response.per_page,
          totalPages: response.last_page,
          totalItems: response.total
        };
    
        console.log('Updated Feedback List:', this.feedbackList);
        console.log('Updated Pagination:', this.pagination);
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch feedback';
        console.error('Fetch Feedback Error:', error);
      } finally {
        this.loading = false;
        console.log('Fetching process completed.');
      }
    },
    
    

    async getFeedback(id) {
      this.loading = true
      this.error = null
      try {
        const response = await service.get(`/feedback/${id}`)
        this.feedbackDetails = response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch feedback details'
      } finally {
        this.loading = false
      }
    },
    async createFeedback(payload) {
      this.loading = true;
      this.error = null;
      try {
        await service.post('/feedback', payload);
        ElMessage.success('Feedback created successfully!');
    
        // Refresh feedback list
        await this.fetchFeedback(this.pagination.currentPage);
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create feedback';
        ElMessage.error(this.error);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    

    async updateFeedback(id, payload) {
      this.loading = true
      this.error = null
      try {
        await service.put(`/feedback/${id}`, payload)
        await this.fetchFeedback(this.pagination.currentPage) // Refresh list
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update feedback'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteFeedback(id) {
      this.loading = true
      this.error = null
      try {
        await service.delete(`/feedback/${id}`)
        await this.fetchFeedback(this.pagination.currentPage) // Refresh list
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete feedback'
        throw error
      } finally {
        this.loading = false
      }
    },

    setFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters }
    }
  }
})
