<template>
  <el-container>
    <!-- HEADER -->
    <el-header class="header">
      <h1>Recipient Requests Management</h1>
  
      <el-button v-if="canCreate" type="primary" @click="showCreateDialog = true">Create Request</el-button>
    </el-header>

    <el-main>
      <el-card v-if="canFilter" class="filters-card shadow-lg rounded-lg">
        <el-collapse v-model="filtersOpen">
          <el-collapse-item name="filters">
            <template #title>
              <span class="filters-title">Filter Requests</span>
            </template>
            <el-form
              inline
              class="filters-form max-h-80 overflow-y-auto px-4 py-2 bg-gray-50 rounded-md"
              label-width="120px"
            >
              <!-- Type Filter -->
              <el-form-item label="Type">
                <el-select
                  v-model="filters.type"
                  placeholder="Select Type"
                  @change="updateFilter('type', filters.type)"
                >
                  <el-option label="All" value=""></el-option>
                  <el-option label="Food" value="food"></el-option>
                  <el-option label="Supplies" value="supplies"></el-option>
                  <el-option label="Monetary" value="monetary"></el-option>
                </el-select>
              </el-form-item>

              <!-- Status Filter -->
              <el-form-item label="Status">
                <el-select
                  v-model="filters.status"
                  placeholder="Select Status"
                  @change="updateFilter('status', filters.status)"
                >
                  <el-option label="All" value=""></el-option>
                  <el-option label="Pending" value="pending"></el-option>
                  <el-option label="Approved" value="approved"></el-option>
                  <el-option label="Rejected" value="rejected"></el-option>
                </el-select>
              </el-form-item>

              <!-- Recipient ID Filter -->
              <el-form-item label="Recipient">
                <el-select
                  v-model="filters.recipient_id"
                  placeholder="Select Recipient"
                  @change="updateFilter('recipient_id', filters.recipient_id)"
                >
                  <el-option label="All" value=""></el-option>
                  <el-option
                    v-for="recipient in recipients"
                    :key="recipient.id"
                    :label="recipient.name"
                    :value="recipient.id"
                  ></el-option>
                </el-select>
              </el-form-item>

              <!-- Search Filter -->
              <el-form-item label="Search">
                <el-input
                  v-model="filters.search"
                  placeholder="Search by type or status"
                  @input="updateFilter('search', filters.search)"
                  clearable
                />
              </el-form-item>

              <!-- Sort By Filter -->
              <el-form-item label="Sort By">
                <el-select v-model="filters.sort_by" @change="updateFilter('sort_by', filters.sort_by)">
                  <el-option label="Type" value="type"></el-option>
                  <el-option label="Quantity" value="quantity"></el-option>
                  <el-option label="Status" value="status"></el-option>
                  <el-option label="Created At" value="created_at"></el-option>
                </el-select>
              </el-form-item>

              <!-- Sort Order Filter -->
              <el-form-item label="Order">
                <el-select v-model="filters.sort_order" @change="updateFilter('sort_order', filters.sort_order)">
                  <el-option label="Ascending" value="asc"></el-option>
                  <el-option label="Descending" value="desc"></el-option>
                </el-select>
              </el-form-item>
            </el-form>
          </el-collapse-item>
        </el-collapse>
      </el-card>

      <!-- SCROLLABLE TABLE CONTAINER -->
      <div v-if="canManageRequests" class="table-container">

        <el-table
          :data="requestStore.requests"
          v-loading="requestStore.loading"
          stripe
          style="width: 100%; overflow-x: auto"
        >
          <el-table-column prop="id" label="ID" width="80" @click="updateSorting('id')" sortable></el-table-column>
          <el-table-column prop="type" label="Type" @click="updateSorting('type')" sortable></el-table-column>
          <el-table-column
            prop="quantity"
            label="Quantity"
            @click="updateSorting('quantity')"
            sortable
          ></el-table-column>
          <el-table-column prop="status" label="Status" @click="updateSorting('status')" sortable></el-table-column>

          <!-- Recipient Name Column -->
          <el-table-column label="Recipient">
            <template #default="{ row }">
              <span>{{ getRecipientName(row.recipient_id) || 'N/A' }}</span>
            </template>
          </el-table-column>

          <!-- Foodbank Name Column -->
          <el-table-column label="Foodbank Request Made To">
            <template #default="{ row }">
              <span>{{ getFoodbankName(row.foodbank_id) || 'N/A' }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="300">
            <template #default="{ row }">
              <el-button v-if="canEdit" size="small" type="success" @click="editRequest(row)">Edit</el-button>
              <el-button v-if="canDelete" size="small" type="danger" @click="deleteRequest(row.id)">Delete</el-button>
              <el-dropdown v-if="canApproveReject">
                <el-button size="small" type="primary">
                  Approve/Reject
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item @click="updateStatus(row.id, 'approved')">Approve</el-dropdown-item>
                    <el-dropdown-item @click="updateStatus(row.id, 'rejected')">Reject</el-dropdown-item>
                    <el-dropdown-item @click="updateStatus(row.id, 'pending')">Set as Pending</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- If user doesn't have permission, show a message -->
<div v-else class="text-center text-gray-500">
  <p>You do not have permission to view recipient requests.</p>
</div>

      <!-- PAGINATION -->
      <el-pagination
        background
        layout="prev, pager, next, jumper, sizes, total"
        :total="requestStore.pagination.total"
        :page-size="requestStore.pagination.per_page"
        :current-page="requestStore.pagination.current_page"
        @size-change="updatePerPage"
        @current-change="updateCurrentPage"
      />
    </el-main>

    <!-- CREATE/EDIT DIALOG -->
    <!-- CREATE/EDIT DIALOG -->
    <el-dialog v-model="showCreateDialog" :title="isEditing ? 'Edit Request' : 'Create Request'">
      <el-form :model="form" label-width="120px">
        <!-- Recipient Selector (Only for Admin) -->
        <el-form-item v-if="isAdmin" label="Recipient">
          <el-select v-model="form.recipient_id" placeholder="Select Recipient">
            <el-option
              v-for="recipient in recipients"
              :key="recipient.id"
              :label="recipient.name"
              :value="recipient.id"
            ></el-option>
          </el-select>
        </el-form-item>
        <!-- Foodbank Selector -->
        <el-form-item label="Foodbank">
          <el-select v-model="form.foodbank_id" placeholder="Select Foodbank (Optional) If You Know Any!">
            <el-option :label="'None'" :value="null"></el-option>
            <el-option
              v-for="foodbank in foodbanks"
              :key="foodbank.id"
              :label="foodbank.name"
              :value="foodbank.id"
            ></el-option>
          </el-select>
        </el-form-item>

        <!-- Recipient ID (Hidden or Displayed Based on Role) -->
        <el-form-item v-if="isEditing" label="Recipient ID">
          <el-input v-model="form.recipient_id" disabled></el-input>
        </el-form-item>

        <!-- Type Selector -->
        <el-form-item label="Type">
          <el-select v-model="form.type" placeholder="Select Type">
            <el-option label="Food" value="food"></el-option>
            <el-option label="Supplies" value="supplies"></el-option>
            <el-option label="Monetary" value="monetary"></el-option>
          </el-select>
        </el-form-item>

        <!-- Quantity Input -->
        <el-form-item label="Quantity">
          <el-input-number v-model="form.quantity" :min="1"></el-input-number>
        </el-form-item>

        <!-- Status Selector (Only for Edit Mode) -->
        <el-form-item v-if="isEditing" label="Status">
          <el-select v-model="form.status" placeholder="Select Status" disabled>
            <el-option label="Pending" value="pending"></el-option>
            <el-option label="Approved" value="approved"></el-option>
            <el-option label="Rejected" value="rejected"></el-option>
          </el-select>
        </el-form-item>

        <!-- Submit Button -->
        <el-form-item>
          <el-button type="primary" @click="submitRequest">{{ isEditing ? 'Update' : 'Submit' }}</el-button>
        </el-form-item>
      </el-form>
    </el-dialog>
  </el-container>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRequestStore } from '@/store/requestStore'
import service from '@/utils/request'
import { userStore } from '@/store/user'

import { ability } from "@/utils/ability";

const requestStore = useRequestStore()
const user = userStore()
const showCreateDialog = ref(false)
const isEditing = ref(false)

// Check permissions
const canFilter = computed(() => {
  const result = ability.can("view", "filters");
  console.log("canFilter:", result);
  return result;
});

const canCreate = computed(() => {
  const result = ability.can("create", "recipientRequests");
  console.log("canCreate:", result);
  return result;
});

const canEdit = computed(() => {
  const result = ability.can("edit", "recipientRequests");
  console.log("canEdit:", result);
  return result;
});

const canDelete = computed(() => {
  const result = ability.can("delete", "recipientRequests");
  console.log("canDelete:", result);
  return result;
});

const canApproveReject = computed(() => {
  const canApprove = ability.can("approve", "recipientRequests");
  const canReject = ability.can("reject", "recipientRequests");
  
  const result = canApprove || canReject;
  console.log("canApproveReject:", result);
  return result;
});


const canManageRequests = computed(() => {
  const result = ability.can("manage", "recipientRequests");
  console.log("canManageRequests:", result);
  return result;
});






// Reactive state for filters, sorting, and pagination
const filters = computed({
  get: () => requestStore.filters,
  set: (value) => (requestStore.filters = value)
})
const filtersOpen = ref(['filters']) // Keep filters open by default

const pagination = reactive({
  current_page: 1,
  per_page: 10
})
const form = ref({ id: null, foodbank_id: null, type: '', quantity: 1 })
const foodbanks = ref([])
const recipients = ref([]) // Define recipients for admin
// Computed properties for user role checks
const isAdmin = computed(() => user.roles.includes('admin'))
const isRecipient = computed(() => user.roles.includes('recipient'))

//const isAdmin = computed(() => user.$state.role === 'admin');
const isFoodbank = computed(() => user.$state.role === 'foodbank');
const canEditOrApprove = computed(() => isAdmin.value || isFoodbank.value);


// Add these methods for pagination control
const updatePerPage = (size) => {
  requestStore.pagination.per_page = size
  requestStore.pagination.current_page = 1 // Reset to first page on per-page change
  requestStore.fetchRequests()
}

const updateCurrentPage = (page) => {
  requestStore.pagination.current_page = page
  requestStore.fetchRequests()
}

// Fetch user info and requests on component mount
onMounted(async () => {
  console.log('Component mounted: Fetching user info...')
  try {
    await user.getInfo() // Fetch user info and update store
    console.log('Fetched user info:', user.$state)

      // Debug user role and permissions
      console.log("User Role:", user.role);
    console.log("User Permissions:", ability.rules);


    console.log('Fetching requests...')
    await requestStore.fetchRequests() // Fetch requests
    console.log('Fetched requests:', requestStore.requests)

    await requestStore.fetchUsers({ role: 'foodbank', fetchAll: true })
    console.log('Fetched all foodbanks:', requestStore.users)
    foodbanks.value = requestStore.users

    // Fetch recipients only if the user is an admin
    if (isAdmin.value) {
      await requestStore.fetchUsers({ role: 'recipient', fetchAll: true })
      console.log('Fetched all recipients:', requestStore.users)
      recipients.value = requestStore.users
    }
  } catch (error) {
    console.error('Error during onMounted:', error)
  }
})

// Handle form submission for creating or updating requests
const submitRequest = async () => {
  try {
    if (!form.value.foodbank_id) {
      form.value.foodbank_id = null // Ensure null if no foodbank selected
    }

    // Auto-set recipient_id for recipient role
    if (isRecipient.value) {
      form.value.recipient_id = user.id // Use userStore for user ID
    }

    // Determine if it's an edit or create operation
    if (isEditing.value) {
      console.log('Updating request:', form.value)
      await requestStore.updateRequest(form.value) // Update request
    } else {
      console.log('Creating request:', form.value)
      await requestStore.createRequest(form.value) // Create new request
    }

    showCreateDialog.value = false // Close dialog
    await requestStore.fetchRequests() // Refresh request list
  } catch (error) {
    console.error('Error in submitRequest:', error)
    ElMessage.error(error.response?.data?.message || 'Failed to process request.')
  }
}

const editRequest = (request) => {
  isEditing.value = true
  form.value = { ...request }
  showCreateDialog.value = true
}

const deleteRequest = async (id) => {
  await requestStore.deleteRequest(id)
  fetchRequests()
}

const getRecipientName = (recipientId) => {
  const recipient = recipients.value.find((r) => r.id === recipientId)
  return recipient ? recipient.name : null // Return name if found, otherwise null
}

const getFoodbankName = (foodbankId) => {
  const foodbank = foodbanks.value.find((fb) => fb.id === foodbankId)
  return foodbank ? foodbank.name : null // Return name if found, otherwise null
}

const updateStatus = async (id, status) => {
  console.log('Updating status for ID:', id, 'to:', status) // Log here
  try {
    await requestStore.updateRequestStatus(id, status)
    ElMessage.success(`Request status updated to ${status}.`)
  } catch (error) {
    console.error('Error updating status:', error)
  }
}

// Fetch requests from the store
const fetchRequests = async () => {
  console.log('Current Filters:', filters.value)
  await requestStore.fetchRequests()
}

// Update filters and refetch data
const updateFilter = (key, value) => {
  console.log(`Filter changed: ${key} = ${value}`)
  filters[key] = value
  pagination.current_page = 1 // Reset to first page on filter change
  fetchRequests()
}

// Update sorting and refetch data
const updateSorting = (sortBy) => {
  if (filters.sort_by === sortBy) {
    filters.sort_order = filters.sort_order === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sort_by = sortBy
    filters.sort_order = 'asc'
  }
  fetchRequests()
}

watch(
  filters,
  (newFilters, oldFilters) => {
    console.log('Filters updated:', { newFilters, oldFilters })
  },
  { deep: true }
)
</script>

<style scoped>
.table-container {
  max-width: 100%;
  overflow-x: auto;
  margin-bottom: 10px;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px;
  background: #f5f5f5;
  border-bottom: 1px solid #ddd;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.filters-title {
  font-size: 14px;
  font-weight: 300;
  color: #333;
}

.filters-card {
  padding: 0; /* Remove default padding for a tighter look */
  margin-bottom: 10px;
  background-color: #fff;
  border: 1px solid #ebeef5;
  border-radius: 4px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  max-width: 100%;
}

.filters-form {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 6px; /* Reduced gap for compact layout */
}

.el-form-item label {
  font-weight: 500;
  color: #666;
}

.el-select,
.el-input {
  width: 100%;
}

.el-button {
  margin-right: 4px;
  background-color: #409eff;
  color: #fff;
  transition: background-color 0.3s ease;
}

.el-button:hover {
  background-color: #66b1ff;
}
</style>
