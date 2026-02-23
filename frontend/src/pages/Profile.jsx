import { useState, useRef, useEffect } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import { userApi } from '../services/apis'
import { useAuthStore } from '../store'

export default function Profile() {
  const { username } = useParams()
  const navigate = useNavigate()
  const { user: currentUser, isAuthenticated } = useAuthStore()
  const [activeTab, setActiveTab] = useState('overview')
  const [showActionsMenu, setShowActionsMenu] = useState(false)
  const actionsMenuRef = useRef(null)

  const { data: profileData, isLoading } = useQuery({
    queryKey: ['profile', username],
    queryFn: () => userApi.getProfile(username),
  })

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
  const isOwnProfile = currentUser?.username === username
  const network = networkData?.data || []

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

          <div className="mt-4">
            {/* Réseau */}
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

            {/* Boutons d'action */}
            <div className="flex gap-2 items-center">
              {isOwnProfile ? (
                <>
                  <Link
                    to="/profile/edit"
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
              ) : (
                isAuthenticated && (
                  <>
                    <Link
                      to={`/messages/new/${user.id}`}
                      className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                    >
                      Envoyer un message
                    </Link>
                    <button
                      onClick={() => toggleNetworkMutation.mutate(user.id)}
                      disabled={toggleNetworkMutation.isPending}
                      className={`inline-block px-4 py-2 rounded text-white transition ${
                        user.isInNetwork
                          ? 'bg-red-600 hover:bg-red-700'
                          : 'bg-green-600 hover:bg-green-700'
                      }`}
                    >
                      {user.isInNetwork ? 'Supprimer du réseau' : 'Ajouter au réseau'}
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
                            {user.isBlocked ? 'Débloquer' : 'Bloquer'}
                          </button>
                        </div>
                      )}
                    </div>
                  </>
                )
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Tabs */}
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

      {/* Tab Content */}
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
              <Link
                key={post.id}
                to={`/forums/${post.category}/${post.id}`}
                className="block bg-white rounded-lg p-4 shadow mb-4 hover:shadow-lg transition"
              >
                <h3 className="font-bold text-lg mb-2">{post.title}</h3>
                <p className="text-gray-600 text-sm">
                  {new Date(post.creationDate).toLocaleDateString('fr-FR')}
                </p>
              </Link>
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
                  {new Date(comment.creationDate).toLocaleDateString('fr-FR')}
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
    </div>
  )
}
