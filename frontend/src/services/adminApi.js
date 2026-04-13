import api from './api'

/**
 * Get all tags, optionally filtered by search query
 */
export const getTags = async (search = '') => {
  const response = await api.get('/admin/tags', {
    params: search ? { search } : {}
  })
  return response.data
}

/**
 * Create a new tag
 */
export const createTag = async (name) => {
  const response = await api.post('/admin/tags', { name })
  return response.data
}

/**
 * Update a tag
 */
export const updateTag = async (id, name) => {
  const response = await api.put(`/admin/tags/${id}`, { name })
  return response.data
}

/**
 * Delete a tag
 */
export const deleteTag = async (id) => {
  const response = await api.delete(`/admin/tags/${id}`)
  return response.data
}

/**
 * Get moderation reports
 */
export const getReports = async (status = '') => {
  const response = await api.get('/admin/reports', {
    params: status ? { status } : {}
  })
  return response.data
}

/**
 * Get posts flagged with sensitive content
 */
export const getSensitivePosts = async () => {
  const response = await api.get('/admin/sensitive-posts')
  return response.data
}

/**
 * Update moderation report status
 */
export const processReport = async (id, status, adminNote = '') => {
  const response = await api.patch(`/admin/reports/${id}`, { status, adminNote })
  return response.data
}

/**
 * Apply an automated moderation action from a report
 */
export const autoActionReport = async (id, action, adminNote = '') => {
  const response = await api.post(`/admin/reports/${id}/auto-action`, { action, adminNote })
  return response.data
}
