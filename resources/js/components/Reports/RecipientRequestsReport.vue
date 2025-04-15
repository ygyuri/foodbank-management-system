<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReportStore } from '@/store/reportStore';
import { ElTable, ElTableColumn, ElInput, ElButton, ElPagination, ElMessage } from 'element-plus';

// Initialize the store
const reportStore = useReportStore();

// Local state
const searchId = ref(''); // For filtering by recipient ID
const currentPage = ref(1); // Current page for pagination
const pageSize = ref(10); // Number of items per page
const sortKey = ref('name'); // Default sort key
const sortOrder = ref('asc'); // Default sort order

// Fetch data on component mount
const fetchRecipientRequests = async () => {
  try {
    await reportStore.fetchRecipientRequestReport();
  } catch (error) {
    ElMessage.error('Failed to fetch recipient requests. Please try again.');
  }
};

// Filtered and sorted data
const filteredData = computed(() => {
  let data = reportStore.recipientRequests;

  // Filter by ID if searchId is provided
  if (searchId.value) {
    data = data.filter((item) => item.id.toString().includes(searchId.value));
  }

  // Sort data
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
onMounted(fetchRecipientRequests);
</script>

<template>
  <div class="recipient-request-report">
    <h2>Recipient Request Report</h2>

    <!-- Search and Actions -->
    <div class="actions">
      <el-input
        v-model="searchId"
        placeholder="Filter by Recipient ID"
        clearable
        class="filter-input"
      />
      <el-button type="primary" @click="fetchRecipientRequests">Refresh</el-button>
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
          prop="id"
          label="ID"
          sortable
          width="100"
          @click="handleSort('id')"
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
          prop="recipient_type"
          label="Recipient Type"
          sortable
          width="200"
          @click="handleSort('recipient_type')"
        />
        <el-table-column    
          label="Requests"
          width="400"
          v-slot="scope"
        >   
          <ul>
            <li v-for="request in scope.row.requests" :key="request.id">
              {{ request.type }} - {{ request.quantity }} ({{ request.status }})
              <br />
              Foodbank: {{ request.foodbank?.name || 'N/A' }}
              <br />
              Created At: {{ request.created_at }}
            </li>
          </ul>
        </el-table-column>
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
.recipient-request-report {
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