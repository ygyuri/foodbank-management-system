<template>
  <div class="line-chart-container">
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
      <p>Loading donation trends...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-message">
      Error: {{ error }}
    </div>

    <!-- Chart Container -->
    <div v-if="!loading && !error && chartOptions" class="chart-wrapper">
      <apexchart
        type="line"
        height="100%"
        width="100%" 
        :options="chartOptions"
        :series="series"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

// Props
const props = defineProps({
  chartData: {
    type: Object,
    required: true,
  },
});

// Toggle for Dummy Data
const useDummyData = ref(false);

// Dummy Data
const dummyData = {
  series: [
    {
      name: 'Total Donations',
      data: [100, 120, 90, 140, 110, 130, 150],
    },
    {
      name: 'Approved Donations',
      data: [80, 90, 70, 100, 85, 95, 110],
    },
    {
      name: 'Pending Donations',
      data: [15, 20, 10, 25, 18, 22, 30],
    },
    {
      name: 'Rejected Donations',
      data: [5, 10, 10, 15, 7, 13, 10],
    },
  ],
  categories: ['2025-01', '2025-02', '2025-03', '2025-04', '2025-05', '2025-06', '2025-07'],
};

// Chart Data
const chartData = computed(() => {
  return useDummyData.value ? dummyData : props.chartData;
});

// Chart Options
const chartOptions = computed(() => {
  return {
    chart: {
      type: 'line',
      height: '100%',
      toolbar: {
        show: false,
      },
      zoom: {
        enabled: false,
      },
    },
    title: {
      text: 'Donation Trends Over Time',
      align: 'left',
      style: {
        fontSize: '16px',
        fontWeight: 'bold',
        color: '#333',
      },
    },
    xaxis: {
      categories: chartData.value.categories,
      labels: {
        style: {
          colors: '#6E6E6E',
          fontSize: '12px',
        },
      },
      title: {
        text: 'Month',
        style: {
          color: '#6E6E6E',
          fontSize: '14px',
        },
      },
    },
    yaxis: {
      title: {
        text: 'Number of Donations',
        style: {
          color: '#6E6E6E',
          fontSize: '14px',
        },
      },
      labels: {
        style: {
          colors: '#6E6E6E',
          fontSize: '12px',
        },
      },
    },
    stroke: {
      width: 3,
      curve: 'smooth',
    },
    markers: {
      size: 5,
      colors: '#fff',
      strokeColors: ['#4CAF50', '#FFC107', '#F44336', '#2196F3'],
      strokeWidth: 2,
    },
    tooltip: {
      enabled: true,
      shared: true,
      intersect: false,
      style: {
        fontSize: '12px',
      },
      x: {
        format: 'MMM yyyy',
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
    grid: {
      borderColor: '#e0e0e0',
      strokeDashArray: 4,
    },
    colors: ['#4CAF50', '#FFC107', '#F44336', '#2196F3'], // Green, Yellow, Red, Blue
  };
});

// Series Data
const series = computed(() => {
  return chartData.value.series;
});
</script>

<style scoped>
.line-chart-container {
  background: #fff;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.chart-wrapper {
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