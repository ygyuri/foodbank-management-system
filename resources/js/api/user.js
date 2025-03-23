import request from '@/utils/request'
import Resource from '@/api/resource'

class UserResource extends Resource {
  constructor() {
    super('users') // Inheriting base URL for users API
  }

  // Create user (uses inherited store method)
  createUser(userData) {
    return this.store(userData)
  }

  // Update user
  updateUser(userId, userData) {
    return this.update(userId, userData)
  }

  // Fetch user permissions
  permissions(id) {
    return request({
      url: `/${this.uri}/${id}/permissions`,
      method: 'get'
    })
  }

  // Update user permissions
  updatePermission(id, permissions) {
    return request({
      url: `/${this.uri}/${id}/permissions`,
      method: 'put',
      data: permissions
    })
  }

  // Fetch user logs
  logs(id, params) {
    return request({
      url: `/${this.uri}/${id}/logs`,
      method: 'get',
      params: params
    })
  }
}

export { UserResource as default }
