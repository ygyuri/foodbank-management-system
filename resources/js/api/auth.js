import request from '@/utils/request'

export function login(data) {
  return request({
    url: '/auth/login', // Calls Laravel backend
    method: 'post',
    data: data // Sends { email, password }
  })
}

export function getInfo(token) {
  return request({
    url: '/user',
    method: 'get'
  })
}

export function logout() {
  return request({
    url: '/auth/logout',
    method: 'post'
  })
}

export function csrf() {
  return request({
    url: '/sanctum/csrf-cookie',
    method: 'get'
  })
}
