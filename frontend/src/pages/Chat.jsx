import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { useState, useEffect, useRef } from 'react';
import { conversationApi } from '../services/conversationApi';
import { chatApi } from '../services/chatApi';
import { useAuthStore } from '../store';

export default function Chat() {
  const [showGlobalChat, setShowGlobalChat] = useState(true);
  const [messageText, setMessageText] = useState('');
  const messagesEndRef = useRef(null);
  const queryClient = useQueryClient();
  const user = useAuthStore((state) => state.user);

  // Récupère les conversations
  const { data: conversations, isLoading: conversationsLoading, error: conversationsError } = useQuery({
    queryKey: ['conversations'],
    queryFn: conversationApi.getConversations,
  });

  // Récupère les messages du chat global avec polling toutes les 2 secondes
  const { data: globalMessages, isLoading: messagesLoading } = useQuery({
    queryKey: ['globalMessages'],
    queryFn: chatApi.getGlobalMessages,
    refetchInterval: showGlobalChat ? 2000 : false, // Polling seulement si le chat global est affiché
    enabled: showGlobalChat,
  });

  // Mutation pour envoyer un message global
  const sendMessageMutation = useMutation({
    mutationFn: (text) => chatApi.sendGlobalMessage(text),
    onSuccess: () => {
      setMessageText('');
      queryClient.invalidateQueries(['globalMessages']);
    },
  });

  // Auto-scroll vers le bas quand de nouveaux messages arrivent
  useEffect(() => {
    if (messagesEndRef.current && showGlobalChat) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [globalMessages, showGlobalChat]);

  const handleSubmit = (e) => {
    e.preventDefault();
    const trimmedText = messageText.trim();
    if (trimmedText) {
      sendMessageMutation.mutate(trimmedText);
    }
  };

  const accessibleConversations = conversations?.filter(c => !c.isBlocked) || [];

  return (
    <div className="flex-1 flex flex-row w-full text-gray-700 min-h-0 max-h-[calc(100vh-80px)]">
      {/* Chat Navigation */}
      <div className="bg-white m-6 rounded-lg p-4 w-52 shadow-xl overflow-y-auto flex-shrink-0">
        {/* Salons */}
        <div className="inline-block">
          <h2 className="text-2xl">Salons</h2>
        </div>
        <div className="flex flex-col ml-2 mb-2">
          <button
            onClick={() => setShowGlobalChat(true)}
            className={`p-1 w-full text-left hover:bg-blue-50 hover:text-blue-900 ${showGlobalChat ? 'bg-blue-50 text-blue-900' : ''}`}
          >
            # General
          </button>
        </div>

        {/* Conversations */}
        <div className="inline-block mt-4">
          <h2 className="text-2xl">Conversations</h2>
        </div>
        <div className="ml-2 mb-2">
          {conversationsLoading ? (
            <div className="py-4 text-gray-500">Chargement...</div>
          ) : conversationsError ? (
            <div className="py-4 text-red-500">Erreur</div>
          ) : accessibleConversations.length === 0 ? (
            <div className="py-4 text-gray-500">Aucune conversation.</div>
          ) : (
            accessibleConversations.map((conversation) => (
              <li key={conversation.id} className="py-4 flex items-center justify-between list-none">
                <Link
                  to={`/messages/${conversation.id}`}
                  className="flex items-center gap-3 p-1 w-full hover:bg-blue-50 hover:text-blue-900"
                >
                  {conversation.otherUser.profileImage ? (
                    <img
                      src={conversation.otherUser.profileImage}
                      alt={conversation.otherUser.username}
                      className="w-10 h-10 rounded-full object-cover"
                    />
                  ) : (
                    <div className="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                      <span className="text-gray-600 font-semibold">
                        {conversation.otherUser.username[0].toUpperCase()}
                      </span>
                    </div>
                  )}
                  <span className="font-semibold">{conversation.otherUser.username}</span>
                </Link>
              </li>
            ))
          )}
        </div>
      </div>

      {/* Chat wrapper */}
      <div className="bg-white flex flex-col shadow-xl p-2 rounded-lg flex-1 m-6 ml-0 min-h-0">
        {showGlobalChat ? (
          <>
            {/* Chat header */}
            <h1 className="flex flex-col ml-2 mb-2">Live Chat</h1>

            {/* Chat messages */}
            <div className="flex-1 overflow-y-auto mb-2 px-2 min-h-0">
              {messagesLoading ? (
                <div className="text-gray-500">Chargement...</div>
              ) : !globalMessages || globalMessages.length === 0 ? (
                <div className="text-gray-500">Aucun message.</div>
              ) : (
                globalMessages.map((msg, index) => (
                  <div key={index} className="flex justify-start mb-4">
                    <div className="px-4 py-2">
                      <div className="flex flex-row items-center">
                        {msg.sender ? (
                          <Link
                            to={`/profile/${msg.sender.username}`}
                            className="flex flex-row items-center mr-3"
                          >
                            <img
                              src={msg.sender.profileImage ? `/profile_images/${msg.sender.profileImage}` : '/images/default-profile.png'}
                              alt="Profil"
                              className="w-10 h-10 mr-3 rounded-full"
                            />
                            <div className="text-sm font-semibold">{msg.sender.username}</div>
                          </Link>
                        ) : (
                          <div className="flex flex-row items-center mr-3">
                            <img src="/images/default-profile.png" alt="Profil" className="w-10 h-10 mr-3 rounded-full" />
                            <div className="text-sm font-semibold text-gray-500">Ancien utilisateur</div>
                          </div>
                        )}
                        <div className="text-xs text-gray-400 mt-1">{msg.sentAt}</div>
                      </div>
                      <div className="ml-[52px]">{msg.content}</div>
                    </div>
                  </div>
                ))
              )}
              <div ref={messagesEndRef} />
            </div>

            {/* Message form */}
            <form onSubmit={handleSubmit} className="flex gap-2">
              <input
                type="text"
                value={messageText}
                onChange={(e) => setMessageText(e.target.value)}
                placeholder="Type your message..."
                required
                className="flex-1 px-2 py-1 rounded border"
                disabled={sendMessageMutation.isPending}
              />
              <button
                type="submit"
                disabled={!messageText.trim() || sendMessageMutation.isPending}
                className="px-4 py-1 rounded text-white bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed"
              >
                Envoyer
              </button>
            </form>
          </>
        ) : null}
      </div>
    </div>
  );
}
