import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useParams, useNavigate } from 'react-router-dom';
import { useState, useEffect, useRef } from 'react';
import { conversationApi } from '../services/conversationApi';
import { useAuthStore } from '../store';

export default function Conversation() {
  const { conversationId } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const messagesEndRef = useRef(null);
  const [messageContent, setMessageContent] = useState('');
  const user = useAuthStore((state) => state.user);

  // Récupère les messages avec polling toutes les 2 secondes
  const { data, isLoading, error } = useQuery({
    queryKey: ['conversation', conversationId],
    queryFn: () => conversationApi.getMessages(conversationId),
    refetchInterval: 2000, // Polling toutes les 2 secondes
  });

  // Mutation pour envoyer un message
  const sendMessageMutation = useMutation({
    mutationFn: (content) => conversationApi.sendMessage(conversationId, content),
    onSuccess: () => {
      setMessageContent('');
      queryClient.invalidateQueries(['conversation', conversationId]);
      queryClient.invalidateQueries(['conversations']);
    },
  });

  // Auto-scroll vers le bas quand de nouveaux messages arrivent
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [data?.messages]);

  const handleSubmit = (e) => {
    e.preventDefault();
    const trimmedContent = messageContent.trim();
    if (trimmedContent) {
      sendMessageMutation.mutate(trimmedContent);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-gray-600">Chargement...</div>
      </div>
    );
  }

  if (error) {
    if (error.response?.status === 403) {
      return (
        <div className="flex flex-col items-center justify-center min-h-screen">
          <div className="text-red-600 mb-4">Conversation inaccessible à cause d'un blocage.</div>
          <button
            onClick={() => navigate('/chat')}
            className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          >
            Retour aux conversations
          </button>
        </div>
      );
    }
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-red-600">Erreur lors du chargement de la conversation</div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8 max-w-4xl">
      {/* En-tête de la conversation */}
      <div className="bg-white rounded-t-lg shadow p-4 flex items-center gap-4 border-b">
        <button
          onClick={() => navigate('/chat')}
          className="text-gray-600 hover:text-gray-900"
        >
          ← Retour
        </button>
        
        {data.otherUser.profileImage ? (
          <img
            src={data.otherUser.profileImage}
            alt={data.otherUser.username}
            className="w-10 h-10 rounded-full object-cover"
          />
        ) : (
          <div className="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
            <span className="text-gray-600 font-semibold">
              {data.otherUser.username[0].toUpperCase()}
            </span>
          </div>
        )}
        
        <h1 className="text-xl font-bold">{data.otherUser.username}</h1>
      </div>

      {/* Zone des messages */}
      <div className="bg-white shadow px-4 py-6 h-[500px] overflow-y-auto">
        <div className="flex flex-col gap-4">
          {data.messages.length === 0 ? (
            <div className="text-center text-gray-500">Aucun message pour le moment</div>
          ) : (
            data.messages.map((message) => (
              <div
                key={message.id}
                className={`flex ${message.isOwn ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className={`max-w-[70%] rounded-lg px-4 py-2 ${
                    message.isOwn
                      ? 'bg-blue-100 text-gray-900'
                      : 'bg-gray-100 text-gray-900'
                  }`}
                >
                  {!message.isOwn && (
                    <div className="font-semibold text-sm mb-1">{message.sender.username}</div>
                  )}
                  <div className="break-words">{message.content}</div>
                  <div className="text-xs text-gray-600 mt-1">{message.sentAt}</div>
                </div>
              </div>
            ))
          )}
          <div ref={messagesEndRef} />
        </div>
      </div>

      {/* Formulaire d'envoi de message */}
      <form onSubmit={handleSubmit} className="bg-white rounded-b-lg shadow p-4 border-t">
        <div className="flex gap-2">
          <input
            type="text"
            value={messageContent}
            onChange={(e) => setMessageContent(e.target.value)}
            placeholder="Écrivez votre message..."
            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            disabled={sendMessageMutation.isPending}
          />
          <button
            type="submit"
            disabled={!messageContent.trim() || sendMessageMutation.isPending}
            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            {sendMessageMutation.isPending ? 'Envoi...' : 'Envoyer'}
          </button>
        </div>
      </form>
    </div>
  );
}
