import api from './api';

/**
 * Get network status with a user (connected, incoming_request, outgoing_request, none)
 */
export const getNetworkStatus = async (userId) => {
  const response = await api.get(`/network/status/${userId}`);
  return response.data;
};

/**
 * Toggle network status (add/remove from network, send/cancel request)
 */
export const toggleNetwork = async (userId) => {
  const response = await api.post(`/network/toggle/${userId}`);
  return response.data;
};

/**
 * Get network members for a user
 */
export const getNetwork = async (userId) => {
  const response = await api.get(`/network/${userId}`);
  return response.data;
};

/**
 * Get notifications for current user
 */
export const getNotifications = async () => {
  const response = await api.get('/notifications');
  return response.data;
};

/**
 * Accept a network request notification
 */
export const acceptNetworkRequest = async (notificationId) => {
  const response = await api.post(`/notifications/accept/${notificationId}`);
  return response.data;
};

/**
 * Decline a network request notification
 */
export const declineNetworkRequest = async (notificationId) => {
  const response = await api.post(`/notifications/decline/${notificationId}`);
  return response.data;
};

/**
 * Clear all notifications
 */
export const clearAllNotifications = async () => {
  const response = await api.post('/notifications/clear-all');
  return response.data;
};

/**
 * Toggle block status (block/unblock user)
 */
export const toggleBlock = async (userId) => {
  const response = await api.post(`/block/toggle/${userId}`);
  return response.data;
};
