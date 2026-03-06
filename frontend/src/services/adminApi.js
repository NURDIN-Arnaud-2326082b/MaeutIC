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
