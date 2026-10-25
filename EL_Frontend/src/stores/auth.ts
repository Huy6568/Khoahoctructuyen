import { defineStore } from 'pinia'
import api from '../../src/api/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user_info') || 'null') as any | null,
    token: localStorage.getItem('access_token') || null,
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  
  actions: {
    async register(payload: { name: string, email: string, password: string, password_confirmation: string }) {
      const response = await api.post('/register', payload)
      const data = response.data?.data ?? response.data ?? {}

      const token = data.access_token ?? data.token ?? data.authorization?.token ?? null
      const user = data.user ?? data.profile ?? null

      this.token = token
      this.user = user

      if (token) {
        localStorage.setItem('access_token', token)
      }

      if (user) {
        localStorage.setItem('user_info', JSON.stringify(user))
      }

      return response
    },

    async login(credentials: { email: string, password: string }) {
      const response = await api.post('/login', credentials)
      const data = response.data?.data ?? response.data ?? {}

      const token = data.access_token ?? data.token ?? data.authorization?.token ?? null
      const user = data.user ?? data.profile ?? null

      this.token = token
      this.user = user

      if (token) {
        localStorage.setItem('access_token', token)
      }

      if (user) {
        localStorage.setItem('user_info', JSON.stringify(user))
      }

      return response
    },
    
    async logout() {
      // Gọi API xóa token trên server
      await api.post('/logout')
      
      // Xóa ở client
      this.token = null
      this.user = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('user_info')
    },
    
    async fetchUser() {
      // Hàm này chạy khi F5 web để lấy lại thông tin user từ token cũ
      if (this.token) {
        try {
          const response = await api.get('/user/me')
          this.user = response.data.data
          localStorage.setItem('user_info', JSON.stringify(this.user))
        } catch (error) {
          // Nếu token hết hạn, interceptor trong axios.ts sẽ tự động xóa token
          this.token = null
          this.user = null
          localStorage.removeItem('user_info')
        }
      }
    }
  }
})