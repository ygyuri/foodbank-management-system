<template>
  <div>
    <h2>User Management</h2>

    <!-- Filter and Add User Card -->
    <el-card class="filter-card" v-if="canViewFilters">
      <el-row gutter="20" class="filter-container">
        <el-col :xs="24" :sm="12" :md="6" :lg="4">
          <el-input v-model="store.filters.name" placeholder="Search by name" clearable @clear="fetchUsers">c</el-input>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6" :lg="4">
          <el-input v-model="store.filters.email" placeholder="Search by email" clearable @clear="fetchUsers">
            <template #prefix>
              <Message />
            </template>
          </el-input>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6" :lg="4">
          <el-select v-model="store.filters.role" placeholder="Filter by role" clearable>
            <el-option label="Admin" value="admin" />
            <el-option label="User" value="user" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="24" :md="6" :lg="4" class="filter-actions">
          <el-space>
            <el-button type="primary" icon="el-icon-search" @click="fetchUsers">Search</el-button>
            <el-button type="success" icon="el-icon-plus" @click="openCreateModal" v-if="canCreateUser">Add User</el-button>
          </el-space>
        </el-col>
      </el-row>
    </el-card>

    <el-card class="table-container" v-if="canViewUsers">
      <el-table :data="paginatedUsers" stripe style="width: 100%" v-loading="loading" height="400">
        <el-table-column prop="name" label="Name" sortable />
        <el-table-column prop="email" label="Email" sortable />
        <el-table-column prop="phone" label="Phone" sortable />
        <el-table-column prop="location" label="Location" sortable />
        <el-table-column prop="address" label="Address" sortable />
        <el-table-column label="Role">
          <template #default="{ row }">
            <el-tag type="info">{{ row.roles.length ? row.roles.join(', ') : 'No Role' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sex" label="Sex" sortable />
        <el-table-column label="Status">
          <template #default="{ row }">
            <el-tag :type="row.status === 'approved' ? 'success' : row.status === 'rejected' ? 'danger' : 'warning'">
              {{ row.status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <el-button size="small" type="primary" icon="el-icon-edit" @click="openEditModal(row)" v-if="canViewUsers">Edit</el-button>
            <el-button size="small" type="danger" icon="el-icon-delete" @click="deleteUser(row)" v-if="canViewUsers">Delete</el-button>
            <el-dropdown >
              <el-button size="small" type="info" icon="el-icon-arrow-down" v-if="canViewUsers">Change Status</el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="updateStatus(row.id, 'approved')">Approve</el-dropdown-item>
                  <el-dropdown-item @click="updateStatus(row.id, 'rejected')">Reject</el-dropdown-item>
                  <el-dropdown-item @click="updateStatus(row.id, 'pending')">Reset to Pending</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>

      <!-- Move Pagination Inside Table Container -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="store.pagination.current_page"
          v-model:page-size="store.pagination.per_page"
          :total="store.pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

     <!-- Fallback Message if User Doesn't Have Permission -->
     <el-alert type="warning" :closable="false" v-else>
      You do not have permission to view the user list.
    </el-alert>

    <!-- Create/Edit User Modal -->
    <el-dialog
      v-model="isModalVisible"
      :title="isEditing ? 'Edit User' : 'Create User'"
      width="50%"
      :close-on-click-modal="false"
      @closed="resetForm"
    >
      <el-form ref="userForm" :model="user" :rules="rules" label-width="120px">
        <el-form-item label="Name" prop="name">
          <el-input v-model="user.name" />
        </el-form-item>

        <el-form-item label="Email" prop="email">
          <el-input v-model="user.email" type="email" />
        </el-form-item>

        <el-form-item label="Password" prop="password" v-if="!isEditing">
          <el-input v-model="user.password" type="password" />
        </el-form-item>

        <el-form-item label="Confirm Password" prop="password_confirmation" v-if="!isEditing">
          <el-input v-model="user.password_confirmation" type="password" />
        </el-form-item>

        <el-form-item label="Sex" prop="sex">
          <el-select v-model="user.sex">
            <el-option label="Male" :value="0" />
            <el-option label="Female" :value="1" />
          </el-select>
        </el-form-item>

        <el-form-item label="Birthday" prop="birthday">
          <el-date-picker v-model="user.birthday" type="date" format="YYYY-MM-DD" />
        </el-form-item>

        <el-form-item label="Phone" prop="phone">
          <el-input v-model="user.phone" />
        </el-form-item>

        <el-form-item label="Role" prop="role">
          <el-select v-model="user.role" placeholder="Select a role">
            <el-option v-for="role in roles" :key="role.id" :label="role.name" :value="role.id" />
          </el-select>
        </el-form-item>

        <el-form-item label="Location" prop="location">
          <el-input v-model="user.location" />
        </el-form-item>

        <el-form-item label="Address" prop="address">
          <el-input v-model="user.address" />
        </el-form-item>

        <el-form-item label="Organization Name" prop="organization_name">
          <el-input v-model="user.organization_name" />
        </el-form-item>

        <el-form-item label="Recipient Type" prop="recipient_type">
          <el-select v-model="user.recipient_type" placeholder="Select recipient type">
            <el-option label="Individual" value="individual"></el-option>
            <el-option label="Organization" value="organization"></el-option>
          </el-select>
        </el-form-item>

        <el-form-item label="Donor Type" prop="donor_type">
          <el-input v-model="user.donor_type" />
        </el-form-item>

        <el-form-item label="Notes" prop="notes">
          <el-input v-model="user.notes" type="textarea" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="isModalVisible = false">Cancel</el-button>
        <el-button type="primary" :loading="loading" @click="submitForm">
          {{ isEditing ? 'Update' : 'Create' }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
// import UserResource from "@/api/user";
// import CustomTable from '@/components/CustomTable.vue';
import { userStore } from '@/store/user'
import { ability } from '@/utils/ability'; // Import ability instance

const store = userStore()

const loading = ref(false)
const isModalVisible = ref(false)
const isEditing = ref(false)
const filterQuery = ref('')
const userForm = ref(null)

const user = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  sex: null,
  birthday: '',
  phone: '',
  role: '',
  location: '',
  address: '',
  organization_name: '',
  recipient_type: '',
  donor_type: '',
  notes: ''
})

const roles = ref([
  { id: 'admin', name: 'Admin' },
  { id: 'foodbank', name: 'Foodbank' },
  { id: 'donor', name: 'Donor' },
  { id: 'recipient', name: 'Recipient' }
])

const users = ref([])

const pageSizes = [10, 20, 50, 100]

const rules = {
  name: [{ required: true, message: 'Name is required', trigger: 'blur' }],
  email: [
    { required: true, message: 'Email is required', trigger: 'blur' },
    { type: 'email', message: 'Invalid email format', trigger: 'blur' }
  ],
  password: [
    { required: !isEditing.value, message: 'Password is required', trigger: 'blur' },
    { min: 6, message: 'Password must be at least 6 characters', trigger: 'blur' }
  ],
  password_confirmation: [{ required: !isEditing.value, message: 'Please confirm password', trigger: 'blur' }],
  role: [{ required: true, message: 'Role is required', trigger: 'change' }]
}



// Computed properties for permissions
const canViewFilters = computed(() => {
  const result = ability.can('view', 'filters');
  console.log('canViewFilters:', result);
  return result;
});

const canCreateUser = computed(() => {
  const result = ability.can('create', 'users');
  console.log('canCreateUser:', result);
  return result;
});

const canViewUsers = computed(() => {
  const result = ability.can('view', 'users');
  console.log('canViewUsers:', result);
  return result;
});

const canEditUser = (row) => {
  const result = ability.can('edit', 'users', { user_id: row.id });
  console.log('canEditUser:', result, 'Row:', row);
  return result;
};

const canDeleteUser = (row) => {
  const result = ability.can('delete', 'users', { user_id: row.id });
  console.log('canDeleteUser:', result, 'Row:', row);
  return result;
};

const canChangeStatus = computed(() => {
  const result = ability.can('changeStatus', 'users');
  console.log('canChangeStatus:', result);
  return result;
});

const updateStatus = async (id, newStatus) => {
  try {
    loading.value = true
    let response

    if (newStatus === 'approved') {
      response = await store.approveUser(id)
    } else if (newStatus === 'rejected') {
      response = await store.rejectUser(id)
    } else {
      response = await store.resetStatus(id)
    }
    console.log(response)

    ElMessage.success(response?.message || 'Status updated successfully')

    await fetchUsers() // Refresh the user list after status change
  } catch (error) {
    console.error('Status update failed:', error)
    ElMessage.error('Failed to update user status')
  } finally {
    loading.value = false
  }
}

// **Fetch users when component is mounted**
const fetchUsers = async () => {
  loading.value = true
  try {
    await store.fetchUsers() // Fetch all users once, store handles pagination
    console.log('Fetched users:', store.users)
    console.log('Pagination state:', store.pagination)
  } catch (error) {
    console.error('Failed to fetch users:', error)
    ElMessage.error('Failed to fetch users')
  } finally {
    loading.value = false
  }
}

// Computed Properties for Reactive Data
const paginatedUsers = computed(() => store.paginatedUsers)

// Handle Sorting
const handleSortChange = ({ prop, order }) => {
  const sortOrder = order === 'ascending' ? 'asc' : 'desc'
  store.setSortOptions(prop, sortOrder)
}

// Handle Pagination
const handlePageChange = (page) => {
  console.log('Page changed to:', page)
  store.setCurrentPage(page) // Update store
  store.fetchUsers() // Manually trigger API
}

const handleSizeChange = (size) => {
  console.log('Page size changed to:', size)
  store.setPerPage(size)
  fetchUsers() // Fetch new data with the updated page size
}

watch(
  () => store.paginatedUsers,
  (newPaginatedUsers) => {
    console.log('Paginated users updated:', newPaginatedUsers)
  }
)

watch(
  () => paginatedUsers.value,
  (updatedUsers) => {
    console.log('Updated table data:', updatedUsers)
  }
)

// Handle table actions (edit/delete)
const handleTableAction = (action, row) => {
  if (action === 'edit-user') {
    openEditModal(row)
  } else if (action === 'delete-user') {
    deleteUser(row)
  }
}

// Open create user modal
const openCreateModal = () => {
  isEditing.value = false
  isModalVisible.value = true
}

// Open edit user modal
const openEditModal = (row) => {
  isEditing.value = true
  user.value = { ...row }
  isModalVisible.value = true
}

// Delete user with confirmation
const deleteUser = async (row) => {
  try {
    await ElMessageBox.confirm('Are you sure you want to delete this user?', 'Warning', {
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
      type: 'warning'
    })
    await new UserResource().delete(row.id)
    ElMessage.success('User deleted successfully')
    fetchUsers()
  } catch (error) {
    console.error('Failed to delete user:', error)
    ElMessage.error('Failed to delete user')
  }
}

// Submit form (create/update)
const submitForm = async () => {
  try {
    loading.value = true

    const formattedUser = { ...user.value }
    if (formattedUser.birthday) {
      formattedUser.birthday = new Date(formattedUser.birthday).toISOString().slice(0, 19).replace('T', ' ')
    }

    if (isEditing.value) {
      await store.editUser(formattedUser.id, formattedUser)
    } else {
      await store.createUser(formattedUser)
    }

    isModalVisible.value = false
  } catch (error) {
    console.error('Failed to submit form:', error)

    // Handle validation errors
    if (error.response?.data?.errors) {
      Object.values(error.response.data.errors)
        .flat()
        .forEach((msg) => {
          ElMessage.error(msg)
        })
    } else {
      ElMessage.error(error.response?.data?.message || 'Form submission failed')
    }
  } finally {
    loading.value = false
  }
}

// Reset form fields
const resetForm = () => {
  userForm.value.resetFields()
  user.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    sex: null,
    birthday: '',
    phone: '',
    role: '',
    location: '',
    address: '',
    organization_name: '',
    recipient_type: '',
    donor_type: '',
    notes: ''
  }
}

// const fetchRoles = async () => {
//   try {
//     await store.fetchRoles(); // Calls the store method
//     roles.value = store.roleList; // Assigns the fetched role list
//   } catch (error) {
//     console.error('Failed to fetch roles:', error);
//     ElMessage.error('Could not load roles.');
//   }
// };

onMounted(async () => {
  console.log('Initial Pagination Data:', store.pagination)
  //  await fetchRoles();
  await fetchUsers()
  // console.log("Total users fetched:", store.users.length);
})
</script>
<style scoped>
/* Define CSS variables for consistency */
:root {
  --primary-color: #409eff;
  --secondary-color: #67c23a;
  --danger-color: #f56c6c;
  --text-color: #303133;
  --border-radius: 4px;
  --padding-sm: 4px;
  --padding-md: 8px;
  --padding-lg: 12px;
}

/* Ensure cards have padding and consistent spacing */
.el-card {
  margin-bottom: 10px;
  box-shadow: 0 1px 2.5px rgba(0, 0, 0, 0.1);
  border-radius: var(--border-radius);
  padding: var(--padding-md);
}

/* Filter section styles */
.filter-container {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  align-items: center;
  justify-content: space-between;
  padding: 5px 0;
}

.filter-container .el-input,
.filter-container .el-select {
  min-width: 200px;
  max-width: 300px;
}

/* Table styles for consistency */
.table-container {
  overflow-x: auto;
}

.el-table {
  width: 100%;
  border: 1px solid #ebeef5;
  border-radius: var(--border-radius);
}

.el-table th,
.el-table td {
  text-align: center;
  padding: 12px;
}

/* Pagination styles */
.pagination-container {
  display: flex;
  justify-content: center;
  padding: 20px 0;
}

/* Modal styles */
.el-dialog {
  padding: var(--padding-md);
}

.el-form-item {
  margin-bottom: 15px;
}

/* Button styling */
.el-button {
  border-radius: 5px;
  transition: all 0.3s ease;
  padding: 5px 10px;
}
.el-button i {
  margin-right: 4px;
}
.el-button:hover {
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
}
h2 {
  margin-bottom: 10px;
  text-align: center;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .filter-container {
    flex-direction: column;
    align-items: flex-start;
  }

  .el-card {
    padding: var(--padding-sm);
  }

  .el-form-item {
    display: block;
    width: 100%;
  }
}
</style>
