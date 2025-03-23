<template>
    <div class="role-permissions-manager">
      <!-- Page Header -->
      <el-page-header @back="goBack" title="Back" class="page-header">
        <template #content>
          <h2>Role Permissions Management</h2>
        </template>
      </el-page-header>
  
      <!-- Role Selection Dropdown -->
      <el-card class="role-selector-card">
        <el-select
          v-model="selectedRole"
          placeholder="Select a role"
          class="role-selector"
          @change="loadRolePermissions"
        >
          <el-option
            v-for="role in roles"
            :key="role.id"
            :label="role.name"
            :value="role.id"
          />
        </el-select>
      </el-card>
  
      <!-- Permissions Table -->
      <el-card class="permissions-table-card">
        <el-table
          :data="filteredPermissions"
          stripe
          style="width: 100%"
          v-loading="loading"
          height="400"
        >
          <el-table-column prop="subject" label="Subject" sortable />
          <el-table-column prop="action" label="Action" sortable />
          <el-table-column label="Allowed">
            <template #default="{ row }">
              <el-checkbox
                v-model="row.allowed"
                @change="updatePermission(row)"
              />
            </template>
          </el-table-column>
        </el-table>
  
        <!-- Pagination -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="pagination.currentPage"
            v-model:page-size="pagination.pageSize"
            :total="pagination.total"
            :page-sizes="[10, 20, 50, 100]"
            layout="sizes, prev, pager, next, jumper"
            @size-change="handlePageSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>
  
      <!-- Error Handling -->
      <el-alert
        v-if="error"
        :title="error"
        type="error"
        :closable="false"
        class="error-alert"
      />
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue';
  import { ElMessage, ElPageHeader, ElCard, ElSelect, ElOption, ElTable, ElTableColumn, ElCheckbox, ElPagination, ElAlert } from 'element-plus';
  import { useAbilityStore } from '@/store/abilityStore';
  
  // Stores
  const abilityStore = useAbilityStore();
  
  // State
  const roles = ref([
    { id: 'admin', name: 'Admin' },
    { id: 'donor', name: 'Donor' },
    { id: 'foodbank', name: 'Foodbank' },
    { id: 'recipient', name: 'Recipient' },
  ]);
  const selectedRole = ref(null);
  const permissions = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const pagination = ref({
    currentPage: 1,
    pageSize: 10,
    total: 0,
  });
  
  // Fetch Roles on Mount
  onMounted(() => {
    console.log('Roles loaded:', roles.value);
  });
  
  // Load Permissions for Selected Role
  const loadRolePermissions = () => {
    if (!selectedRole.value) return;
  
    try {
      loading.value = true;
      const role = roles.value.find(role => role.id === selectedRole.value);
      if (!role) throw new Error('Role not found');
  
      // Initialize abilities for the selected role
      abilityStore.initializeAbilities([role.id], []);
  
      // Map permissions for the table
      permissions.value = abilityStore.ability.rules.map(rule => ({
        action: rule.action,
        subject: rule.subject,
        allowed: abilityStore.can(rule.action, rule.subject),
      }));
  
      pagination.value.total = permissions.value.length;
    } catch (err) {
      error.value = 'Failed to load role permissions.';
      console.error('Error loading role permissions:', err);
    } finally {
      loading.value = false;
    }
  };
  
  // Update Permission in Real-Time
  const updatePermission = (permission) => {
    try {
      abilityStore.togglePermission(permission.action, permission.subject, permission.allowed);
      ElMessage.success('Permission updated successfully!');
    } catch (err) {
      error.value = 'Failed to update permission.';
      console.error('Error updating permission:', err);
    }
  };
  
  // Filtered Permissions for Pagination
  const filteredPermissions = computed(() => {
    const start = (pagination.value.currentPage - 1) * pagination.value.pageSize;
    const end = start + pagination.value.pageSize;
    return permissions.value.slice(start, end);
  });
  
  // Pagination Handlers
  const handlePageChange = (page) => {
    pagination.value.currentPage = page;
  };
  
  const handlePageSizeChange = (size) => {
    pagination.value.pageSize = size;
    pagination.value.currentPage = 1;
  };
  
  // Go back to the previous page
  const goBack = () => {
    // Implement navigation logic if needed
  };
  </script>
  
  <style scoped>
  .role-permissions-manager {
    padding: 20px;
  }
  
  .page-header {
    margin-bottom: 20px;
  }
  
  .role-selector-card {
    margin-bottom: 20px;
  }
  
  .role-selector {
    width: 100%;
  }
  
  .permissions-table-card {
    margin-bottom: 20px;
  }
  
  .pagination-container {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
  }
  
  .error-alert {
    margin-top: 20px;
  }
  </style>