import apiClient from './api'

// Auth APIs
export const authApi = {
  login: (credentials) => apiClient.post('/login', credentials),
  register: (userData) => apiClient.post('/register', userData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  logout: () => apiClient.post('/logout'),
  checkAuth: () => apiClient.get('/me'),
  checkEmail: (email) => apiClient.get('/check-email', { params: { email } }),
  checkUsername: (username) => apiClient.get('/check-username', { params: { username } }),
  resetPassword: (email) => apiClient.post('/forgot-password', { email }),
}

// Forum APIs
export const forumApi = {
  getAll: () => apiClient.get('/forums'),
  getForums: () => apiClient.get('/forums'),
  getAllPosts: () => apiClient.get('/forums/General'),
  getPostsByCategory: (category) => apiClient.get(`/forums/${category}`),
  searchPosts: (params) => apiClient.get('/forums/search', { params }),
  getPost: (id) => apiClient.get(`/forums/post/${id}`),
  createPost: (data) => apiClient.post('/forums/post', data),
  updatePost: (id, data) => apiClient.put(`/forums/post/${id}`, data),
  deletePost: (id) => apiClient.delete(`/forums/post/${id}`),
  likePost: (id) => apiClient.post(`/forums/post/${id}/like`),
  unlikePost: (id) => apiClient.delete(`/forums/post/${id}/like`),
}

// Comment APIs
export const commentApi = {
  getComments: (postId) => apiClient.get(`/post/${postId}/comments`),
  createComment: (data) => apiClient.post(`/post/${data.postId}/comment`, { content: data.body }),
  updateComment: (id, data) => apiClient.put(`/comment/${id}`, data),
  deleteComment: (id) => apiClient.delete(`/comment/${id}`),
  likeComment: (id) => apiClient.post(`/comment/${id}/like`),
}

// Chat APIs
export const chatApi = {
  getConversations: () => apiClient.get('/conversations'),
  getConversation: (id) => apiClient.get(`/conversation/${id}`),
  getMessages: (conversationId) => apiClient.get(`/conversation/${conversationId}/messages`),
  sendMessage: (conversationId, message) => apiClient.post(`/conversation/${conversationId}/message`, message),
  createConversation: (userId) => apiClient.post('/conversation', { userId }),
}

// Library APIs
export const libraryApi = {
  getBooks: () => apiClient.get('/library/books'),
  getArticles: () => apiClient.get('/library/articles'),
  getAuthors: () => apiClient.get('/library/authors'),
  createBook: (data) => apiClient.post('/library/book', data),
  updateBook: (id, data) => apiClient.put(`/library/book/${id}`, data),
  deleteBook: (id) => apiClient.delete(`/library/book/${id}`),
  createArticle: (data) => apiClient.post('/library/article', data),
  updateArticle: (id, data) => apiClient.put(`/library/article/${id}`, data),
  deleteArticle: (id) => apiClient.delete(`/library/article/${id}`),
}

// User APIs
export const userApi = {
  getProfile: (username) => apiClient.get(`/profile/${username}`),
  getProfileOverview: (username) => apiClient.get(`/profile/${username}/overview`),
  updateProfile: (data) => apiClient.put('/profile', data),
  deleteAccount: () => apiClient.delete('/profile'),
  getUserPosts: (username) => apiClient.get(`/profile/${username}/posts`),
  getUserComments: (username) => apiClient.get(`/profile/${username}/comments`),
  getNetwork: (userId) => apiClient.get(`/network/${userId}`),
  toggleNetwork: (userId) => apiClient.post(`/network/toggle/${userId}`),
  toggleBlock: (userId) => apiClient.post(`/block/toggle/${userId}`),
  uploadProfileImage: (formData) => apiClient.post('/profile/image', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
}

// Maps APIs
export const mapsApi = {
  getUsers: () => apiClient.get('/maps/users'),
  getUsersByRegion: (region) => apiClient.get('/maps/users', { params: { region } }),
}

// Admin APIs
export const adminApi = {
  getStats: () => apiClient.get('/admin/stats'),
  getUsers: () => apiClient.get('/admin/users'),
  updateUser: (id, data) => apiClient.put(`/admin/user/${id}`, data),
  deleteUser: (id) => apiClient.delete(`/admin/user/${id}`),
  getReportedContent: () => apiClient.get('/admin/reports'),
}
