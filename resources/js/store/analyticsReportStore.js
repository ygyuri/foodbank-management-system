import { defineStore } from 'pinia';
import { ref } from 'vue';
import service from '@/utils/request'; // Axios instance

export const useAnalyticsReportStore = defineStore('analyticsReport', () => {
  // State
  const overview = ref(null);
  const donationTrends = ref([]);
  const foodbankStats = ref([]);
  const donorStats = ref([]);
  const recipientStats = ref([]);
  const donationTrendsByDay = ref([]);
  const recipientDemographics = ref([]);
  const foodbankInformation = ref([]);
  const donorInformation = ref([]);
  const loading = ref(false);
  const error = ref(null);

  // Actions
  // Actions
  const fetchOverview = async () => {
    loading.value = true;
    error.value = null;
  
    try {
      console.log('Fetching overview data...'); // Log start of the request
  
      // Fetch data from the API
      const response = await service.get('/analytics-reports/overview');
  
      // Log the response data
     // console.log('Response Data:', response);
  
      // Check if the response data is valid
      if (response) {
        // Update the overview object with the new structure
        overview.value = {
          total_donations: response.total_donations,
          total_foodbanks: response.total_foodbanks,
          total_donors: response.total_donors,
          total_recipients: response.total_recipients,
          historical_data: {
            donations: response.historical_data.donations,
            foodbanks: response.historical_data.foodbanks,
            donors: response.historical_data.donors,
            recipients: response.historical_data.recipients,
          },
        };
  
       // console.log('Overview data successfully fetched and stored:', overview.value);
      } else {
        console.error('Empty or invalid response data:', response);
        error.value = 'Empty or invalid response data';
      }
    } catch (err) {
      // Log the error details
      console.error('Error fetching overview:', err);
  
      // Extract and log the error message
      error.value = err.response?.data?.message || err.message;
      console.error('Error Message:', error.value);
  
      // Log the error response (if available)
      if (err.response) {
        console.error('Error Response:', err.response);
      }
    } finally {
      loading.value = false;
      //console.log('Fetching process completed.'); // Log end of the request
    }
  };


  const fetchDonationTrends = async (groupBy = 'month') => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/donation-trends', {
        params: { group_by: groupBy }
      });
      console.log("API Response:", response); // Debug API response
      donationTrends.value = response|| []; // Ensure it's always an array
    } catch (err) {
      error.value = err.response.message || err.message;
      console.error('Error fetching donation trends:', err);
    } finally {
      loading.value = false;
    }
  };

  const fetchFoodbankStats = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/foodbank-stats');
      //foodbankStats.value = response?.data;
      foodbankStats.value = response.data|| []; // Ensure data exists
      console.log("Store `foodbankStats` updated:", foodbankStats.value);
     
     // console.log("FoodbankStats Response:", response.data); // Debug API response
    } catch (err) {
      error.value = err.response?.data?.message || err.message;
      console.error('Error fetching foodbank stats:', err);
    } finally {
      loading.value = false;
    }
  };

 

  const fetchRecipientStats = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/recipient-stats');
      recipientStats.value = response.data;
      console.log("Full API Response of the recipient Stats:", response); // Log the entire response object
      console.log("Store method receiving the recipinet Stats from laravel Backend:", response.data); // Debug API response
    } catch (err) {
      error.value = err.response?.data?.message || err.message;
      console.error('Error fetching recipient stats:', err);
    } finally {
      loading.value = false;
    }
  };



  const fetchRecipientDemographics = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/recipient-demographics');
      recipientDemographics.value = response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message;
      console.error('Error fetching recipient demographics:', err);
    } finally {
      loading.value = false;
    }
  };
  const fetchDonorStats = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/donor-stats');
      donorStats.value = response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message;
      console.error('Error fetching donor stats:', err);
    } finally {
      loading.value = false;
    }
  };
  const fetchDonationTrendsByDay = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/donation-trends-by-day');
      donationTrendsByDay.value = response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message;
      console.error('Error fetching donation trends by day:', err);
    } finally {
      loading.value = false;
    }
  };

  const fetchFoodbankInformation = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/foodbank-information');
      foodbankInformation.value = response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message;
      console.error('Error fetching foodbank information:', err);
    } finally {
      loading.value = false;
    }
  };

  const fetchDonorInformation = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await service.get('/analytics-reports/donor-information');
      donorInformation.value = response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message;
      console.error('Error fetching donor information:', err);
    } finally {
      loading.value = false;
    }
  };

  // Reset store state
  const reset = () => {
    overview.value = null;
    donationTrends.value = [];
    foodbankStats.value = [];
    donorStats.value = [];
    recipientStats.value = [];
    donationTrendsByDay.value = [];
    recipientDemographics.value = [];
    foodbankInformation.value = [];
    donorInformation.value = [];
    loading.value = false;
    error.value = null;
  };

  return {
    // State
    overview,
    donationTrends,
    foodbankStats,
    donorStats,
    recipientStats,
    donationTrendsByDay,
    recipientDemographics,
    foodbankInformation,
    donorInformation,
    loading,
    error,

    // Actions
    fetchOverview,
    fetchDonationTrends,
    fetchFoodbankStats,
    fetchDonorStats,
    fetchRecipientStats,
    fetchDonationTrendsByDay,
    fetchRecipientDemographics,
    fetchFoodbankInformation,
    fetchDonorInformation,
    reset,
  };
});