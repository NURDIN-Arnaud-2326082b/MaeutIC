import api from './api';

export const chatApi = {
  // Récupère les messages du chat global
  getGlobalMessages: async () => {
    const response = await api.get('/chat/messages');
    return response.data;
  },

  // Envoie un message dans le chat global
  sendGlobalMessage: async (text) => {
    const response = await api.post('/chat/send', { text });
    return response.data;
  },
};
