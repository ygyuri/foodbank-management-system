import { defineStore } from 'pinia';
import service from '@/utils/request';

export const useReportStore = defineStore('reportStore', {
  state: () => ({
    foodbankActivities: [],
    donorTransactions: [],
    recipientRequests: [],
    systemSummary: {},
    foodbankLoading: false,
    foodbankError: null,
  }),

  actions: {
    async fetchFoodbankActivityReport(foodbankId = null) {
      console.log('[Store] Fetching foodbank activity report...');
      this.foodbankLoading = true;
      this.foodbankError = null;
      try {
        const endpoint = foodbankId ? `/reports/foodbank/${foodbankId}` : '/reports/foodbank';
        const response = await service.get(endpoint);
        console.log('[Store] Foodbank activity report fetched:', response.foodbanks);
        this.foodbankActivities = response.foodbanks || [];
      } catch (error) {
        console.error('[Store] Error fetching foodbank activity report:', error);
        this.foodbankError = error.message || 'Failed to fetch foodbank activities.';
      } finally {
        this.foodbankLoading = false;
      }
    },

    async fetchDonorTransactionReport(donorId = null) {
      this.donorLoading = true;
      this.donorError = null;
      try {
        const endpoint = donorId ? `/reports/donor/${donorId}` : '/reports/donor';
        const response = await service.get(endpoint);
       // console.log('[Store] Donor transaction report fetched:', response.donors);
        this.donorTransactions = response.donors || [];
      } catch (error) {
       // console.error('[Store] Error fetching donor transaction report:', error);
        this.donorError = error.message;
      } finally {
        this.donorLoading = false;
      }
    },

    async fetchRecipientRequestReport(recipientId = null) {
    //  console.log('[Store] Fetching recipient request report...');
      this.loading = true;
      this.error = null;
      try {
        const endpoint = recipientId ? `/reports/recipient/${recipientId}` : '/reports/recipient';
        const response = await service.get(endpoint);
       // console.log('[Store] Recipient request report fetched:', response.recipients);
        this.recipientRequests = response.recipients || [];
      } catch (error) {
      //  console.error('[Store] Error fetching recipient request report:', error);
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },

    async fetchSystemSummaryReport() {
    //  console.log('[Store] Fetching system summary report...');
      this.loading = true;
      this.error = null;
      try {
        const response = await service.get('/reports/system-summary');
       // console.log('[Store] System summary report fetched:', response.summary);
        this.systemSummary = response.summary || {};
      } catch (error) {
       // console.error('[Store] Error fetching system summary report:', error);
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },
  },
});