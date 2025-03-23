<template>
  <div class="recipient-stats-chart">
    <!-- Toggle for Dummy Data -->
    <div class="dummy-data-toggle">
      <label>
        <input type="checkbox" v-model="useDummyData" />
        Use Dummy Data
      </label>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-overlay">
      <div class="loader"></div>
      <p>Loading recipient statistics...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-message">
      Error: {{ error }}
    </div>

    <!-- Chart Container -->
    <div v-if="!loading && !error && chartOptions" class="chart-container">
      <apexchart
        type="bar"
        height="400"
        :options="chartOptions"
        :series="series"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useAnalyticsReportStore } from '@/store/analyticsReportStore';

// Props
const props = defineProps({
  recipientStats: {
    type: Array,
    required: true,
  },
});

// Store
const store = useAnalyticsReportStore();

// Reactive State
const loading = ref(false);
const error = ref(null);
const useDummyData = ref(false); // Toggle for dummy data

// Dummy Data
const dummyData = [
  {
    id: 1,
    name: 'Recipient 1',
    total_requests: 10,
    total_approved_requests: 7,
    total_rejected_requests: 2,
    total_pending_requests: 1,
    total_fulfilled_requests: 6,
  },
  {
    id: 2,
    name: 'Recipient 2',
    total_requests: 15,
    total_approved_requests: 10,
    total_rejected_requests: 3,
    total_pending_requests: 2,
    total_fulfilled_requests: 8,
  },
  {
    id: 3,
    name: 'Recipient 3',
    total_requests: 8,
    total_approved_requests: 5,
    total_rejected_requests: 1,
    total_pending_requests: 2,
    total_fulfilled_requests: 4,
  },
];

// Computed property to switch between real and dummy data
const stats = computed(() => {
  return useDummyData.value ? dummyData : props.recipientStats;
});

// Chart Data
const chartOptions = computed(() => {
  if (!stats.value.length) return null;

  // Extract labels (recipient names)
  const labels = stats.value.map((recipient) => recipient.name);

  return {
    chart: {
      type: 'bar',
      height: 400,
      toolbar: {
        show: false,
      },
    },
    title: {
      text: 'Recipient Statistics', // Add a title to the chart
      align: 'left',
      style: {
        fontSize: '16px',
        fontWeight: 'bold',
        color: '#333',
      },
    },
    xaxis: {
      categories: labels,
      labels: {
        style: {
          colors: ['#6E6E6E'],
          fontSize: '12px',
        },
      },
      title: {
        text: 'Recipients', // Add a title for the x-axis
        style: {
          color: '#6E6E6E',
          fontSize: '14px',
        },
      },
    },
    yaxis: {
      title: {
        text: 'Number of Requests', // Add a title for the y-axis
        style: {
          color: '#6E6E6E',
          fontSize: '14px',
        },
      },
      labels: {
        style: {
          colors: ['#6E6E6E'],
          fontSize: '12px',
        },
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '70%',
        endingShape: 'rounded',
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: true,
      width: 2,
      colors: ['transparent'],
    },
    fill: {
      opacity: 1,
    },
    tooltip: {
      enabled: true,
      shared: true,
      intersect: false,
      style: {
        fontSize: '12px',
      },
    },
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '14px',
      labels: {
        colors: '#6E6E6E',
      },
      markers: {
        width: 12,
        height: 12,
        radius: 12,
      },
    },
    colors: ['#4CAF50', '#FFC107', '#F44336', '#2196F3', '#9C27B0'], // Custom colors for bars
  };
});

const series = computed(() => {
  if (!stats.value.length) return [];

  return [
    {
      name: 'Total Requests',
      data: stats.value.map((recipient) => recipient.total_requests),
    },
    {
      name: 'Approved Requests',
      data: stats.value.map((recipient) => recipient.total_approved_requests),
    },
    {
      name: 'Rejected Requests',
      data: stats.value.map((recipient) => recipient.total_rejected_requests),
    },
    {
      name: 'Pending Requests',
      data: stats.value.map((recipient) => recipient.total_pending_requests),
    },
    {
      name: 'Fulfilled Requests',
      data: stats.value.map((recipient) => recipient.total_fulfilled_requests),
    },
  ];
});

// Watch for changes in recipientStats
watch(
  () => props.recipientStats,
  (newStats) => {
    if (newStats.length) {
      loading.value = false;
      error.value = null;
    }
  },
  { immediate: true }
);

// Debug: Check what the component receives
watch(
  () => stats.value,
  (newValue) => {
    console.log('Chart received stats:', newValue);
  },
  { deep: true }
);
</script>

<style scoped>
.recipient-stats-chart {
  background: #fff;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.chart-container {
  flex: 1;
  min-height: 400px;
}

.dummy-data-toggle {
  margin-bottom: 16px;
}

.dummy-data-toggle label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #666;
}

.loading-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
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

.error-message {
  color: #dc3545;
  padding: 1rem;
  margin: 1rem 0;
  border: 1px solid #dc3545;
  border-radius: 4px;
}
</style>