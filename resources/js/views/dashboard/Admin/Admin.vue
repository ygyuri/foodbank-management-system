<template>
  <div class="dashboard-editor-container">
    <!-- Panel Group -->
    <PanelGroup class="panel-group-section" />

    <!-- Date Range Picker and Line Chart -->
    <el-row class="chart-section">
      <el-col :xs="24" :sm="24" :lg="24">
        <div class="line-chart-container">
          <LineChart :chart-data="chartData" />
        </div>
      </el-col>
    </el-row>

    <!-- Foodbank Stats Chart -->
    <el-row class="foodbank-stats-section">
      <el-col :xs="24" :sm="24" :lg="24">
        <div v-if="store.error" class="error-message">
          Error: {{ store.error }}
        </div>
        <FoodbankStatsChart 
          v-if="!store.loading && foodbankStats.length"
          :data="foodbankStats"
        />
        <div v-if="store.loading" class="loading-overlay">
          <div class="loader"></div>
          <p>Loading foodbank statistics...</p>
        </div>
      </el-col>
    </el-row>

    <!-- Radar Chart -->
    <el-row class="radar-chart-section">
      <el-col :xs="24" :sm="24" :lg="24">
        <div class="chart-wrapper">
          <RaddarRecipientChart :recipientStats="recipientStats" />
        </div>
      </el-col>
    </el-row>
  </div>
</template>
<script setup>
import { toRefs,computed, onMounted, watch, reactive } from 'vue'

import PanelGroup from './components/PanelGroup.vue'
import LineChart from './components/LineChart.vue'
import FoodbankStatsChart from './components/FoodbankStatsChart.vue'

import RaddarRecipientChart from './components/RaddarRecipientChart.vue'

import { useAnalyticsReportStore } from '@/store/analyticsReportStore';


const store = useAnalyticsReportStore();

// Access foodbank stats from store
const foodbankStats = computed(() => store.foodbankStats);



// Access recipient stats from store
const recipientStats = computed(() => {
  console.log("Computed Recipient Stats:", store.recipientStats ?? []);
  return store.recipientStats ?? [];
});



// Transform data into format required by LineChart
// ✅ Always return a default object to prevent undefined issues
const chartData = computed(() => {
  console.log("Computed `store.donationTrends`:", store.donationTrends);

  const trendsData = store.donationTrends?.data ?? [];

  if (!Array.isArray(trendsData)) {
    console.error("Error: store.donationTrends.data is not an array", trendsData);
    return { series: [], categories: [] }; // Default structure
  }

  // Transform data for ApexCharts
  const categories = trendsData.map(item => item.month ?? item.date ?? "N/A"); // x-axis labels
  const series = [
    {
      name: 'Total Donations',
      data: trendsData.map(item => item.total_count ?? 0),
    },
    {
      name: 'Approved Donations',
      data: trendsData.map(item => item.approved_count ?? 0),
    },
    {
      name: 'Pending Donations',
      data: trendsData.map(item => item.pending_count ?? 0),
    },
    {
      name: 'Rejected Donations',
      data: trendsData.map(item => item.rejected_count ?? 0),
    },
  ];

  return { series, categories };
});


// ✅ Ensure `lineChartData` references existing data
const lineChart = {
  totalDonations: {
    expectedData: [67, 70, 75, 80, 85, 90, 95],
    actualData: [65, 68, 72, 78, 82, 88, 92],
  }
};

const state = reactive({
  lineChartData: lineChart?.totalDonations ?? { expectedData: [], actualData: [] }
});

// Destructure inside `setup()` scope (instead of using `let`)
const lineChartData = toRefs(state).lineChartData;



watch(foodbankStats, (newValue) => {
  console.log("Updated `foodbankStats` in Admin.vue:", newValue);
}, { deep: true });

// Fetch data on component mount
onMounted(async () => {
  console.log("Fetching Donation Trends...");
  await store.fetchDonationTrends();
  console.log("Donation Trends Fetched:", store.donationTrends);
  await store.fetchFoodbankStats();
  await store.fetchRecipientStats();

});

</script>

<style lang="scss" scoped>
.dashboard-editor-container {
  padding: 1.5rem;
  background-color: rgb(240, 242, 245);
  position: relative;

  :root {
    --primary-color: #409eff;
    --border-radius: 8px;
    --box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
    --transition-speed: 0.3s;
  }

  /* Panel Group Section */
  .panel-group-section {
    margin-bottom: 1.5rem;
  }

  /* Date Range Picker and Line Chart Section */
  .chart-section {
    background: #fff;
    padding: 1rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
  }

  .line-chart-container {
    width: 100%;
    height: 100%;
    background: #fff;
    border-radius: var(--border-radius);
  }

  /* Foodbank Stats Section */
  .foodbank-stats-section {
    background: #fff;
    padding: 1rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
  }

  /* Radar Chart Section */
  .radar-chart-section {
    background: #fff;
    padding: 1rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
  }

  .chart-wrapper {
    min-height: 300px; /* Ensures the div is visible */
    background: #fff;
    padding: 1rem;
    display: block !important;
    visibility: visible !important;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: box-shadow var(--transition-speed) ease;
  }

  .chart-wrapper:hover {
    box-shadow: 0 4px 16px 0 rgba(0, 0, 0, 0.2);
  }

  /* Loading Overlay */
  .loading-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
  }

  .loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  /* Error Message */
  .error-message {
    color: #dc3545;
    padding: 1rem;
    margin: 1rem 0;
    border: 1px solid #dc3545;
    border-radius: 4px;
  }
}

/* Responsive Design */
@media (max-width: 1024px) {
  .dashboard-editor-container {
    padding: 1rem;
  }

  .chart-wrapper {
    padding: 0.5rem;
  }
}

@media (max-width: 768px) {
  .dashboard-editor-container {
    padding: 0.5rem;
  }

  .chart-section,
  .foodbank-stats-section,
  .radar-chart-section {
    padding: 0.5rem;
  }

  .chart-wrapper {
    min-height: 250px;
  }
}
</style>