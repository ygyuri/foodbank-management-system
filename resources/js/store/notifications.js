import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import service from '@/utils/request' // Axios instance

export const useNotificationStore = defineStore('notifications', () => {
  // 🔄 State
  const notifications = ref([])
  const loading = ref(false)
  const error = ref(null)
  const isAdmin = ref(false) // Determine if the user is admin

  // 🟢 Actions
  const fetchNotifications = async () => {
    loading.value = true
    error.value = null
    try {
      // Choose endpoint based on user role
      const endpoint = isAdmin.value ? '/notifications/admin' : '/notifications'
      const response = await service.get(endpoint)
      notifications.value = response.data.data // Use .data if paginated
    } catch (err) {
      console.error('Error fetching notifications:', err)
      error.value = 'Failed to load notifications'
    } finally {
      loading.value = false
    }
  }

  const addNotification = (notification) => {
    notifications.value.push(notification)
  }

  const removeNotification = (id) => {
    notifications.value = notifications.value.filter((n) => n.id !== id)
  }

  const clearNotifications = () => {
    notifications.value = []
  }

  const markAsRead = async (id) => {
    try {
      await service.post(`/notifications/${id}/read`)
      const notification = notifications.value.find((n) => n.id === id)
      if (notification) {
        notification.read_at = new Date().toISOString() // Update locally
      }
    } catch (err) {
      console.error('Error marking notification as read:', err)
    }
  }

  // 🔍 Computed Properties
  const unreadNotifications = computed(() => notifications.value.filter((n) => !n.read_at))

  const notificationCount = computed(() => unreadNotifications.value.length)

  return {
    notifications,
    loading,
    error,
    isAdmin,
    fetchNotifications,
    addNotification,
    removeNotification,
    clearNotifications,
    markAsRead,
    unreadNotifications,
    notificationCount
  }
})
