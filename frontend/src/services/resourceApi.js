import api from './api'

/**
 * Get all resources for a specific page
 */
export const getResources = async (page) => {
  const response = await api.get(`/resources/${page}`)
  return response.data
}

/**
 * Create a new resource
 */
export const createResource = async (page, data) => {
  const response = await api.post(`/resources/${page}`, data)
  return response.data
}

/**
 * Update a resource
 */
export const updateResource = async (page, id, data) => {
  const response = await api.put(`/resources/${page}/${id}`, data)
  return response.data
}

/**
 * Delete a resource
 */
export const deleteResource = async (page, id) => {
  const response = await api.delete(`/resources/${page}/${id}`)
  return response.data
}
