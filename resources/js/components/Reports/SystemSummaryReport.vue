<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReportStore } from '@/store/reportStore';
import { ElTable, ElTableColumn, ElInput, ElButton, ElPagination, ElMessage } from 'element-plus';

// Initialize the store
const reportStore = useReportStore();

// Local state
const searchQuery = ref(''); // For filtering by name
const currentPage = ref(1); // Current page for pagination
const pageSize = ref(10); // Number of items per page
const sortKey = ref('type'); // Default sort key
const sortOrder = ref('asc'); // Default sort order

// Fetch data on component mount
const fetchSystemSummary = async () => {
  try {
    await reportStore.fetchSystemSummaryReport();
  } catch (error) {
    ElMessage.error('Failed to fetch system summary. Please try again.');
  }
};

// Combine all data into a single array for display
const combinedData = computed(() => {
  const donors = reportStore.systemSummary.donors?.map((donor) => ({
    type: 'Donor',
    name: donor.name,
    email: donor.email,
    donations: donor.donations.length,
    requests: donor.donationRequests.length,
  })) || [];

  const foodbanks = reportStore.systemSummary.foodbanks?.map((foodbank) => ({
    type: 'Foodbank',
    name: foodbank.name,
    email: foodbank.email,
    donations: foodbank.donations.length,
    requests: foodbank.donationRequests.length,
  })) || [];

  const recipients = reportStore.systemSummary.recipients?.map((recipient) => ({
    type: 'Recipient',
    name: recipient.name,
    email: recipient.email,
    donations: 0,
    requests: recipient.requestsFb.length,
  })) || [];

  return [...donors, ...foodbanks, ...recipients];
});

// Filtered and sorted data
const filteredData = computed(() => {
  let data = combinedData.value;

  // Apply filtering
  if (searchQuery.value) {
    data = data.filter((item) =>
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }

  // Apply sorting
  data = [...data].sort((a, b) => {
    const aValue = a[sortKey.value];
    const bValue = b[sortKey.value];
    if (sortOrder.value === 'asc') {
      return aValue > bValue ? 1 : -1;
    } else {
      return aValue < bValue ? 1 : -1;
    }
  });

  return data;
});

// Paginated data
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = currentPage.value * pageSize.value;
  return filteredData.value.slice(start, end);
});

// Handle sorting
const handleSort = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
};

// Fetch data on mount
onMounted(fetchSystemSummary);
</script>

<template>
  <div class="system-summary-report">
    <h2>System Summary Report</h2>

    <!-- Search and Actions -->
    <div class="actions">
      <el-input
        v-model="searchQuery"
        placeholder="Filter by Name"
        clearable
        class="filter-input"
      />
      <el-button type="primary" @click="fetchSystemSummary">Refresh</el-button>
    </div>

    <!-- Table -->
    <div class="table-container">
      <el-table
        :data="paginatedData"
        style="width: 100%"
        v-loading="reportStore.loading"
        :default-sort="{ prop: sortKey, order: sortOrder === 'asc' ? 'ascending' : 'descending' }"
        border
        height="400"
        :scrollbar-always-on="true"
      >
        <el-table-column
          prop="type"
          label="Type"
          sortable
          width="150"
          @click="handleSort('type')"
        />
        <el-table-column
          prop="name"
          label="Name"
          sortable
          width="200"
          @click="handleSort('name')"
        />
        <el-table-column
          prop="email"
          label="Email"
          sortable
          width="250"
          @click="handleSort('email')"
        />
        <el-table-column
          prop="donations"
          label="Donations"
          sortable
          width="150"
          @click="handleSort('donations')"
        />
        <el-table-column
          prop="requests"
          label="Requests"
          sortable
          width="150"
          @click="handleSort('requests')"
        />
      </el-table>
    </div>

    <!-- Pagination -->
    <el-pagination
      v-model:current-page="currentPage"
      :page-size="pageSize"
      :total="filteredData.length"
      layout="prev, pager, next"
      class="pagination"
    />
  </div>
</template>

<style scoped>
.system-summary-report {
  padding: 20px;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.actions {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
}

.filter-input {
  flex: 1;
  margin-right: 10px;
}

.table-container {
  overflow-x: auto; /* Enable horizontal scrolling */
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  text-align: center;
}
</style>