<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReportStore } from '@/store/reportStore';
import { ElTable, ElTableColumn, ElInput, ElButton, ElPagination, ElMessage, ElSelect, ElOption } from 'element-plus';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import dayjs from 'dayjs';

// Replace the static import with a dynamic import
let parse;
(async () => {
  const json2csv = await import('json2csv');
  parse = json2csv.parse;
})();

// Initialize the store
const reportStore = useReportStore();

// Local state
const searchId = ref(''); // For filtering by ID
const selectedFoodbank = ref(null); // Selected foodbank for filtering
const currentPage = ref(1); // Current page for pagination
const pageSize = ref(10); // Number of items per page
const sortKey = ref('name'); // Default sort key
const sortOrder = ref('asc'); // Default sort order

// Fetch data on component mount
const fetchFoodbankActivities = async () => {
  try {
    await reportStore.fetchFoodbankActivityReport(selectedFoodbank.value);
  } catch (error) {
    ElMessage.error('Failed to fetch foodbank activities. Please try again.');
  }
};

// Filtered and sorted data
const filteredData = computed(() => {
  let data = reportStore.foodbankActivities;

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

// Export to PDF
const exportToPdf = () => {
  const doc = new jsPDF();
  doc.text('Foodbank Activity Report', 14, 10);

  const tableData = reportStore.foodbankActivities.map((foodbank) => ({
    ID: foodbank.id,
    Name: foodbank.name,
    Email: foodbank.email,
    Donations: foodbank.donations.length,
    'Donation Requests': foodbank.donationRequests.length,
    'Recipient Requests': foodbank.requestsFb.length,
  }));

  autoTable(doc, {
    head: [['ID', 'Name', 'Email', 'Donations', 'Donation Requests', 'Recipient Requests']],
    body: tableData.map((row) => Object.values(row)),
    styles: { fontSize: 10, halign: 'center' },
    margin: { top: 20 },
  });

  doc.save('FoodbankActivityReport.pdf');
};
// Export to CSV
const exportToCsv = async () => {
  try {
    // Dynamically import json2csv
    const { parse } = await import('json2csv');

    // Prepare the CSV data
    const csvData = reportStore.foodbankActivities.map((foodbank) => ({
      ID: foodbank.id,
      Name: foodbank.name,
      Email: foodbank.email,
      Donations: foodbank.donations.length,
      'Donation Requests': foodbank.donationRequests.length,
      'Recipient Requests': foodbank.requestsFb.length,
    }));

    // Generate the CSV string
    const csv = parse(csvData);

    // Create a Blob and trigger the download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'FoodbankActivityReport.csv';
    link.click();
  } catch (error) {
    console.error('Error exporting to CSV:', error);
    ElMessage.error('Failed to export to CSV. Please try again.');
  }
};
// Fetch data on mount
onMounted(fetchFoodbankActivities);
</script>

<template>
  <div class="foodbank-activity-report">
    <h2>Foodbank Activity Report</h2>

    <!-- Search and Actions -->
    <div class="actions">
      <el-select
        v-model="selectedFoodbank"
        placeholder="Select Foodbank"
        clearable
        class="filter-dropdown"
        @change="fetchFoodbankActivities"
      >
        <el-option
          v-for="foodbank in reportStore.foodbankActivities"
          :key="foodbank.id"
          :label="foodbank.name"
          :value="foodbank.id"
        />
      </el-select>
      <el-input
        v-model="searchId"
        placeholder="Filter by Foodbank ID"
        clearable
        class="filter-input"
      />
      <el-button type="primary" @click="fetchFoodbankActivities">Refresh</el-button>
    </div>

    <!-- Export Buttons -->
    <div class="export-buttons">
      <el-button type="primary" @click="exportToPdf">Export to PDF</el-button>
      <el-button type="success" @click="exportToCsv">Export to CSV</el-button>
    </div>

    <!-- Table -->
    <div class="table-container">
      <el-table
        :data="paginatedData"
        style="width: 100%"
        v-loading="reportStore.foodbankLoading"
        :default-sort="{ prop: sortKey, order: sortOrder === 'asc' ? 'ascending' : 'descending' }"
        border
        height="400"
        :scrollbar-always-on="true"
      >
        <el-table-column
          prop="id"
          label="ID"
          sortable
          width="10"
          @click.native="handleSort('id')"
        />
        <el-table-column
          prop="name"
          label="Name"
          sortable
          width="150"
          @click.native="handleSort('name')"
        />
        <el-table-column
          prop="email"
          label="Email"
          sortable
          width="200"
          @click.native="handleSort('email')"
        />
        <el-table-column
          label="Donations"
          width="400"
          v-slot="scope"
        >
          <ul class="styled-list">
            <li v-for="donation in scope.row.donations" :key="donation.id">
              <strong>Type:</strong> {{ donation.type }} <br />
              <strong>Quantity:</strong> {{ donation.quantity }} <br />
              <strong>Status:</strong> {{ donation.status }} <br />
              <strong>Donor:</strong> {{ donation.donor?.name || 'N/A' }} <br />
              <strong>Recipient:</strong> {{ donation.recipient?.name || 'N/A' }} <br />
              <strong>Created At:</strong> {{ dayjs(donation.created_at).format('MMMM D, YYYY h:mm A') }} <br />
              <strong>Updated At:</strong> {{ dayjs(donation.updated_at).format('MMMM D, YYYY h:mm A') }}
            </li>
          </ul>
        </el-table-column>
        <el-table-column
          label="Donation Requests"
          width="400"
          v-slot="scope"
        >
          <ul class="styled-list">
            <li v-for="request in scope.row.donationRequests" :key="request.id">
              <strong>Type:</strong> {{ request.type }} <br />
              <strong>Quantity:</strong> {{ request.quantity }} <br />
              <strong>Status:</strong> {{ request.status }} <br />
              <strong>Donor:</strong> {{ request.donor?.name || 'N/A' }} <br />
              <strong>Created By:</strong> {{ request.created_by?.name || 'N/A' }} <br />
              <strong>Created At:</strong> {{ dayjs(request.created_at).format('MMMM D, YYYY h:mm A') }} <br />
            <strong>Updated At:</strong> {{ dayjs(request.updated_at).format('MMMM D, YYYY h:mm A') }}
            </li>
          </ul>
        </el-table-column>

        <el-table-column
          label="Recipient Requests"
          width="400"
          v-slot="scope"
        >
          <ul>
            <li v-for="request in scope.row.requestsFb" :key="request.id">
              {{ request.type }} - {{ request.quantity }} ({{ request.status }})
              <br />
              Recipient: {{ request.recipient?.name || 'N/A' }}
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
.foodbank-activity-report {
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

.filter-dropdown {
  margin-right: 10px;
  width: 300px;
}

.filter-input {
  flex: 1;
  margin-right: 10px;
}

.export-buttons {
  margin-bottom: 20px;
  display: flex;
  gap: 10px;
}

.table-container {
  overflow-x: auto;
  margin-bottom: 20px;
}

.styled-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.styled-list li {
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

.styled-list li:last-child {
  border-bottom: none;
}

.pagination {
  margin-top: 20px;
  text-align: center;
}
</style>