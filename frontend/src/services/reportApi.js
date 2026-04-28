import api from './api'

export const createReport = async (payload) => {
  const response = await api.post('/reports', payload)
  return response.data
}
