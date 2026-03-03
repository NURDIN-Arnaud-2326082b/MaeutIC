import api from './api'

/**
 * Request a password reset link
 */
export const requestPasswordReset = async (email) => {
  const response = await api.post('/forgot-password/request', { email })
  return response.data
}

/**
 * Verify if a reset token is valid
 */
export const verifyResetToken = async (token) => {
  const response = await api.get(`/forgot-password/verify/${token}`)
  return response.data
}

/**
 * Reset password with token
 */
export const resetPassword = async (token, password) => {
  const response = await api.post(`/forgot-password/reset/${token}`, { password })
  return response.data
}
