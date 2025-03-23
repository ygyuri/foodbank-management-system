<script setup>
import { ref, computed, onMounted } from 'vue';
import { useFeedbackStore } from '@/store/feedback';
import { userStore } from '@/store/user';
import { useRequestStore } from '@/store/requestStore';
import { ElMessage, ElMessageBox } from 'element-plus';

// Stores
const feedbackStore = useFeedbackStore();
const user = userStore();
const requestStore = useRequestStore();

const filters = ref({
  receiver_id: null,
  sender_id: null,
  rating: null
});


// Refs
const feedbackMessage = ref('');
const receiverId = ref(null);
const referenceId = ref(null);
const selectedScenario = ref('');
const selectedTemplate = ref(null);
const rating = ref(0);
const editingFeedback = ref(null);
const feedbackDialogVisible = ref(false);
const editDialogVisible = ref(false);
const users = ref([]); // List of users for the receiver dropdown



// Data Sources

const filteredUsers = ref([]); // Filtered users based on scenario

const thankYouTemplates = ref([
  { id: 1, content: 'Thank you for your support!' },
  { id: 2, content: 'We appreciate your contribution!' }
]);


// Scenario Options
const scenarioOptions = {
  'recipient-foodbank': 'Recipient → Foodbank',
  'foodbank-recipient': 'Foodbank → Recipient',
  'foodbank-donor': 'Foodbank → Donor',
  'donor-foodbank': 'Donor → Foodbank',
  'general': 'General Feedback'
};

const typeMapping = {
  'recipient-foodbank': 'request_fb',
  'foodbank-recipient': 'request_fb',
  'foodbank-donor': 'donation_request',
  'donor-foodbank': 'donation',
  'general': 'request_fb' // Default fallback
};


// Computed
const loading = computed(() => feedbackStore.loading);
const feedbackList = computed(() => feedbackStore.feedbackList);
const pagination = computed(() => feedbackStore.pagination);
const error = computed(() => feedbackStore.error);
const role = computed(() => user.roles?.[0]); // Extract first role from array
const userId = computed(() => user.id); // Authenticated user ID


// Pagination
const changePage = (page) => {
  feedbackStore.fetchFeedback(page);
};


// Apply Filters
const applyFilters = () => {
  feedbackStore.setFilters(filters.value);
  feedbackStore.fetchFeedback();
};

// Reset Filters
const resetFilters = () => {
  filters.value = {
    receiver_id: null,
    sender_id: null,
    rating: null
  };
  feedbackStore.setFilters(filters.value);
  feedbackStore.fetchFeedback();
};





// Fetch Users
const fetchUsers = async () => {
  await requestStore.fetchUsers({ fetchAll: true });
  users.value = requestStore.users;
  filteredUsers.value = users.value; // Default to all users
};



// Update Receivers Based on Scenario
const updateReceivers = async () => {
  receiverId.value = null; // Reset receiver selection
  

  console.log('Selected Scenario:', selectedScenario.value);

  if (selectedScenario.value === 'recipient-foodbank' || selectedScenario.value === 'donor-foodbank') {
    console.log('Fetching users with role: foodbank');
    await requestStore.fetchUsers({ role: 'foodbank', fetchAll: true });
  } else if (selectedScenario.value === 'foodbank-recipient') {
    console.log('Fetching users with role: recipient');
    await requestStore.fetchUsers({ role: 'recipient', fetchAll: true });
  } else if (selectedScenario.value === 'foodbank-donor') {
    console.log('Fetching users with role: donor');
    await requestStore.fetchUsers({ role: 'donor', fetchAll: true });
  } else {
    console.log('Defaulting to all users');
    filteredUsers.value = users.value;
    return;
  }

  filteredUsers.value = requestStore.users;
  console.log('Updated filteredUsers:', filteredUsers.value);
};


watch(selectedScenario, async () => {
  console.log('Scenario changed:', selectedScenario.value);
  await updateReceivers();
});


// Watch for scenario changes and update receivers dynamically
watch(selectedScenario, updateReceivers);





// Apply Template to Message Field
const applyTemplate = () => {
  feedbackMessage.value = selectedTemplate.value;
};

// Send Feedback
const sendFeedback = async () => {
  if (!receiverId.value || !feedbackMessage.value || !selectedScenario.value || !rating.value) {
    ElMessage.error('Receiver, type, rating, and message are required!');
    return;
  }

  const payload = {
    sender_id: userStore.userId,
    receiver_id: receiverId.value,
    reference: referenceId.value,
    message: feedbackMessage.value,
    type: typeMapping[selectedScenario.value] || 'request_fb', // Convert frontend value to ENUM
    rating: rating.value,
    thank_you_note: feedbackMessage.value
  };

  try {
    await feedbackStore.createFeedback(payload);
    ElMessage.success('Feedback sent successfully!');
    resetForm();
    feedbackDialogVisible.value = false;
  } catch (error) {
    ElMessage.error(error.message || 'Failed to send feedback');
  }
};


// Edit feedback
const editFeedback = (feedback) => {
  editingFeedback.value = { ...feedback };
  editDialogVisible.value = true;
};


  // Update feedback
  const updateFeedback = async () => {
    try {
      const payload = {
        receiver_id: editingFeedback.value.receiver_id,
        rating: editingFeedback.value.rating,
        type: editingFeedback.value.type,
        reference: editingFeedback.value.reference,
        message: editingFeedback.value.message,
        thank_you_note: editingFeedback.value.thank_you_note,
      };

      await feedbackStore.updateFeedback(editingFeedback.value.id, payload);
      ElMessage.success('Feedback updated successfully!');
      editDialogVisible.value = false;
    } catch (error) {
      ElMessage.error(error.response?.data?.message || 'Failed to update feedback');
    }
  };

// Delete feedback
const deleteFeedback = async (id) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to delete this feedback?',
      'Warning',
      { confirmButtonText: 'Yes', cancelButtonText: 'No', type: 'warning' }
    );
    await feedbackStore.deleteFeedback(id);
    ElMessage.success('Feedback deleted successfully!');
  } catch (error) {
    ElMessage.error(error.message || 'Failed to delete feedback');
  }
};

// Reset form
const resetForm = () => {
  feedbackMessage.value = '';
  receiverId.value = null;
  referenceId.value = null;
  selectedScenario.value = '';
  selectedTemplate.value = null;
  rating.value = 0;
};

// Fetch user info and feedback data on mount
onMounted(async () => {
  console.log('Fetching feedback on mount...');
  try {
    await user.getInfo();
    await feedbackStore.fetchFeedback();
    await fetchUsers();
   
  } catch (error) {
    console.error('Error initializing data:', error);
    ElMessage.error('Failed to load data. Please try again.');
  } finally {
    loading.value = false;
  }
});

</script>

<template>
  <div class="page-container">
    <div class="header">
      <h1 class="text-2xl font-bold">Feedback Management</h1>
      <el-button type="primary" @click="feedbackDialogVisible = true">Send Feedback</el-button>
    </div>

     <!-- Filter Card -->
    <!-- Filter Card -->
  <el-card class="filter-card">
    <el-form :inline="true" @submit.prevent="applyFilters" class="filter-form">
      <!-- Receiver Filter -->
      <el-form-item label="Receiver" class="filter-item">
        <el-select
          v-model="filters.receiver_id"
          placeholder="Select Receiver"
          clearable
          class="filter-select"
        >
          <el-option
            v-for="user in users"
            :key="user.id"
            :label="user.name"
            :value="user.id"
          />
        </el-select>
      </el-form-item>

      <!-- Sender Filter -->
      <el-form-item label="Sender" class="filter-item">
        <el-select
          v-model="filters.sender_id"
          placeholder="Select Sender"
          clearable
          class="filter-select"
        >
          <el-option
            v-for="user in users"
            :key="user.id"
            :label="user.name"
            :value="user.id"
          />
        </el-select>
      </el-form-item>

      <!-- Rating Filter -->
      <el-form-item label="Rating" class="filter-item">
        <el-select
          v-model="filters.rating"
          placeholder="Select Rating"
          clearable
          class="filter-select"
        >
          <el-option
            v-for="rating in [1, 2, 3, 4, 5]"
            :key="rating"
            :label="rating"
            :value="rating"
          />
        </el-select>
      </el-form-item>

      <!-- Apply Filters Button -->
      <el-form-item class="filter-item">
        <el-button type="primary" @click="applyFilters" class="filter-button">
          Apply Filters
        </el-button>
        <el-button @click="resetFilters" class="filter-button">
          Reset Filters
        </el-button>
      </el-form-item>
    </el-form>
  </el-card>


    <!-- Feedback Table -->
    <div class="table-container">
      <el-table :data="feedbackList" v-loading="loading" class="responsive-table">
        <el-table-column label="From" width="180">
          <template #default="{ row }">
            <div>{{ row.sender?.name }}</div>
          </template>
        </el-table-column>
        <el-table-column label="To" width="180">
          <template #default="{ row }">
            <div>{{ row.receiver?.name }}</div>
          </template>
        </el-table-column>
        <el-table-column label="Rating" width="120">
          <template #default="{ row }">
            <el-rate v-model="row.rating" disabled show-score text-color="#ff9900" score-template="{value} points" />
          </template>
        </el-table-column>
        <el-table-column prop="type" label="Type" width="120"></el-table-column>
        <el-table-column prop="reference" label="Reference" width="120"></el-table-column>
        <el-table-column prop="created_at" label="Created At" width="180">
          <template #default="{ row }">
            <div>{{ new Date(row.created_at).toLocaleString() }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="message" label="Message"></el-table-column>
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <el-button size="small" @click="editFeedback(row)">Edit</el-button>
            <el-button type="danger" size="small" @click="deleteFeedback(row.id)">Delete</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
      <el-pagination
        :current-page="pagination.currentPage"
        :page-size="pagination.perPage"
        :total="pagination.totalItems"
        layout="prev, pager, next"
        @current-change="feedbackStore.fetchFeedback"
      />
    </div>

    <!-- Send Feedback Dialog -->
    <el-dialog v-model="feedbackDialogVisible" title="Send Feedback" :width="dialogWidth" center>
      <el-form label-width="120px">
        <!-- Scenario Selection -->
        <el-form-item label="Scenario" required>
          <el-select v-model="selectedScenario" placeholder="Select scenario">
            <el-option v-for="(label, key) in scenarioOptions" :key="key" :label="label" :value="key" />
          </el-select>
        </el-form-item>

        <!-- Receiver Selection -->
        <el-form-item label="Receiver" required>
          <el-select v-model="receiverId" placeholder="Select receiver" filterable>
            <el-option v-for="user in filteredUsers" :key="user.id" :label="user.name" :value="user.id" />
          </el-select>
        </el-form-item>

        <el-form-item label="Type" required>
          <el-select v-model="selectedScenario" placeholder="Select feedback type">
            <el-option label="Request Feedback" value="request_fb" />
            <el-option label="Donation Request" value="donation_request" />
            <el-option label="Donation" value="donation" />
          </el-select>
        </el-form-item>

        <!-- Reference Selection -->
        <el-form-item label="Reference">
          <el-input v-model="referenceId" placeholder="Enter Reference ID"></el-input>
        </el-form-item>

        <!-- Rating -->
        <el-form-item label="Rating" required>
          <el-rate v-model="rating" />
        </el-form-item>

        <!-- Thank You Note (Optional) -->
        <el-form-item label="Template">
          <el-select v-model="selectedTemplate" placeholder="Select template" @change="applyTemplate">
            <el-option v-for="template in thankYouTemplates" :key="template.id" :label="template.content" :value="template.content" />
          </el-select>
        </el-form-item>

        <!-- Message -->
        <el-form-item label="Message" required>
          <el-input v-model="feedbackMessage" type="textarea" rows="4" placeholder="Enter your feedback" />
        </el-form-item>
      </el-form>

      <!-- Action Buttons -->
      <template #footer>
        <el-button @click="feedbackDialogVisible = false">Cancel</el-button>
        <el-button type="primary" @click="sendFeedback">Send</el-button>
      </template>
    </el-dialog>

 
      <!-- Edit Feedback Dialog -->
    <el-dialog v-model="editDialogVisible" title="Edit Feedback" :width="dialogWidth" class="modern-modal">
      <el-form @submit.prevent="updateFeedback">
        <!-- Receiver -->
        <el-form-item label="Receiver" required>
          <el-select v-model="editingFeedback.receiver_id" placeholder="Select receiver" filterable>
            <el-option v-for="user in filteredUsers" :key="user.id" :label="user.name" :value="user.id" />
          </el-select>
        </el-form-item>

        <!-- Rating -->
        <el-form-item label="Rating" required>
          <el-rate v-model="editingFeedback.rating" />
        </el-form-item>

        <!-- Type -->
        <el-form-item label="Type">
          <el-select v-model="editingFeedback.type" placeholder="Select feedback type">
            <el-option label="Request Feedback" value="request_fb" />
            <el-option label="Donation Request" value="donation_request" />
            <el-option label="Donation" value="donation" />
          </el-select>
        </el-form-item>

        <!-- Reference -->
        <el-form-item label="Reference">
          <el-input v-model="editingFeedback.reference" placeholder="Enter Reference ID" />
        </el-form-item>

        <!-- Message -->
        <el-form-item label="Message">
          <el-input v-model="editingFeedback.message" type="textarea" rows="4" placeholder="Enter your feedback" />
        </el-form-item>

        <!-- Thank You Note -->
        <el-form-item label="Thank You Note">
          <el-input v-model="editingFeedback.thank_you_note" type="textarea" rows="4" placeholder="Enter thank you note" />
        </el-form-item>

        <!-- Action Buttons -->
        <el-form-item>
          <el-button type="primary" @click="updateFeedback">Save Changes</el-button>
          <el-button @click="editDialogVisible = false">Cancel</el-button>
        </el-form-item>
      </el-form>
    </el-dialog>


  </div>
</template>
<style scoped>
.page-container {
  height: 100vh;
  overflow-y: auto;
  padding: 20px;
  background-color: #f5f7fa;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  margin-bottom: 20px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.responsive-table {
  width: 100%;
  min-width: 800px; /* Ensure table has a minimum width */
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.modern-modal {
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modern-modal .el-dialog__header {
  background-color: #409EFF;
  color: white;
  border-radius: 12px 12px 0 0;
}

.modern-modal .el-dialog__body {
  padding: 20px;
}

.modern-modal .el-form-item {
  margin-bottom: 20px;
}

.modern-modal .el-button {
  margin-right: 10px;
}

.el-dialog {
  border-radius: 10px;
  width: 90%; /* Responsive dialog width */
  max-width: 500px; /* Maximum width for larger screens */
}

.el-form-item {
  margin-bottom: 15px;
}

.el-button {
  border-radius: 5px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .el-dialog {
    width: 95%; /* Adjust dialog width for smaller screens */
  }

  .header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header .el-button {
    margin-top: 10px;
  }
}
.filter-card {
  margin-bottom: 8px;
  padding: 5px;
  background-color: #ffffff;
  border-radius: 2px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.filter-form {
  display: flex;
  flex-wrap: wrap;
  gap: 8px; /* Consistent spacing between items */
}

.filter-item {
  margin: 0; /* Remove default margins */
  flex: 1 1 100px; /* Allow items to grow and shrink, with a base width of 200px */
}

.filter-select {
  width: 100%; /* Ensure select inputs take full width */
}

.filter-button {
  margin-right: 4px; /* Spacing between buttons */
  border-radius: 2px; /* Rounded corners for buttons */
}

/* Responsive Design */
@media (max-width: 768px) {
  .filter-form {
    flex-direction: column; /* Stack items vertically on smaller screens */
  }

  .filter-item {
    flex: 1 1 auto; /* Allow items to take full width */
  }

  .filter-button {
    width: 100%; /* Full-width buttons on smaller screens */
    margin-bottom: 8px; /* Spacing between stacked buttons */
  }
}

/* Hover and Focus States */
.filter-select:hover,
.filter-select:focus {
  border-color: #409eff; /* Highlight on hover/focus */
}

.filter-button:hover {
  background-color: #409eff; /* Primary color on hover */
  color: #ffffff; /* White text on hover */
}

/* Accessibility */
.filter-item label {
  font-weight: 150; /* Bold labels for better readability */
  color: #606266; /* Subtle label color */
}

.filter-select::placeholder {
  color: #c0c4cc; /* Placeholder text color */
}

</style>