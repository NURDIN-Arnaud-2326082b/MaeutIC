import { create } from 'zustand'

export const useAuthStore = create((set) => ({
  user: null,
  isAuthenticated: false,
  
  login: (userData) => {
    localStorage.setItem('auth-storage', JSON.stringify({ state: { user: userData } }))
    set({ user: userData, isAuthenticated: true })
  },
  logout: () => {
    localStorage.removeItem('auth-storage')
    set({ user: null, isAuthenticated: false })
  },
  updateUser: (userData) => set({ user: userData }),
  
  // Initialize from localStorage
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
