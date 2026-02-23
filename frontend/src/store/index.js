import { create } from 'zustand'
import { authApi } from '../services/apis'

export const useAuthStore = create((set) => ({
  user: null,
  isAuthenticated: false,
  isLoading: true,
  
  login: async (credentials) => {
    try {
      const response = await authApi.login(credentials)
      const userData = response.data.user
      localStorage.setItem('auth-storage', JSON.stringify({ state: { user: userData } }))
      set({ user: userData, isAuthenticated: true })
      return { success: true }
    } catch (error) {
      return { 
        success: false, 
        error: error.response?.data?.error || 'Erreur de connexion' 
      }
    }
  },
  
  logout: async () => {
    try {
      await authApi.logout()
    } catch (error) {
      console.error('Logout error:', error)
    }
    localStorage.removeItem('auth-storage')
    set({ user: null, isAuthenticated: false })
  },
  
  updateUser: (userData) => set({ user: userData }),
  
  // Vérifier l'authentification avec le backend
  checkAuth: async () => {
    set({ isLoading: true })
    try {
      const response = await authApi.checkAuth()
      if (response.data.user) {
        localStorage.setItem('auth-storage', JSON.stringify({ state: { user: response.data.user } }))
        set({ user: response.data.user, isAuthenticated: true, isLoading: false })
      } else {
        localStorage.removeItem('auth-storage')
        set({ user: null, isAuthenticated: false, isLoading: false })
      }
    } catch (error) {
      console.error('Check auth error:', error)
      localStorage.removeItem('auth-storage')
      set({ user: null, isAuthenticated: false, isLoading: false })
    }
  },
  
  // Initialize from localStorage (deprecated - use checkAuth instead)
  initAuth: () => {
    const stored = localStorage.getItem('auth-storage')
    if (stored) {
      const { state } = JSON.parse(stored)
      if (state?.user) {
        set({ user: state.user, isAuthenticated: true })
      }
    }
  },
}))

export const useForumStore = create((set) => ({
  selectedCategory: 'General',
  searchQuery: '',
  searchFilters: {
    type: 'all',
    date: 'all',
    sort: 'recent',
  },
  
  setCategory: (category) => set({ selectedCategory: category }),
  setSearchQuery: (query) => set({ searchQuery: query }),
  setSearchFilters: (filters) => set((state) => ({ 
    searchFilters: { ...state.searchFilters, ...filters } 
  })),
  resetFilters: () => set({ 
    searchQuery: '',
    searchFilters: { type: 'all', date: 'all', sort: 'recent' }
  }),
}))

export const useChatStore = create((set) => ({
  conversations: [],
  activeConversation: null,
  messages: [],
  
  setConversations: (conversations) => set({ conversations }),
  setActiveConversation: (conversation) => set({ activeConversation: conversation }),
  addMessage: (message) => set((state) => ({ 
    messages: [...state.messages, message] 
  })),
  setMessages: (messages) => set({ messages }),
}))

export const useNotificationStore = create((set) => ({
  notifications: [],
  unreadCount: 0,
  
  addNotification: (notification) => set((state) => ({
    notifications: [notification, ...state.notifications],
    unreadCount: state.unreadCount + 1,
  })),
  markAsRead: (notificationId) => set((state) => ({
    notifications: state.notifications.map(n => 
      n.id === notificationId ? { ...n, read: true } : n
    ),
    unreadCount: Math.max(0, state.unreadCount - 1),
  })),
  clearNotifications: () => set({ notifications: [], unreadCount: 0 }),
}))
