<template>
  <div class="app-container scroll-y">
    <div class="filter-container">
      <el-input
        v-model="params.keyword"
        :placeholder="t('user.name') + '/' + t('user.email')"
        style="width: 200px"
        class="filter-item"
        @keyup.enter.native="handleFilter"
      />
      <el-select
        v-model="params.role"
        :placeholder="t('roles.name')"
        clearable
        style="width: 90px; margin-right: 5px"
        class="filter-item"
        @change="handleFilter"
      >
        <el-option v-for="item in roles" :key="item" :label="uppercaseFirst(item)" :value="item" />
      </el-select>

      <el-button class="filter-item" type="primary" :icon="Search" @click="handleFilter">
        {{ t('table.search') }}
      </el-button>
      <el-button class="filter-item" type="primary" :icon="Plus" @click="handleCreate">
        {{ t('table.add') }}
      </el-button>
    </div>

    <custom-table
      :table-data="tableData"
      :table-column="basicColumn"
      :table-option="tableOption"
      :pagination="pagination"
      :paginate="true"
      :page-sizes="pageSizes"
      :loading="loading"
      @table-action="tableActions"
      @set-params="setParams"
    >
      <template #roles="{ row }">
        <span>{{ row.roles.join(', ') }}</span>
      </template>
      <template #table_options="scope">
        <div v-if="!scope.row.roles.includes('user')">
          <el-button
            v-for="(action, index) in tableOption.item_actions"
            :type="action.type ? action.type : 'primary'"
            :size="action.size ? action.size : ''"
            @click="tableActions(action.name, scope.row)"
          >
            <el-svg-item :el-svg-name="action.icon" :title="action.label"></el-svg-item>
          </el-button>
        </div>
      </template>
    </custom-table>

    <el-dialog :title="'Create New User'" v-model="dialogFormVisible">
      <div v-loading="userCreating" class="form-container">
        <el-form
          ref="refUserForm"
          :rules="rules"
          :model="newUser"
          label-position="left"
          label-width="150px"
          style="max-width: 600px"
        >
          <!-- Role -->
          <el-form-item :label="t('user.role')" prop="role">
            <el-select v-model="newUser.role" class="filter-item" placeholder="Please select role">
              <el-option v-for="item in nonAdminRoles" :key="item" :label="uppercaseFirst(item)" :value="item" />
            </el-select>
          </el-form-item>

          <!-- Name -->
          <el-form-item :label="t('user.name')" prop="name">
            <el-input v-model="newUser.name" />
          </el-form-item>

          <!-- Email -->
          <el-form-item :label="t('user.email')" prop="email">
            <el-input v-model="newUser.email" />
          </el-form-item>

          <!-- Password -->
          <el-form-item :label="t('user.password')" prop="password">
            <el-input v-model="newUser.password" show-password />
          </el-form-item>

          <!-- Confirm Password -->
          <el-form-item :label="t('user.confirmPassword')" prop="confirmPassword">
            <el-input v-model="newUser.confirmPassword" show-password />
          </el-form-item>

          <!-- Sex (Male/Female) -->
          <el-form-item :label="t('user.sex')">
            <el-radio-group v-model="newUser.sex">
              <el-radio :label="0">{{ t('user.male') }}</el-radio>
              <el-radio :label="1">{{ t('user.female') }}</el-radio>
            </el-radio-group>
          </el-form-item>

          <!-- Birthday -->
          <el-form-item :label="t('user.birthday')">
            <el-date-picker
              v-model="newUser.birthday"
              type="date"
              placeholder="Select birthday"
              value-format="YYYY-MM-DD"
            />
          </el-form-item>

          <!-- Phone Number -->
          <el-form-item :label="t('user.phone')">
            <el-input v-model="newUser.phone" />
          </el-form-item>

          <!-- Profile Picture -->
          <el-form-item :label="t('user.profilePicture')">
            <el-input v-model="newUser.profile_picture" />
          </el-form-item>

          <!-- Location -->
          <el-form-item :label="t('user.location')">
            <el-input v-model="newUser.location" />
          </el-form-item>

          <!-- Address -->
          <el-form-item :label="t('user.address')">
            <el-input v-model="newUser.address" />
          </el-form-item>

          <!-- Organization Name -->
          <el-form-item :label="t('user.organizationName')">
            <el-input v-model="newUser.organization_name" />
          </el-form-item>

          <!-- Recipient Type -->
          <el-form-item :label="t('user.recipientType')">
            <el-input v-model="newUser.recipient_type" />
          </el-form-item>

          <!-- Donor Type -->
          <el-form-item :label="t('user.donorType')">
            <el-input v-model="newUser.donor_type" />
          </el-form-item>

          <!-- Description -->
          <el-form-item :label="t('user.description')">
            <el-input v-model="newUser.description" type="textarea" maxlength="255" show-word-limit />
          </el-form-item>

          <!-- Notes -->
          <el-form-item :label="t('user.notes')">
            <el-input v-model="newUser.notes" type="textarea" maxlength="255" show-word-limit />
          </el-form-item>
        </el-form>

        <!-- Footer Buttons -->
        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogFormVisible = false">
            {{ t('table.cancel') }}
          </el-button>
          <el-button type="primary" @click="createUser(refUserForm)">
            {{ t('table.confirm') }}
          </el-button>
        </div>
      </div>
    </el-dialog>

    <el-dialog v-model="dialogPermissionVisible" :title="'Edit Permissions - ' + currentUser.name">
      <div v-if="currentUser.name" v-loading="dialogPermissionLoading" class="form-container">
        <div class="permissions-container">
          <div class="block">
            <el-form :model="currentUser" label-width="80px" label-position="top">
              <el-form-item label="Menus">
                <el-tree
                  ref="refMenuPermissions"
                  :data="normalizedMenuPermissions"
                  :default-checked-keys="permissionKeys(userMenuPermissions)"
                  :props="permissionProps"
                  show-checkbox
                  node-key="id"
                  class="permission-tree"
                />
              </el-form-item>
            </el-form>
          </div>
          <div class="block">
            <el-form :model="currentUser" label-width="80px" label-position="top">
              <el-form-item label="Permissions">
                <el-tree
                  ref="refOtherPermissions"
                  :data="normalizedOtherPermissions"
                  :default-checked-keys="permissionKeys(userOtherPermissions)"
                  :props="permissionProps"
                  show-checkbox
                  node-key="id"
                  class="permission-tree"
                />
              </el-form-item>
            </el-form>
          </div>
          <div class="clear-left" />
        </div>
        <div style="text-align: right">
          <el-button type="danger" @click="dialogPermissionVisible = false">
            {{ t('permission.cancel') }}
          </el-button>
          <el-button type="primary" @click="confirmPermission">
            {{ t('permission.confirm') }}
          </el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import CustomTable from '@/components/CustomTable.vue'
import ElSvgItem from '@/components/Item/ElSvgItem.vue'
import UserResource from '@/api/user'
import Resource from '@/api/resource'
import checkPermission from '@/utils/permission'
import { ElMessage, ElMessageBox } from 'element-plus'
import { uppercaseFirst } from '../../utils'
import { useI18n } from 'vue-i18n'
import { userStore } from '@/store/user' // Permission checking
import { Search, Plus } from '@element-plus/icons-vue'
import dayjs from 'dayjs'

export default {
  components: { CustomTable, ElSvgItem },
  setup() {
    const { t } = useI18n({ useScope: 'global' }) // Translation for i18n
    const userResource = new UserResource() // Instance to interact with user API
    const permissionResource = new Resource('permissions') // Instance to interact with permissions API
    const refUserForm = ref(null) // Ref for the user form component
    const refMenuPermissions = ref(null) // Ref for menu permissions
    const refOtherPermissions = ref(null) // Ref for other permissions

    // Validation for password confirmation
    const validateConfirmPassword = (rule, value, callback) => {
      if (value !== resData.newUser.password) {
        callback(new Error('Password is mismatched!'))
      } else {
        callback()
      }
    }

    // Reactive data for handling the table and form state
    const resData = reactive({
      basicColumn: [
        {
          prop: 'id',
          label: 'ID',
          width: '100'
        },
        {
          prop: 'name',
          label: 'Name'
        },
        {
          prop: 'email',
          label: 'Email'
        },
        {
          prop: 'roles',
          label: 'Role',
          width: '200',
          slot: true
        }
      ],
      tableOption: {},
      tableData: [],
      loading: true,
      pagination: {
        total: 0,
        currentPage: 1,
        pageSize: 10
      },
      params: {
        page: 1,
        per_page: 10,
        keyword: '',
        role: ''
      },
      roles: ['admin', 'manager', 'editor', 'user', 'visitor', 'donor', 'foodbank', 'recipient'],
      nonAdminRoles: ['editor', 'user', 'visitor', 'donor', 'foodbank', 'recipient'],
      pageSizes: [10, 30, 50, 100],
      dialogFormVisible: ref(false),
      userCreating: false,
      // Stores details for the new user being created
      newUser: {
        role: '', // User role selection
        name: '', // User's full name
        email: '', // User's email address
        password: '', // Password for the new user
        confirmPassword: '', // Confirmation of the entered password
        sex: null, // User gender (0 for male, 1 for female)
        birthday: '', // User's date of birth (YYYY-MM-DD format)
        phone: '', // User's phone number
        profile_picture: '', // URL or path for profile picture
        location: '', // User's geographical location
        address: '', // User's physical address
        organization_name: '', // Organization the user belongs to
        recipient_type: '', // Type of recipient (if applicable)
        donor_type: '', // Type of donor (if applicable)
        description: '', // Short description about the user
        notes: '' // Additional notes about the user
      },

      rules: {
        role: [{ required: true, message: 'Role is required', trigger: 'change' }],
        name: [{ required: true, message: 'Name is required', trigger: 'blur' }],
        email: [
          { required: true, message: 'Email is required', trigger: 'blur' },
          { type: 'email', message: 'Please input correct email address', trigger: ['blur', 'change'] }
        ],
        password: [{ required: true, message: 'Password is required', trigger: 'blur' }],
        confirmPassword: [{ validator: validateConfirmPassword, trigger: 'blur' }]
      },
      dialogPermissionVisible: false,
      dialogPermissionLoading: false,
      permissionProps: {
        children: 'children',
        label: 'name',
        disabled: 'disabled'
      },
      permissions: [],
      menuPermissions: [],
      otherPermissions: [],
      currentUserId: 0,
      currentUser: {
        name: '',
        permissions: {
          role: [],
          user: []
        },
        rolePermissions: []
      },
      normalizedMenuPermissions: computed(() => {
        let tmp = []
        resData.currentUser.permissions.role.forEach((permission) => {
          tmp.push({
            id: permission.id,
            name: permission.name,
            disabled: true
          })
        })
        const rolePermissions = {
          id: -1, // Just a faked ID
          name: 'Inherited from role',
          disabled: true,
          children: classifyPermissions(tmp).menu
        }

        tmp = resData.menuPermissions.filter(
          (permission) => !resData.currentUser.permissions.role.find((p) => p.id === permission.id)
        )
        const userPermissions = {
          id: 0, // Faked ID
          name: 'Extra menus',
          children: tmp,
          disabled: tmp.length === 0
        }

        return [rolePermissions, userPermissions]
      }),
      normalizedOtherPermissions: computed(() => {
        if (resData.currentUser.permissions.role.length === 0) {
          return []
        }
        let tmp = []
        resData.currentUser.permissions.role.forEach((permission) => {
          tmp.push({
            id: permission.id,
            name: permission.name,
            disabled: true
          })
        })
        const rolePermissions = {
          id: -1,
          name: 'Inherited from role',
          disabled: true,
          children: classifyPermissions(tmp).other
        }

        tmp = resData.otherPermissions.filter(
          (permission) => !resData.currentUser.permissions.role.find((p) => p.id === permission.id)
        )
        const userPermissions = {
          id: 0,
          name: 'Extra permissions',
          children: tmp,
          disabled: tmp.length === 0
        }

        return [rolePermissions, userPermissions]
      }),
      userMenuPermissions: computed(() => {
        if (resData.userPermissions.length === 0) {
          return []
        }
        const { menu } = classifyPermissions(resData.userPermissions)
        return menu
      }),
      userOtherPermissions: computed(() => {
        if (resData.userPermissions.length === 0) {
          return []
        }
        const { other } = classifyPermissions(resData.userPermissions)
        return other
      }),
      userPermissions: computed(() => {
        return resData.currentUser.permissions.role.concat(resData.currentUser.permissions.user)
      })
    })

    const useUserStore = userStore()
    // Check if the user has the 'manage user' permission and set table options accordingly
    if (useUserStore.permissions.includes('manage user')) {
      resData.tableOption = {
        slot: true,
        label: t('table.actions'),
        fixed: 'right',
        item_actions: [
          { name: 'edit-item', type: 'primary', icon: 'EditPen', label: t('table.edit') },
          { name: 'delete-item', type: 'danger', icon: 'Delete', label: t('table.delete') }
        ]
      }

      // Add extra action for permission management if the user has 'manage permission' permission
      if (useUserStore.permissions.includes('manage permission')) {
        resData.tableOption.item_actions.push({
          name: 'edit-permission-item',
          type: 'warning',
          icon: 'EditPen',
          label: t('permission.editPermission')
        })
      }
    }

    // Fetch the list of users from the API and update the table data
    const getList = () => {
      resData.loading = true
      userResource.list(resData.params).then((res) => {
        resData.tableData = res.data
        resData.pagination = res.meta
        resData.loading = false
      })
    }

    // Filter the user list based on the search parameters
    const handleFilter = () => {
      resData.params.page = 1
      getList()
      if (refUserForm) {
        refUserForm.clearValidate()
      }
    }

    // Set parameters for the user list and trigger a data fetch
    const setParams = (key, value) => {
      if (key !== 'per_page' && key !== 'page') {
        resData.params.page = 1
      }
      resData.params[key] = value
      getList()
    }
    const router = useRouter()
    // Handle actions for editing, deleting, or managing user permissions
    const tableActions = (action, data) => {
      if (action === 'edit-item') {
        router.push(`/administrator/users/edit/${data.id}`)
      } else if (action === 'edit-permission-item') {
        handleEditPermissions(data)
      } else if (action === 'delete-item') {
        ElMessageBox.confirm('This will permanently delete user ' + data.name + '. Continue?', 'Warning', {
          confirmButtonText: 'OK',
          cancelButtonText: 'Cancel',
          type: 'warning'
        })
          .then(() => {
            userResource
              .destroy(data.id)
              .then((response) => {
                ElMessage({
                  type: 'success',
                  message: 'Delete completed'
                })
                handleFilter()
              })
              .catch((error) => {
                console.log(error)
              })
          })
          .catch(() => {
            ElMessage({
              type: 'info',
              message: 'Delete canceled'
            })
          })
      }
    }

    // Show the create user form
    const handleCreate = () => {
      resetNewUser()
      resData.dialogFormVisible = true
    }
    // Create a new user and save it to the database
    const createUser = (formEl) => {
      if (!formEl) {
        return
      }
      formEl.validate((valid) => {
        if (valid) {
          resData.newUser.roles = [resData.newUser.role]
          if (resData.newUser.birthday_model) {
            resData.newUser.birthday = dayjs(resData.newUser.birthday_model).format('YYYY-MM-DD HH:mm:ss')
          }
          resData.userCreating = true
          userResource
            .store(resData.newUser)
            .then((response) => {
              ElMessage({
                message:
                  'New user ' + resData.newUser.name + '(' + resData.newUser.email + ') has been created successfully.',
                type: 'success',
                duration: 5 * 1000
              })
              resetNewUser()
              resData.dialogFormVisible = false
              handleFilter()
            })
            .catch((error) => {
              console.log(error)
            })
            .finally(() => {
              resData.userCreating = false
            })
        } else {
          console.log('error submit!!')
          return false
        }
      })
    }
    const resetNewUser = () => {
      resData.newUser = {
        role: 'user'
      }
    }

    const getPermissions = async () => {
      const { data } = await permissionResource.list({})
      const { all, menu, other } = classifyPermissions(data)
      resData.permissions = all
      resData.menuPermissions = menu
      resData.otherPermissions = other
    }

    const handleEditPermissions = async (userData) => {
      resData.currentUserId = userData.id
      resData.dialogPermissionLoading = true
      resData.dialogPermissionVisible = true
      const { data } = await userResource.permissions(userData.id)
      resData.currentUser = {
        id: userData.id,
        name: userData.name,
        permissions: data
      }
      resData.dialogPermissionLoading = false
      nextTick(() => {
        refMenuPermissions.value?.setCheckedKeys(permissionKeys(resData.userMenuPermissions))
        refOtherPermissions.value?.setCheckedKeys(permissionKeys(resData.userOtherPermissions))
      })
    }
    const permissionKeys = (permissions) => {
      return permissions.map((permssion) => permssion.id)
    }
    const classifyPermissions = (permissions) => {
      const all = []
      const menu = []
      const other = []
      permissions.forEach((permission) => {
        const permissionName = permission.name
        all.push(permission)
        if (permissionName.startsWith('view menu')) {
          menu.push(normalizeMenuPermission(permission))
        } else {
          other.push(normalizePermission(permission))
        }
      })
      return { all: all, menu: menu, other: other }
    }

    const normalizeMenuPermission = (permission) => {
      return {
        id: permission.id,
        name: uppercaseFirst(permission.name.substring(10)),
        disabled: permission.disabled || false
      }
    }

    const normalizePermission = (permission) => {
      const disabled = permission.disabled || permission.name === 'manage permission'
      return { id: permission.id, name: uppercaseFirst(permission.name), disabled: disabled }
    }

    const confirmPermission = () => {
      const checkedMenu = refMenuPermissions.value?.getCheckedKeys()
      const checkedOther = refOtherPermissions.value?.getCheckedKeys()
      const checkedPermissions = checkedMenu.concat(checkedOther)
      resData.dialogPermissionLoading = true

      userResource.updatePermission(resData.currentUserId, { permissions: checkedPermissions }).then((response) => {
        ElMessage({
          message: 'Permissions has been updated successfully',
          type: 'success',
          duration: 5 * 1000
        })
        resData.dialogPermissionLoading = false
        resData.dialogPermissionVisible = false
      })
    }
    onMounted(() => {
      getList()
      if (checkPermission(['manage permission'])) {
        getPermissions()
      }
    })

    return {
      t,
      refUserForm,
      refMenuPermissions,
      refOtherPermissions,
      ...toRefs(resData),
      getList,
      handleFilter,
      setParams,
      tableActions,
      uppercaseFirst,
      handleCreate,
      createUser,
      permissionKeys,
      confirmPermission,
      Search,
      Plus
    }
  }
}
</script>

<style lang="scss" scoped>
.edit-input {
  padding-right: 100px;
}

.cancel-btn {
  position: absolute;
  right: 15px;
  top: 10px;
}

.dialog-footer {
  text-align: left;
  padding-top: 0;
  margin-left: 150px;
}

.app-container {
  flex: 1;
  justify-content: space-between;
  font-size: 14px;
  padding-right: 8px;

  .block {
    float: left;
    min-width: 250px;
  }

  .clear-left {
    clear: left;
  }
}
</style>
