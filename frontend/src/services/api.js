import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
})

export async function extractApiErrorMessage(responseData) {
  if (!responseData) {
    return ''
  }

  if (responseData instanceof Blob) {
    try {
      const text = await responseData.text()
      const parsed = JSON.parse(text)
      return parsed?.error || parsed?.message || text
    } catch {
      return await responseData.text()
    }
  }

  if (typeof responseData === 'string') {
    try {
      const parsed = JSON.parse(responseData)
      return parsed?.error || parsed?.message || responseData
    } catch {
      return responseData
    }
  }

  return responseData?.error || responseData?.message || ''
}

export function downloadBlobFile(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

export function extractFilenameFromContentDisposition(contentDisposition, fallbackFilename) {
  if (typeof contentDisposition !== 'string' || contentDisposition.trim() === '') {
    return fallbackFilename
  }

  const match = contentDisposition.match(/filename="?([^";]+)"?/i)
  return match?.[1] || fallbackFilename
}

export function downloadResponseBlob(response, fallbackFilename) {
  const contentDisposition = response?.headers?.['content-disposition'] || ''
  const filename = extractFilenameFromContentDisposition(contentDisposition, fallbackFilename)
  const blob = new Blob([response.data], { type: 'application/json' })

  downloadBlobFile(blob, filename)

  return filename
}

// Request interceptor for auth token
apiClient.interceptors.request.use(
  (config) => {
    // Only set Content-Type to JSON if it's not already set (FormData will set it automatically)
    if (!config.headers['Content-Type'] && !(config.data instanceof FormData)) {
      config.headers['Content-Type'] = 'application/json'
    }
    
    const authData = localStorage.getItem('auth-storage')
    if (authData) {
      const { state } = JSON.parse(authData)
      if (state?.user?.token) {
        config.headers.Authorization = `Bearer ${state.user.token}`
      }
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Clear auth and redirect to login
      localStorage.removeItem('auth-storage')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default apiClient
