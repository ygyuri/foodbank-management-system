<template>
  <div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4">Donation Management</h2>

    <div v-if="canCreateDonation">
      <!-- Create Donation Button -->
      <el-button v-if="canCreateDonation" type="primary" icon="el-icon-plus" class="mb-4" @click="openCreateModal">
        Donor Create Donation
      </el-button>
    </div>

    <!-- Donations Table -->
    <el-table v-if="canViewTable" :data="filteredDonations || []" style="width: 100%" v-loading="loading">
      <el-table-column prop="id" label="ID" width="50" />
      <el-table-column label="Donor" width="150">
        <template #default="{ row }">{{ row?.donor_id || 'N/A' }}</template>
      </el-table-column>
      <!-- <el-table-column label="Foodbank" width="150">
          <template #default="{ row }">{{ getFoodbankName(row?.foodbank_id) || 'N/A' }}</template>
        </el-table-column> -->
      <!-- <el-table-column label="Recipient" width="150">
          <template #default="{ row }">{{ getRecipientName(row.recipient_id) }}</template>
        </el-table-column> -->
      <el-table-column label="Foodbank" width="150">
        <template #default="{ row }">
          <div v-if="!row.foodbank_id">
            <el-button type="primary" size="small" @click="toggleDropdown(row.id)">Assign Foodbank</el-button>
            <el-select
              v-if="dropdownVisible[row.id]"
              v-model="selectedFoodbanks[row.id]"
              placeholder="Select foodbank"
              size="small"
              @change="assignFoodbankToDonation(row.id)"
            >
              <el-option v-for="fb in foodbanks" :key="fb.id" :label="fb.name" :value="fb.id" />
            </el-select>
          </div>
          <div v-else>{{ getFoodbankName(row?.foodbank_id) || 'N/A' }}</div>
        </template>
      </el-table-column>
      <el-table-column prop="type" label="Type" width="120" />
      <el-table-column prop="quantity" label="Quantity" width="100" />
      <el-table-column prop="description" label="Description" width="250">
        <template #default="{ row }">{{ row?.description || 'N/A' }}</template>
      </el-table-column>

      <el-table-column prop="status" label="Status" width="120">
        <template #default="{ row }">
          <el-tag :type="row?.status === 'delivered' ? 'success' : 'warning'">
            {{ row?.status || 'Pending' }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Actions" width="300">
        <template #default="{ row }">
          <el-button v-if="canEdit" size="small" @click="openEditModal(row)">Edit</el-button>
          <el-button v-if="canDelete" size="small" type="danger" @click="deleteDonation(row.id)">Delete</el-button>
          <el-button v-if="isAdmin && canApproveOrReject" size="small" type="primary" @click="markAsCompleted(row.id)">
            Complete
          </el-button>

          <!-- Approve and Reject Buttons -->
          <el-button v-if="isAdmin && row.status === 'pending' && canApproveOrReject" size="small" type="success" @click="changeDonationStatus(row.id, 'approved')">
            Approve
          </el-button>
          <el-button v-if="isAdmin && row.status === 'pending' && canApproveOrReject" size="small" type="danger" @click="changeDonationStatus(row.id, 'rejected')">
            Reject
          </el-button>


        </template>
      </el-table-column>
    </el-table>
    <div v-else class="no-access">
      <el-alert title="You do not have access to view this table." type="warning" show-icon />
    </div>

    <el-pagination
      v-model:current-page="pagination.currentPage"
      :page-size="pagination.perPage"
      :total="totalDonations"
      @current-change="setPage"
      layout="prev, pager, next"
      class="mt-4"
    />
  </div>

  <!-- Create/Edit Modal -->

  <el-dialog v-model="isModalOpen" :title="isEditing ? 'Edit Donation' : 'Create Donation'">
    <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
      <el-form-item v-if="isAdmin" label="Donor" prop="donor_id">
        <el-select v-model="form.donor_id" placeholder="Select donor">
          <el-option v-for="d in donors" :key="d.id" :label="d.name" :value="d.id" />
        </el-select>
      </el-form-item>

      <el-form-item label="Foodbank" prop="foodbank_id">
        <el-select v-model="form.foodbank_id" placeholder="Select foodbank (optional)">
          <el-option v-for="fb in foodbanks" :key="fb.id" :label="fb.name" :value="fb.id" />
        </el-select>
      </el-form-item>

      <el-form-item label="Type" prop="type">
        <el-select v-model="form.type" placeholder="Select donation type">
          <el-option label="Food" value="food" />
          <el-option label="Clothing" value="clothing" />
          <el-option label="Money" value="money" />
        </el-select>
      </el-form-item>

      <el-form-item label="Quantity" prop="quantity">
        <el-input v-model="form.quantity" type="number" placeholder="Enter quantity" />
      </el-form-item>
      <el-form-item label="Description" prop="description">
        <el-input v-model="form.description" type="textarea" placeholder="Enter a description (optional)" />
      </el-form-item>

      <el-form-item>
        <el-button @click="isModalOpen = false">Cancel</el-button>
        <el-button type="primary" @click="handleSave">Save</el-button>
      </el-form-item>
    </el-form>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted, computed, onBeforeUnmount, nextTick } from 'vue'
import { useDonationStore } from '@/store/donationStore'
import { useRequestStore } from '@/store/requestStore'
import { userStore } from '@/store/user'
import { ElMessage } from 'element-plus'
import { ability } from '@/utils/ability';

const donationStore = useDonationStore()
const requestStore = useRequestStore()
const user = userStore()
const controller = new AbortController()
const signal = controller.signal

const isModalOpen = ref(false)
const isEditing = ref(false)
const form = ref({
  donor_id: '',
  foodbank_id: '',
  recipient_id: '',
  type: '',
  quantity: '',
  description: '',
  status: 'pending'
})
const formRef = ref(null)
const foodbanks = ref([])
const donors = ref([])
const recipients = ref([])
const selectedFoodbanks = ref({})
const dropdownVisible = ref({}) // Track visibility of dropdowns
const pagination = computed(() => donationStore.pagination)
const isAdmin = computed(() => user.roles.includes('admin'))
const isDonor = computed(() => user.roles.includes('donor'))

const donations = computed(() => donationStore.donations)
const totalDonations = computed(() => donationStore.totalDonations)
const loading = computed(() => donationStore.loading)

// Access Control
const canCreateDonation = computed(() => {
  const canCreate = isAdmin.value || isDonor.value;
  console.log("Can Create Donation:", canCreate);
  return canCreate;
});

const canViewTable = computed(() => {
  const canView = isAdmin.value || isDonor.value || isFoodbank.value;
  console.log("Can View Table:", canView);
  return canView;
});

const filteredDonations = computed(() => {
  if (isAdmin.value) {
    return donationStore.donations;
  } else if (isDonor.value) {
    return donationStore.donations.filter(donation => donation.donor_id === user.id);
  }
  return [];
});


const canEdit = (donation) => {
  const canEdit = isAdmin.value || (isDonor.value && donation.donor_id === user.id);
  console.log(`Can Edit Donation ${donation.id}:`, canEdit);
  return canEdit;
};

const canDelete = (donation) => {
  const canDelete = isAdmin.value || (isDonor.value && donation.donor_id === user.id);
  console.log(`Can Delete Donation ${donation.id}:`, canDelete);
  return canDelete;
};

const canApproveOrReject = computed(() => {
  const canApproveReject = isAdmin.value || (isFoodbank.value && canApproveDonations.value);
  console.log("Can Approve or Reject:", canApproveReject);
  return canApproveReject;
});

const fetchDonations = donationStore.fetchDonations
const deleteDonation = donationStore.deleteDonation
const markAsCompleted = donationStore.markAsCompleted
const assignRecipient = donationStore.assignRecipient

const rules = {
  donor_id: [{ required: true, message: 'Donor ID is required', trigger: 'blur' }],
  foodbank_id: [{ required: false, message: 'Foodbank is required', trigger: 'change' }],
  recipient_id: [{ required: true, message: 'Recipient is required', trigger: 'change' }],
  type: [{ required: true, message: 'Type is required', trigger: 'blur' }],
  quantity: [{ required: true, message: 'Quantity is required', trigger: 'blur' }],
  description: [{ max: 500, message: 'Description cannot exceed 500 characters', trigger: 'blur' }],
  status: [{ required: true, message: 'Status is required', trigger: 'change' }]
}

// Open modal for creating a new donation
const openCreateModal = () => {
  isEditing.value = false
  isModalOpen.value = true
  resetForm()
}

// Open modal for editing an existing donation
const openEditModal = (donation) => {
  isEditing.value = true
  isModalOpen.value = true
  Object.assign(form.value, donation)
}

// Reset the form
const resetForm = () => {
  form.value = {
    id: null,
    donor_id: '',
    foodbank_id: '',
    recipient_id: '',
    type: '',
    quantity: '',
    description: '',
    status: 'pending'
  }
  formRef.value?.clearValidate()
}

// Handle form save

const handleSave = () => {
  formRef.value.validate(async (valid) => {
    if (valid) {
      if (isEditing.value) {
        await donationStore.updateDonation(form.value.id, form.value)
      } else {
        await donationStore.createDonation(form.value)
      }

      // Fetch the updated donation list
      await donationStore.fetchDonations()

      isModalOpen.value = false
      resetForm()
    } else {
      ElMessage.error('Please fix validation errors.')
    }
  })
}

// Fetch users based on role
const fetchUsersByRole = async (role) => {
  await requestStore.fetchUsers({ role, fetchAll: true })
  return requestStore.users || []
}

// Utility functions for names
const getRecipientName = (recipientId) => {
  const recipient = recipients.value.find((r) => r.id === recipientId)
  return recipient ? recipient.name : 'N/A'
}

const getFoodbankName = (foodbankId) => {
  const foodbank = foodbanks.value.find((fb) => fb.id === foodbankId)
  return foodbank ? foodbank.name : 'N/A'
}

// Pagination
const setPage = (page) => {
  donationStore.pagination.currentPage = page
  donationStore.fetchDonations()
}
// Toggle dropdown visibility
const toggleDropdown = (id) => {
  dropdownVisible.value = {
    ...dropdownVisible.value,
    [id]: !dropdownVisible.value[id]
  }
}

const assignFoodbankToDonation = async (donationId) => {
  const foodbankId = selectedFoodbanks.value[donationId]
  if (foodbankId) {
    await donationStore.assignFoodbank(donationId, foodbankId)
  } else {
    ElMessage.warning('Please select a foodbank.')
  }
}

const changeDonationStatus = async (donationId, newStatus) => {
  console.log(`Changing status for Donation ID: ${donationId} to: ${newStatus}`)
  try {
    const result = await donationStore.updateDonationStatus(donationId, newStatus)
    console.log('Status update result:', result)
    ElMessage.success(`Donation ${newStatus} successfully!`)
    fetchDonations() // Refresh the table after status update
  } catch (error) {
    console.error('Error updating donation status:', error)
    ElMessage.error('Failed to update donation status.')
  }
}

// Cleanup function to prevent memory leaks
const cleanup = () => {
  isModalOpen.value = false
  isEditing.value = false
  resetForm()
}

// Fetch initial data with error handling
onMounted(async () => {
  console.log('Component mounted: Fetching initial data...')
  try {
    await user.getInfo({ signal })
    console.log('Fetched user info:', user.$state)

    await donationStore.fetchDonations({ signal })
    await nextTick()
    console.log('Donations after fetch:', donations.value)

    console.log('Fetching foodbank users...')
    foodbanks.value = await fetchUsersByRole('foodbank')
    console.log('Fetched foodbanks:', foodbanks.value)

    if (isAdmin.value) {
      console.log('Fetching donor users for admin...')
      donors.value = await fetchUsersByRole('donor')
      console.log('Fetched donors:', donors.value)
    }
  } catch (error) {
    console.error('Error during onMounted:', error)
    ElMessage.error('Failed to fetch initial data.')
  }
})

// Cleanup on unmount to prevent memory leaks
onBeforeUnmount(() => {
  donationStore.$reset()
  requestStore.$reset()
  userStore.$reset()
  controller.abort()
  cleanup()
})
</script>

<style scoped>
.p-6 {
  max-width: 1200px;
  margin: 0 auto;
}
.el-table th,
.el-table td {
  text-align: center;
}
.el-tag {
  font-size: 12px;
}
.no-access {
  text-align: center;
  padding: 20px;
}
</style>
