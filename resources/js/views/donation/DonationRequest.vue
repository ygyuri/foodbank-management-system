<template>
  <div class="p-4 bg-white rounded-lg shadow-md">
    <!-- Page Header -->
    <h2 class="mb-4">Donation Requests</h2>

    <!-- Filters Section -->
    <el-row :gutter="20" class="mb-4" v-if="canViewFilters">
      <el-col :span="6">
        <el-select
          v-model="filters.status"
          placeholder="Filter by Status"
          clearable
          @change="handleFilterChange('status')"
        >
          <el-option label="All" value="" />
          <el-option label="Pending" value="pending" />
          <el-option label="Approved" value="approved" />
          <el-option label="Rejected" value="rejected" />
        </el-select>
      </el-col>
      <el-col :span="6">
        <el-select v-model="filters.type" placeholder="Filter by Type" clearable @change="handleFilterChange('type')">
          <el-option label="All" value="" />
          <el-option label="Food" value="food" />
          <el-option label="Supplies" value="supplies" />
          <el-option label="Monetary" value="monetary" />
        </el-select>
      </el-col>
      <el-col :span="6">
        <el-select
          v-model="filters.donorId"
          placeholder="Filter by Donor"
          clearable
          @change="handleFilterChange('donorId')"
        >
          <el-option label="All" value="" />
          <el-option v-for="donor in donors" :key="donor.id" :label="donor.name" :value="donor.id" />
        </el-select>
      </el-col>
      <el-col :span="6">
        <el-button type="primary" @click="fetchDonationRequests">Refresh</el-button>
      </el-col>
    </el-row>

    <div v-if="canCreateDonations">
      <el-button type="primary" @click="openCreateModal">Create Donation Request</el-button>
    </div>
    <!-- Donation Requests Table -->

    <div v-if="canViewDonations">
    <el-table :data="donationRequests" v-loading="loading" border style="width: 100%" @sort-change="handleSortChange">
      <el-table-column prop="id" label="ID" width="80" sortable="custom" />
      <el-table-column prop="donor.name" label="Donor Name" width="120" />
      <!-- Updated -->
      <el-table-column prop="type" label="Type" width="120" />
      <el-table-column prop="quantity" label="Quantity" width="120" />
      <el-table-column prop="description" label="Description" width="160" />
      <!-- New Column -->
      <el-table-column label="Created By" width="120">
        <template #default="{ row }">
          {{ row?.created_by?.name || 'N/A' }}
        </template>
      </el-table-column>

      <el-table-column prop="status" label="Status" width="120">
        <template #default="{ row }">
          <el-tag :type="getStatusTagType(row?.status || 'pending')">
            {{ row?.status || 'pending' }}
          </el-tag>
        </template>
      </el-table-column>
      <!-- <el-table-column prop="created_at" label="Created At" width="180" sortable="custom" /> -->
      <el-table-column label="Actions" width="200">
        <template #default="{ row }">
          <el-button v-if="canApproveDonations && row.status === 'pending'" type="success" size="small" @click="updateStatus(row.id, 'approved')">
            Approve
          </el-button>
          <el-button v-if="canRejectDonations && row.status === 'pending'" type="danger" size="small" @click="updateStatus(row.id, 'rejected')">
            Reject
          </el-button>
            <el-button v-if="canEdit(row)" type="primary" size="small" @click="openEditModal(row)">
              Edit
            </el-button>
            <el-button v-if="canDelete(row)" type="danger" size="small" @click="deleteDonation(row.id)">
              Delete
            </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- Pagination Controls -->
    <div class="mt-4 flex justify-end">
      <el-pagination
        background
        layout="total, sizes, prev, pager, next"
        :current-page="pagination.page"
        :page-size="pagination.pageSize"
        :total="pagination.total"
        @current-change="handlePageChange"
        @size-change="handlePageSizeChange"
      />
    </div>
  </div>
  
  <!-- Fallback Message if User Doesn't Have Permission -->
  <div v-else>
      <el-alert type="warning" :closable="false">
        You do not have permission to view the donation requests list.
      </el-alert>
    </div>
  </div>


  <!-- Form Modal -->
  <el-dialog
    :title="isEditMode ? 'Edit Donation Request' : 'Create Donation Request'"
    v-model="isModalVisible"
    width="500px"
  >
    <el-form :model="form" label-width="120px" :rules="formRules" ref="donationForm">
      <el-form-item label="Donor" prop="donor_id" required>
        <el-select v-model="form.donor_id" placeholder="Select Donor">
          <el-option v-for="donor in donors" :key="donor.id" :label="donor.name" :value="donor.id" />
        </el-select>
      </el-form-item>

      <el-form-item v-if="isAdmin" label="Foodbank" prop="foodbank_id" required>
        <el-select v-model="form.foodbank_id" placeholder="Select Foodbank">
          <el-option v-for="foodbank in foodbanks" :key="foodbank.id" :label="foodbank.name" :value="foodbank.id" />
        </el-select>
      </el-form-item>

      <el-form-item label="Type" prop="type" required>
        <el-select v-model="form.type" placeholder="Select Type">
          <el-option label="Food" value="food" />
          <el-option label="Supplies" value="supplies" />
          <el-option label="Monetary" value="monetary" />
        </el-select>
      </el-form-item>

      <el-form-item label="Quantity" prop="quantity" required>
        <el-input type="number" v-model="form.quantity" placeholder="Enter quantity" />
      </el-form-item>

      <el-form-item label="Description" prop="description">
        <!-- New Field -->
        <el-input type="textarea" v-model="form.description" placeholder="Enter description" />
      </el-form-item>

      <el-form-item label="Created By" prop="created_by">
        <!-- New Field -->
        <el-input v-model="form.created_by" :disabled="true" placeholder="Created By" />
      </el-form-item>

      <div class="dialog-footer">
        <el-button @click="isModalVisible = false">Cancel</el-button>
        <el-button type="primary" @click="handleSubmit">
          {{ isEditMode ? 'Update' : 'Create' }}
        </el-button>
      </div>
    </el-form>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, onUnmounted, watch, onMounted } from 'vue'
import { useDonationRequestStore } from '@/store/donationRequestStore'
import { ElMessage } from 'element-plus'
import { useRequestStore } from '@/store/requestStore'
import { userStore } from '@/store/user'
import { useRoute } from 'vue-router'
import { ability } from '@/utils/ability' // Import ability instance

const store = useDonationRequestStore()
const requestStore = useRequestStore() // Import requestStore
const user = userStore()
const route = useRoute()

const donors = ref([])
const foodbanks = ref([])
const isModalVisible = ref(false)
const isEditMode = ref(false)
const donationForm = ref(null) // Define the form reference

const form = reactive({
  id: null,
  donor_id: '',
  type: '',
  quantity: '',
  status: 'pending',
  foodbank_id: '',
  description: '', // New Field
  created_by: user.name // Auto-set creator's name
})

const formRules = computed(() => ({
  donor_id: [{ required: true, message: 'Donor is required', trigger: 'blur' }],
  type: [{ required: true, message: 'Type is required', trigger: 'change' }],
  quantity: [{ required: true, message: 'Quantity is required', trigger: 'blur' }],
  foodbank_id: isAdmin.value ? [{ required: true, message: 'Foodbank is required for admins', trigger: 'change' }] : []
}))

// Destructure and access state using computed properties
const donationRequests = computed(() => store.donationRequests)
//const donors = computed(() => store.donors);
const loading = computed(() => store.loading)
const filters = computed(() => store.filters)
const pagination = computed(() => store.pagination || { page: 1, pageSize: 10, total: 0 })

const sort = computed(() => store.sort)

const isAdmin = computed(() => user.roles.includes('admin'))


// ✅ CASL Computed Properties
const canViewFilters = computed(() => ability.can('view', 'filters'));
const canCreateDonations = computed(() => ability.can('create', 'donations'));
const canViewDonations = computed(() => ability.can('view', 'donations'));
const canApproveDonations = computed(() => ability.can('approve', 'donations'));
const canRejectDonations = computed(() => ability.can('reject', 'donations'));

const canEdit = (donation) => {
  const canEdit = ability.can('edit', 'donations') && (user.roles.includes('admin') || donation.donor_id === user.id);
  console.log(`Can Edit Donation ${donation.id}:`, canEdit);
  return canEdit;
};

const canDelete = (donation) => {
  const canDelete = ability.can('delete', 'donations') && (user.roles.includes('admin') || donation.donor_id === user.id);
  console.log(`Can Delete Donation ${donation.id}:`, canDelete);
  return canDelete;
};

// Watch for user role changes to update CASL abilities
watch(
  () => [user.roleList, user.permissionList],
  () => {
    ability.update(user.roleList, user.permissionList);
  },
  { deep: true }
);
// watch(() => form.foodbank_id, (newVal) => {
//     console.log("Selected Foodbank ID:", newVal);
// });

// Fetch data on mount
onMounted(async () => {
  await fetchData()
})

// Fetch data when route changes
watch(
  () => route.path,
  async () => {
    await fetchData()
  }
)

// Fetch data function
const fetchData = async () => {
  try {
    await store.fetchDonationRequests()
    await requestStore.fetchUsers({ role: 'donor', fetchAll: true })
    donors.value = requestStore.users

    if (isAdmin.value) {
      await requestStore.fetchUsers({ role: 'foodbank', fetchAll: true })
      foodbanks.value = requestStore.users
    }
  } catch (error) {
    console.error('Error fetching data:', error)
    ElMessage.error('Failed to fetch data')
  }
}

// Filter change handler
const handleFilterChange = (field) => {
  store.setFilter(field, filters.value[field])
}

// Sorting handler
const handleSortChange = ({ prop, order }) => {
  if (order) {
    store.setSort(prop)
  }
}

// Pagination handlers
const handlePageChange = (page) => {
  store.setPage(page)
}

const handlePageSizeChange = (size) => {
  store.setPageSize(size)
}

// Status update handler
const updateStatus = async (id, status) => {
  await store.updateDonationRequestStatus(id, status)
}

// Utility to get tag type for status
const getStatusTagType = (status) => {
  switch (status) {
    case 'pending':
      return 'warning'
    case 'approved':
      return 'success'
    case 'rejected':
      return 'danger'
    default:
      return ''
  }
}

const openCreateModal = () => {
  isEditMode.value = false
  resetForm()
  isModalVisible.value = true
}

watch([donors, foodbanks], ([newDonors, newFoodbanks]) => {
  console.log('Donors Loaded:', newDonors)
  console.log('Foodbanks Loaded:', newFoodbanks)
})

const openEditModal = (request) => {
  console.log('Request Data for Edit:', request) // 🟢 Log request data
  isEditMode.value = true
  form.id = request.id
  form.donor_id = request.donor?.id || '' // 🟢 Extract donor ID correctly
  form.foodbank_id = request.foodbank?.id || '' // 🟢 Extract foodbank ID correctly
  form.type = request.type
  form.quantity = request.quantity
  // form.status = request.status;
  form.description = request.description
  form.created_by = request.created_by?.name || '' // 🟢 Extract creator name if available
  console.log('Form Data in Edit Mode:', JSON.stringify(form, null, 2)) // 🟢 Log form data
  isModalVisible.value = true
}

const resetForm = () => {
  form.id = null
  form.donor_id = ''
  form.type = ''
  form.quantity = ''
  form.status = 'pending'
  form.foodbank_id = ''
  form.description = ''
}

const handleSubmit = async () => {
  console.log('Form Data Before Submit:', form) // 🟢 Log the form data
  try {
    if (await donationForm.value.validate()) {
      // ✅ Use donationForm instead of refs
      if (isEditMode.value) {
        await store.editDonationRequest(form)
      } else {
        await store.createDonationRequest(form)
      }
      isModalVisible.value = false
    }
  } catch (error) {
    console.error('Submit Error:', error)
  }
}

// Cleanup on unmount
onUnmounted(() => {
  console.log('Component unmounted: Cleaning up...')
  // Clear any intervals, timeouts, or subscriptions here if needed
})
</script>

<style scoped>
.el-table {
  max-width: 100%;
  overflow-x: auto;
}

.el-pagination {
  display: flex;
  justify-content: flex-end;
}

.el-form-item {
  margin-bottom: 0.5rem;
}

.el-button {
  margin-right: 0.5rem;
}

.dialog-footer {
  text-align: right;
}
.el-button + .el-button {
  margin-left: 10px;
}
</style>
