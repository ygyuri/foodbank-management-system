<template>
  <div class="chart-container">
    <div v-if="loading" class="loading-indicator">
      <div class="loader"></div>
      <span>Loading foodbank statistics...</span>
    </div>
    
    <div v-else-if="hasData" id="chart-wrapper">
      <apexchart
        type="bar"
        height="400"
        :options="chartOptions"
        :series="series"
      ></apexchart>
    </div>
    
    <div v-else class="no-data-message">
      No foodbank statistics available
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  data: {
    type: Array,
    required: true,
    default: () => []
  },
  loading: Boolean
});

const chart = ref(null);
const isMounted = ref(false);
const COLOR_PALETTE = ['#3B82F6', '#10B981']; // Tailwind-inspired blue and emerald

const hasData = computed(() => 
  props.data.length > 0 && 
  props.data.some(item => item.total_requests > 0 || item.total_donations > 0)
);

const chartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 400,
    toolbar: {
      show: true,
      tools: {
        download: true,
        selection: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: false,
        reset: true
      },
      export: {
        csv: { filename: 'foodbank-stats' },
        svg: { filename: 'foodbank-stats' },
        png: { filename: 'foodbank-stats' }
      }
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  title: {
    text: 'Foodbank Activity Overview',
    align: 'center',
    style: {
      fontSize: '20px',
      fontWeight: 600,
      color: '#1F2937'
    }
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '70%',
      borderRadius: 6,
      dataLabels: {
        position: 'top'
      }
    }
  },
  dataLabels: {
    enabled: true,
    formatter: (val) => val > 0 ? val : '',
    offsetY: -20,
    style: {
      fontSize: '12px',
      colors: ['#6B7280']
    }
  },
  xaxis: {
    categories: props.data.map(item => item.name),
    title: {
      text: 'Foodbank Organizations',
      style: {
        fontSize: '14px',
        fontWeight: 500,
        color: '#374151'
      }
    },
    labels: {
      style: {
        fontSize: '12px',
        colors: '#4B5563'
      },
      trim: true,
      hideOverlappingLabels: true
    },
    tickPlacement: 'on'
  },
  yaxis: {
    title: {
      text: 'Number of Requests/Donations',
      style: {
        fontSize: '14px',
        fontWeight: 500,
        color: '#374151'
      }
    },
    labels: {
      formatter: (val) => Math.floor(val) === val ? val : '',
      style: {
        colors: '#4B5563'
      }
    },
    min: 0,
    forceNiceScale: true
  },
  grid: {
    borderColor: '#E5E7EB',
    strokeDashArray: 4,
    padding: {
      top: 20,
      right: 20
    }
  },
  fill: {
    opacity: 0.9,
    type: 'solid'
  },
  colors: COLOR_PALETTE,
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    fontSize: '14px',
    itemMargin: {
      horizontal: 16
    },
    markers: {
      radius: 4
    }
  },
  tooltip: {
    shared: true,
    intersect: false,
    y: {
      formatter: (val) => `${val} transactions`
    },
    style: {
      fontSize: '14px'
    }
  },
  responsive: [{
    breakpoint: 768,
    options: {
      chart: {
        height: 300
      },
      dataLabels: {
        enabled: false
      },
      legend: {
        position: 'bottom',
        horizontalAlign: 'center'
      }
    }
  }]
}));

const series = computed(() => [
  {
    name: 'Total Requests',
    data: props.data.map(item => item.total_requests)
  },
  {
    name: 'Total Donations',
    data: props.data.map(item => item.total_donations)
  }
]);

// Chart initialization and lifecycle management
const initializeChart = () => {
  if (!isMounted.value || !hasData.value) return;
  
  chart.value = new ApexCharts(document.querySelector("#chart-wrapper"), {
    series: series.value,
    options: chartOptions.value
  });
  chart.value.render();
};

watch([series, chartOptions, hasData], () => {
  if (chart.value && hasData.value) {
    chart.value.updateOptions({
      series: series.value,
      ...chartOptions.value
    });
  }
});

onMounted(() => {
  isMounted.value = true;
  initializeChart();
});

onBeforeUnmount(() => {
  if (chart.value) {
    chart.value.destroy();
  }
});
</script>

<style scoped>
.chart-container {
  background-color: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  border: 1px solid #f3f4f6;
}

.loading-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 1rem;
  color: #4b5563;
}

.loader {
  width: 2rem;
  height: 2rem;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.no-data-message {
  padding: 1.5rem;
  text-align: center;
  color: #6b7280;
  background-color: #f9fafb;
  border-radius: 0.5rem;
}

#chart-wrapper {
  transition: opacity 0.3s;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* For the date range picker component */
.date-range-picker {
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: flex-end;
}

.group-by-select {
  width: 200px;
  transition: box-shadow 0.3s ease;
}

@media (max-width: 768px) {
  .date-range-picker {
    justify-content: center;
  }
  
  .group-by-select {
    width: 100%;
  }
}

.el-select:focus {
  border-color: #409eff;
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.2);
}

.el-option {
  padding: 8px 16px;
}

.el-option:hover {
  background-color: #f5f7fa;
}

.el-select-dropdown {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
</style>