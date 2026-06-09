import api from './api'

/**
 * Get all tags, optionally filtered by search query
 */
export const getTags = async (search = '') => {
    const response = await api.get('/admin/tags', {
        params: search ? {search} : {}
    })
    return response.data
}

/**
 * Create a new tag
 */
export const createTag = async (name) => {
    const response = await api.post('/admin/tags', {name})
    return response.data
}

/**
 * Update a tag
 */
export const updateTag = async (id, name) => {
    const response = await api.put(`/admin/tags/${id}`, {name})
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
        params: status ? {status} : {}
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
 * Get RGPD data-access requests for admins
 */
export const getDataAccessRequests = async (status = '') => {
    const response = await api.get('/admin/data-access-requests', {
        params: status ? {status} : {}
    })
    return response.data
}

/**
 * Process RGPD data-access request status
 */
export const processDataAccessRequest = async (id, status, adminNote = '') => {
    const response = await api.patch(`/admin/data-access-requests/${id}`, {status, adminNote})
    return response.data
}

/**
 * Fetch JSON export for one RGPD request
 */
export const getDataAccessRequestData = async (id) => {
    const response = await api.get(`/admin/data-access-requests/${id}/data`)
    return response.data
}

/**
 * Update moderation report status
 */
export const processReport = async (id, status, adminNote = '') => {
    const response = await api.patch(`/admin/reports/${id}`, {status, adminNote})
    return response.data
}

/**
 * Apply an automated moderation action from a report
 */
export const autoActionReport = async (id, action, adminNote = '') => {
    const response = await api.post(`/admin/reports/${id}/auto-action`, {action, adminNote})
    return response.data
}

/**
 * Get all banned users
 */
export const getBannedUsers = async () => {
    const response = await api.get('/admin/banned-users')
    return response.data
}

/**
 * Search users (for admin management)
 */
export const searchUsers = async (query) => {
    const response = await api.get('/admin/search-users', {
        params: {q: query}
    })
    return response.data
}

/**
 * Ban a user
 */
export const banUser = async (id) => {
    const response = await api.post(`/admin/users/${id}/ban`)
    return response.data
}

/**
 * Unban a user
 */
export const unbanUser = async (id) => {
    const response = await api.post(`/admin/users/${id}/unban`)
    return response.data
}

/**
 * Change a user's role
 */
export const changeUserRole = async (id, userType) => {
    const response = await api.post(`/admin/users/${id}/role`, {userType})
    return response.data
}

/**
 * Fetch full profile data of a user for admin editing
 */
export const getFullProfileForEdit = async (id) => {
    const response = await api.get(`/admin/users/${id}/full-profile`)
    return response.data
}

/**
 * Update a user's profile as an admin (using the existing user update route + targetUserId)
 */
export const updateAnyUserProfile = async (targetUserId, formData) => {
    const currentDataStr = formData.get('data')
    if (currentDataStr) {
        const currentData = JSON.parse(currentDataStr)
        currentData.targetUserId = targetUserId
        formData.set('data', JSON.stringify(currentData))
    }

    const response = await api.post(`/user/update`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
    return response.data
}