<template>
    <div class="register-container">
      <el-card class="register-card">
        <h2 class="register-title">Sign Up</h2>
        <el-form ref="registerForm" :model="form" :rules="rules" label-width="140px">
          <!-- Full Name -->
          <el-form-item label="Full Name *" prop="name">
            <el-input v-model="form.name" placeholder="Enter your full name" clearable />
          </el-form-item>
  
          <!-- Email -->
          <el-form-item label="Email *" prop="email">
            <el-input v-model="form.email" type="email" placeholder="Enter your email" clearable />
          </el-form-item>
  
          <!-- Password -->
          <el-form-item label="Password *" prop="password">
            <el-input v-model="form.password" type="password" placeholder="Enter password" show-password />
          </el-form-item>
  
          <!-- Confirm Password -->
          <el-form-item label="Confirm Password *" prop="password_confirmation">
            <el-input v-model="form.password_confirmation" type="password" placeholder="Confirm password" show-password />
          </el-form-item>
  
          <!-- Phone -->
          <el-form-item label="Phone *" prop="phone">
            <el-input v-model="form.phone" placeholder="Enter phone number" clearable />
          </el-form-item>
  
          <!-- Birthday -->
          <el-form-item label="Birthday">
            <el-date-picker v-model="form.birthday" type="date" placeholder="Select birthday" />
          </el-form-item>
  
          <!-- Sex -->
          <el-form-item label="Sex">
            <el-select v-model="form.sex" placeholder="Select sex">
              <el-option label="Male" value="male"></el-option>
              <el-option label="Female" value="female"></el-option>
            </el-select>
          </el-form-item>
  
          <!-- Role Selection -->
          <el-form-item label="Role *" prop="role">
            <el-select v-model="form.role" placeholder="Select role">
              <el-option v-for="role in roles" :key="role" :label="role" :value="role"></el-option>
            </el-select>
          </el-form-item>
  
          <!-- Conditional Fields -->
          <el-form-item label="Recipient Type *" v-if="form.role === 'recipient'" prop="recipient_type">
            <el-select v-model="form.recipient_type" placeholder="Select recipient type">
              <el-option v-for="type in recipientTypes" :key="type" :label="type" :value="type"></el-option>
            </el-select>
          </el-form-item>
  
          <el-form-item label="Donor Type *" v-if="form.role === 'donor'" prop="donor_type">
            <el-input v-model="form.donor_type" placeholder="Enter donor type" clearable />
          </el-form-item>
  
          <!-- Location -->
          <el-form-item label="Location">
            <el-input v-model="form.location" placeholder="Enter location" clearable />
          </el-form-item>
  
          <!-- Address -->
          <el-form-item label="Address">
            <el-input v-model="form.address" placeholder="Enter address" clearable />
          </el-form-item>
  
          <!-- Organization -->
          <el-form-item label="Organization Name">
            <el-input v-model="form.organization_name" placeholder="Enter organization name" clearable />
          </el-form-item>
  
          <!-- Notes -->
          <el-form-item label="Notes">
            <el-input v-model="form.notes" type="textarea" placeholder="Additional notes" clearable />
          </el-form-item>
  
          <!-- Submit Button -->
          <el-form-item>
            <el-button type="primary" @click="submitForm" :loading="loading">Register</el-button>
          </el-form-item>
        </el-form>
  
        <!-- <p class="login-link">
          Already have an account? <router-link to="/login">Login here</router-link>
        </p> -->
      </el-card>
    </div>
  </template>
  
  <script setup>
  import { ref, defineEmits } from "vue";
  import { useRouter } from "vue-router";
  import axios from "axios";
  import { ElMessage } from "element-plus";
import service from "@/utils/request";
  
  const router = useRouter();
  const loading = ref(false);
  const emit = defineEmits(['registration-complete'])
  
  console.log('Register component loaded');
  // Form state
  const form = ref({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    phone: "",
    birthday: "",
    sex: "",
    location: "",
    address: "",
    organization_name: "",
    recipient_type: "",
    donor_type: "",
    role: "",
    notes: "",
  });
  
  // Role options
  const roles = ["recipient", "donor", "foodbank"];
  const recipientTypes = ["individual", "organization"];
  
  // Validation rules
  const rules = {
    name: [{ required: true, message: "Full name is required", trigger: "blur" }],
    email: [{ required: true, message: "Valid email is required", type: "email", trigger: "blur" }],
    password: [{ required: true, message: "Password must be at least 6 characters", min: 6, trigger: "blur" }],
    password_confirmation: [
      { required: true, message: "Passwords must match", trigger: "blur" },
      { validator: (rule, value) => value === form.value.password, message: "Passwords do not match", trigger: "blur" },
    ],
    phone: [{ required: true, message: "Phone number is required", trigger: "blur" }],
    role: [{ required: true, message: "Role selection is required", trigger: "change" }],
    recipient_type: [{ required: true, message: "Recipient type is required", trigger: "change" }],
    donor_type: [{ required: true, message: "Donor type is required", trigger: "blur" }],
  };
  
  // Submit form
  const submitForm = async () => {
  try {
    loading.value = true

    // Validate form manually
    if (form.value.password !== form.value.password_confirmation) {
      ElMessage.error("Passwords do not match")
      return
    }

    await store.register(form.value)
    ElMessage.success("Registration successful! Check your email for login details.")
    emit('registration-complete') // Emit the event
  } catch (error) {
    ElMessage.error(error.response?.data?.message || "Registration failed. Try again.")
  } finally {
    loading.value = false
  }
}
  </script>
  
  <style scoped>
  /* Container Styling */
  .register-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #f5f7fa;
  }
  
  /* Card Styling */
  .register-card {
    width: 480px;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    background: #fff;
  }
  
  /* Title Styling */
  .register-title {
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 20px;
  }
  
  /* Form Input Fields */
  .el-form-item {
    margin-bottom: 16px;
  }
  
  /* Login Link */
  .login-link {
    text-align: center;
    margin-top: 15px;
    font-size: 0.9rem;
  }
  
  .login-link a {
    color: #409eff;
    text-decoration: none;
  }
  
  .login-link a:hover {
    text-decoration: underline;
  }
  </style>
  