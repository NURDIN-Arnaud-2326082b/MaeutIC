import api from './api';

export const conversationApi = {
  // Récupère toutes les conversations de l'utilisateur
  getConversations: async () => {
    const response = await api.get('/conversations');
    return response.data;
  },

  // Récupère les messages d'une conversation
  getMessages: async (conversationId) => {
    const response = await api.get(`/conversation/${conversationId}/messages`);
    return response.data;
  },

  // Envoie un message dans une conversation
  sendMessage: async (conversationId, content) => {
    const response = await api.post(`/conversation/${conversationId}/message`, { content });
    return response.data;
  },

  // Trouve ou crée une conversation avec un utilisateur
  findOrCreateConversation: async (userId) => {
    const response = await api.get(`/conversation/with/${userId}`);
    return response.data;
  },
};
