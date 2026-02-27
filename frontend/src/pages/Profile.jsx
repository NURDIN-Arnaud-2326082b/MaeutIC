import { useState, useRef, useEffect } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { userApi } from '../services/apis'
import { conversationApi } from '../services/conversationApi'
import { useAuthStore } from '../store'
import { getNetworkStatus } from '../services/networkApi'

export default function Profile() {
  const { username } = useParams()
  const navigate = useNavigate()
  const { user: currentUser, isAuthenticated } = useAuthStore()
  const [activeTab, setActiveTab] = useState('overview')
  const [showActionsMenu, setShowActionsMenu] = useState(false)
  const actionsMenuRef = useRef(null)
  const queryClient = useQueryClient()

  const { data: profileData, isLoading } = useQuery({
    queryKey: ['profile', username],
    queryFn: () => userApi.getProfile(username),
  })

  const isOwnProfile = currentUser?.username === username

  const { data: networkStatusData } = useQuery({
    queryKey: ['networkStatus', profileData?.data?.id],
    queryFn: () => getNetworkStatus(profileData?.data?.id),
    enabled: !!profileData?.data?.id && !isOwnProfile && isAuthenticated,
  })

  const networkStatus = networkStatusData?.status || 'none'

  const { data: networkData } = useQuery({
    queryKey: ['network', profileData?.data?.id],
    queryFn: () => userApi.getNetwork(profileData?.data?.id),
    enabled: !!profileData?.data?.id,
  })

  const { data: overviewData } = useQuery({
    queryKey: ['profile-overview', username],
    queryFn: () => userApi.getProfileOverview(username),
    enabled: activeTab === 'overview',
  })

  const { data: postsData } = useQuery({
    queryKey: ['profile-posts', username],
    queryFn: () => userApi.getUserPosts(username),
    enabled: activeTab === 'posts',
  })

  const { data: commentsData } = useQuery({
    queryKey: ['profile-comments', username],
    queryFn: () => userApi.getUserComments(username),
    enabled: activeTab === 'comments',
  })

  const toggleNetworkMutation = useMutation({
    mutationFn: (userId) => userApi.toggleNetwork(userId),
    onSuccess: () => {
      // Invalidate queries to refresh data
      queryClient.invalidateQueries(['networkStatus', profileData?.data?.id])
      queryClient.invalidateQueries(['profile', username])
      queryClient.invalidateQueries(['network', profileData?.data?.id])
      // Reload to ensure full sync
      window.location.reload()
    },
  })

  const toggleBlockMutation = useMutation({
    mutationFn: (userId) => userApi.toggleBlock(userId),
    onSuccess: () => {
      window.location.reload()
    },
  })

  const deleteAccountMutation = useMutation({
    mutationFn: () => userApi.deleteAccount(),
    onSuccess: () => {
      navigate('/')
    },
  })

  const startConversationMutation = useMutation({
    mutationFn: (userId) => conversationApi.findOrCreateConversation(userId),
    onSuccess: (data) => {
      navigate(`/messages/${data.conversationId}`)
    },
  })

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (actionsMenuRef.current && !actionsMenuRef.current.contains(event.target)) {
        setShowActionsMenu(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  if (isLoading) {
    return (
      <div className="flex-1 flex items-center justify-center">
        <div className="text-center py-12">Chargement...</div>
      </div>
    )
  }

  if (!profileData?.data) {
    return (
      <div className="flex-1 flex items-center justify-center">
        <div className="text-center py-12 text-gray-500">Utilisateur non trouvé</div>
      </div>
    )
  }

  const user = profileData.data
  const network = networkData?.data || []
  
  // Blocage mutuel ou unilatéral
  const isMutuallyBlocked = !isOwnProfile && (user.isBlocked || user.blockedByThem)
  const showNetwork = !isMutuallyBlocked || isOwnProfile

  return (
    <div className="flex-1">
      {/* Header Card */}
      <div className="flex items-center bg-white/45 p-8 pr-0 rounded-lg backdrop-blur-sm shadow-xl max-w-3xl mx-auto mt-8">
        <div className="w-36 h-36 rounded-full flex items-center justify-center mr-8 overflow-hidden bg-white border border-gray-300">
          <img
            src={user.profileImage || '/images/default-profile.png'}
            alt="Photo de profil"
            className="w-36 h-36 object-cover rounded-full"
          />
        </div>

        <div className="text-indigo-900">
          <h2 className="m-0 text-3xl font-bold">{user.username}</h2>
          <div className="text-xl mb-2">
            {user.firstName} {user.lastName}
          </div>
          {user.researcherTitle && (
            <div className="text-indigo-700 font-semibold text-lg mb-1 capitalize">
              {user.researcherTitle}
            </div>
          )}
          <div className="text-indigo-500 text-base">
            {user.affiliationLocation}
            {user.specialization && ` · ${user.specialization}`}
          </div>

          {/* Message de blocage */}
          {isMutuallyBlocked && (
            <div className="mt-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">
              Ce profil n'est pas accessible.
            </div>
          )}

          <div className="mt-4">
            {/* Réseau - caché si bloqué mutuellement */}
            {showNetwork && (
              <div className="mb-3">
                <div className="flex items-center justify-start gap-3">
                  <div className="text-sm text-gray-600 mr-2">Réseau</div>
                  {network.length > 0 ? (
                    <div className="flex items-center gap-1">
                      <div className="flex items-center -space-x-2">
                        {network.slice(0, 3).map((member, idx) => (
                          <Link
                            key={idx}
                            to={`/profile/${member.username}`}
                            className="w-8 h-8 rounded-full border-2 border-white overflow-hidden"
                          >
                            <img
                              src={member.profileImage || '/images/default-profile.png'}
                              alt={member.username}
                              className="w-full h-full object-cover"
                            />
                          </Link>
                        ))}
                      </div>
                      <div className="text-sm text-gray-600 ml-1 whitespace-nowrap">
                        ({network.length})
                      </div>
                    </div>
                  ) : (
                    <div className="text-sm text-gray-400">Aucun membre connecté.</div>
                  )}
                </div>
              </div>
            )}

            {/* Boutons d'action */}
            <div className="flex gap-2 items-center">
              {isOwnProfile ? (
                <>
                  <Link
                    to="/profile-edit"
                    className="inline-block px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition"
                  >
                    Éditer mon profil
                  </Link>
                  <button
                    onClick={() => {
                      if (confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
                        deleteAccountMutation.mutate()
                      }
                    }}
                    className="inline-block px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition"
                  >
                    Supprimer mon compte
                  </button>
                </>
              ) : isAuthenticated ? (
                isMutuallyBlocked ? (
                  /* Si bloqué mutuellement, afficher seulement Débloquer si c'est moi qui ai bloqué */
                  user.isBlocked ? (
                    <button
                      onClick={() => toggleBlockMutation.mutate(user.id)}
                      disabled={toggleBlockMutation.isPending}
                      className="inline-block px-4 py-2 rounded text-white bg-red-600 hover:bg-red-700 transition disabled:opacity-50"
                    >
                      {toggleBlockMutation.isPending ? 'Chargement...' : 'Débloquer'}
                    </button>
                  ) : null
                ) : (
                  /* Pas bloqué : afficher tous les boutons normaux */
                  <>
                    <button
                      onClick={() => startConversationMutation.mutate(user.id)}
                      disabled={startConversationMutation.isPending}
                      className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:bg-blue-400 disabled:cursor-not-allowed"
                    >
                      {startConversationMutation.isPending ? 'Chargement...' : 'Envoyer un message'}
                    </button>
                    <button
                      onClick={() => toggleNetworkMutation.mutate(user.id)}
                      disabled={toggleNetworkMutation.isPending}
                      className={`inline-block px-4 py-2 rounded text-white transition ${
                        networkStatus === 'connected'
                          ? 'bg-red-600 hover:bg-red-700'
                          : networkStatus === 'outgoing_request'
                          ? 'bg-red-600 hover:bg-red-700'
                          : 'bg-green-600 hover:bg-green-700'
                      } disabled:opacity-50`}
                    >
                      {toggleNetworkMutation.isPending
                        ? 'Chargement...'
                        : networkStatus === 'connected'
                        ? 'Supprimer du réseau'
                        : networkStatus === 'outgoing_request'
                        ? 'Annuler la demande'
                        : networkStatus === 'incoming_request'
                        ? 'Accepter la demande'
                        : 'Ajouter au réseau'}
                    </button>

                    {/* Burger Menu */}
                    <div className="relative inline-block" ref={actionsMenuRef}>
                      <button
                        onClick={() => setShowActionsMenu(!showActionsMenu)}
                        className="inline-flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 focus:outline-none"
                      >
                        &#9776;
                      </button>
                      {showActionsMenu && (
                        <div className="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg z-40">
                          <button
                            onClick={() => {
                              toggleBlockMutation.mutate(user.id)
                              setShowActionsMenu(false)
                            }}
                            className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          >
                            Bloquer
                          </button>
                        </div>
                      )}
                    </div>
                  </>
                )
              ) : null}
            </div>
          </div>
        </div>
      </div>

      {/* Tabs - cachés si bloqué mutuellement */}
      {!isMutuallyBlocked && (
        <div className="max-w-3xl mx-auto mt-6 flex justify-center gap-6">
          <button
            onClick={() => setActiveTab('overview')}
            className={`px-6 py-2 rounded font-semibold focus:outline-none ${
              activeTab === 'overview'
                ? 'bg-indigo-700 text-white'
                : 'bg-indigo-900 text-white'
            }`}
          >
            Vue d'ensemble
          </button>
          <button
            onClick={() => setActiveTab('posts')}
            className={`px-6 py-2 rounded font-semibold focus:outline-none ${
              activeTab === 'posts'
                ? 'bg-indigo-700 text-white'
                : 'bg-indigo-900 text-white'
            }`}
          >
            Posts/Réponses
          </button>
          <button
            onClick={() => setActiveTab('comments')}
            className={`px-6 py-2 rounded font-semibold focus:outline-none ${
              activeTab === 'comments'
                ? 'bg-indigo-700 text-white'
                : 'bg-indigo-900 text-white'
            }`}
          >
            Commentaires
          </button>
        </div>
      )}

      {/* Tab Content - caché si bloqué mutuellement */}
      {!isMutuallyBlocked && (
        <div className="max-w-3xl mx-auto my-6 space-y-4">
          {activeTab === 'overview' && (
          <div>
            {overviewData?.data?.questions?.map((question, idx) => (
              <div key={idx} className="bg-white rounded-lg p-4 shadow mb-4">
                <h3 className="font-bold text-lg border-b border-indigo-200 pb-1 mb-1">
                  {question.label}
                </h3>
                <div className="break-words whitespace-pre-line">{question.answer}</div>
              </div>
            ))}
            {overviewData?.data?.tags?.map((tagGroup, idx) => (
              <div key={idx} className="bg-white rounded-lg p-4 shadow mb-4">
                <h3 className="font-bold text-lg border-b border-indigo-200 pb-1 mb-1">
                  {tagGroup.label}
                </h3>
                <div className="flex flex-wrap gap-2">
                  {tagGroup.tags.map((tag, tagIdx) => (
                    <span
                      key={tagIdx}
                      className="px-2 py-1 bg-indigo-50 text-indigo-800 rounded font-medium text-sm break-words"
                    >
                      {tag}
                    </span>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}

        {activeTab === 'posts' && (
          <div>
            {postsData?.data?.map((post) => (
              <div key={post.id} className="bg-white rounded-lg p-4 shadow mb-4">
                <div className="font-bold text-lg mb-2">{post.title}</div>
                <div className="text-gray-700 mb-2 break-words">{post.description}</div>
                <div className="text-xs text-gray-400">
                  Publié le {new Date(post.creationDate).toLocaleDateString('fr-FR', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </div>
              </div>
            ))}
            {(!postsData?.data || postsData.data.length === 0) && (
              <div className="text-center text-gray-500 py-8">
                Aucun post pour le moment
              </div>
            )}
          </div>
        )}

        {activeTab === 'comments' && (
          <div>
            {commentsData?.data?.map((comment) => (
              <div key={comment.id} className="bg-white rounded-lg p-4 shadow mb-4">
                <div className="text-sm text-gray-500 mb-2">
                  Sur le post: <Link to={`/forums/${comment.forum}/${comment.postId}`} className="text-blue-600 hover:underline">{comment.postTitle}</Link>
                </div>
                <div className="break-words whitespace-pre-line">{comment.body}</div>
                <div className="text-xs text-gray-400 mt-2">
                  Publié le {new Date(comment.creationDate).toLocaleDateString('fr-FR', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </div>
              </div>
            ))}
            {(!commentsData?.data || commentsData.data.length === 0) && (
              <div className="text-center text-gray-500 py-8">
                Aucun commentaire pour le moment
              </div>
            )}
          </div>
        )}
      </div>
      )}
    </div>
  )
}
