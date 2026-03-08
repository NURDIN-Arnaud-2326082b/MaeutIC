import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { conversationApi } from '../services/conversationApi';

export default function PrivateMessages() {
  const { data: conversations, isLoading, error } = useQuery({
    queryKey: ['conversations'],
    queryFn: conversationApi.getConversations,
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-gray-600">Chargement...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-red-600">Erreur lors du chargement des conversations</div>
      </div>
    );
  }

  const accessibleConversations = conversations?.filter(c => !c.isBlocked) || [];

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">Messages privés</h1>

      {accessibleConversations.length === 0 ? (
        <div className="bg-white rounded-lg shadow p-8 text-center text-gray-600">
          <p>Aucune conversation</p>
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow">
          <ul className="divide-y divide-gray-200">
            {accessibleConversations.map((conversation) => (
              <li key={conversation.id}>
                <Link
                  to={`/messages/${conversation.id}`}
                  className="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors"
                >
                  {/* Avatar */}
                  <div className="flex-shrink-0">
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
                  </div>

                  {/* Infos conversation */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between">
                      <h3 className="text-lg font-semibold text-gray-900 truncate">
                        {conversation.otherUser.username}
                      </h3>
                      {conversation.lastMessage && (
                        <span className="text-sm text-gray-500 ml-2">
                          {conversation.lastMessage.sentAt}
                        </span>
                      )}
                    </div>
                    {conversation.lastMessage && (
                      <p className="text-sm text-gray-600 truncate mt-1">
                        {conversation.lastMessage.content}
                      </p>
                    )}
                  </div>

                  {/* Bouton Voir */}
                  <div className="flex-shrink-0">
                    <span className="text-blue-600 hover:text-blue-800 font-medium">
                      Voir
                    </span>
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
