<template>
  <el-row :gutter="40" class="panel-group">
    <!-- Total Donations Card -->
    <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
      <div class="card-panel" @click="handleSetLineChartData('totalDonations')">
        <div class="card-panel-icon-wrapper icon-people">
          <el-icon class="card-panel-icon"><Money /></el-icon>
        </div>
        <div class="card-panel-description">
          <div class="card-panel-text">Total Donations</div>
          <span class="card-panel-num">{{ overview?.total_donations || 0 }}</span>
        </div>
      </div>
    </el-col>

    <!-- Total Foodbanks Card -->
    <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
      <div class="card-panel" @click="handleSetLineChartData('totalFoodbanks')">
        <div class="card-panel-icon-wrapper icon-message">
          <el-icon class="card-panel-icon"><Shop /></el-icon>
        </div>
        <div class="card-panel-description">
          <div class="card-panel-text">Total Foodbanks</div>
          <span class="card-panel-num">{{ overview?.total_foodbanks || 0 }}</span>
        </div>
      </div>
    </el-col>

    <!-- Total Donors Card -->
    <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
      <div class="card-panel" @click="handleSetLineChartData('totalDonors')">
        <div class="card-panel-icon-wrapper icon-money">
          <el-icon class="card-panel-icon"><User /></el-icon>
        </div>
        <div class="card-panel-description">
          <div class="card-panel-text">Total Donors</div>
          <span class="card-panel-num">{{ overview?.total_donors || 0 }}</span>
        </div>
      </div>
    </el-col>

    <!-- Total Recipients Card -->
    <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
      <div class="card-panel" @click="handleSetLineChartData('totalRecipients')">
        <div class="card-panel-icon-wrapper icon-shopping">
          <el-icon class="card-panel-icon"><UserFilled /></el-icon>
        </div>
        <div class="card-panel-description">
          <div class="card-panel-text">Total Recipients</div>
          <span class="card-panel-num">{{ overview?.total_recipients || 0 }}</span>
        </div>
      </div>
    </el-col>
  </el-row>
</template>
<script setup>
import { onMounted, ref } from 'vue';
import { useAnalyticsReportStore } from '@/store/analyticsReportStore'; // Import the Pinia store
import { ElIcon, ElAlert } from 'element-plus';
import { Loading } from '@element-plus/icons-vue';
import { Money, Shop, User, UserFilled } from '@element-plus/icons-vue'; // Import the icons

// const emit = defineEmits(['handleSetLineChartData']);

// Fetch data from the Pinia store
const analyticsReportStore = useAnalyticsReportStore();
const overview = ref({});
const loading = ref(false);
const error = ref(null);

// Fetch overview data when the component is mounted
onMounted(async () => {
  loading.value = true;
  error.value = null;

  try {
    await analyticsReportStore.fetchOverview();
    overview.value = analyticsReportStore.overview;
  //  console.log('Overview data in component:', overview.value); // Log the data in the component
  } catch (err) {
    error.value = err.message || 'Failed to fetch overview data';
    console.error('Error fetching overview data:', err);
  } finally {
    loading.value = false;
  }
});

// Emit event when a card is clicked
// Handle card click events
const handleSetLineChartData = (type) => {
  //console.log(`Selected Chart Data: ${type}`);
  // Perform the necessary state update or method call here
};
</script>

<style lang="scss" scoped>
.panel-group {
  margin-top: 18px;

  .card-panel-col {
    margin-bottom: 32px;
  }

  .card-panel {
    height: 108px;
    cursor: pointer;
    font-size: 12px;
    position: relative;
    overflow: hidden;
    color: #666;
    background: #fff;
    box-shadow: 4px 4px 40px rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.05);

    &:hover {
      .card-panel-icon-wrapper {
        color: #fff;
      }

      .icon-people {
        background: #40c9c6;
      }

      .icon-message {
        background: #36a3f7;
      }

      .icon-money {
        background: #f4516c;
      }

      .icon-shopping {
        background: #34bfa3;
      }
    }

    .icon-people {
      color: #40c9c6;
    }

    .icon-message {
      color: #36a3f7;
    }

    .icon-money {
      color: #f4516c;
    }

    .icon-shopping {
      color: #34bfa3;
    }

    .card-panel-icon-wrapper {
      float: left;
      margin: 14px 0 0 14px;
      padding: 16px;
      transition: all 0.38s ease-out;
      border-radius: 6px;
    }

    .card-panel-icon {
      float: left;
      font-size: 48px;
    }

    .card-panel-description {
      float: right;
      font-weight: bold;
      margin: 26px;
      margin-left: 0px;

      .card-panel-text {
        line-height: 18px;
        color: rgba(0, 0, 0, 0.45);
        font-size: 16px;
        margin-bottom: 12px;
      }

      .card-panel-num {
        font-size: 20px;
      }
    }
  }
}

.loading-overlay {
  display: flex;
  align-items: center;
  justify-content: center;
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.8);
  z-index: 1000;

  .loading-icon {
    margin-right: 8px;
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
}

.error-message {
  margin-top: 16px;
}

@media (max-width: 550px) {
  .card-panel-description {
    display: none;
  }

  .card-panel-icon-wrapper {
    float: none !important;
    width: 100%;
    height: 100%;
    margin: 0 !important;

    .svg-icon {
      display: block;
      margin: 14px auto !important;
      float: none !important;
    }
  }
}
</style>