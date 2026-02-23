import { useState } from 'react'
import { Link, useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { forumApi, commentApi } from '../services/apis'
import { useAuthStore } from '../store'

const philosopherNames = [
  'Victor Hugo', 'Platon', 'René Descartes', 'Jean-Paul Sartre', 'Voltaire', 
  'Friedrich Nietzsche', 'Albert Camus', 'Michel de Montaigne', 'Jean-Jacques Rousseau',
  'Honoré de Balzac', 'Socrates', 'Aristote', 'Emmanuel Kant', 'Sigmund Freud',
  'John Locke', 'Thomas Hobbes', 'Karl Marx', 'Georg Wilhelm Friedrich Hegel', 'Sören Kierkegaard'
]

const getRandomPhilosopher = () => philosopherNames[Math.floor(Math.random() * philosopherNames.length)]

export default function Forums() {
  const { category = 'General', postId } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { user, isAuthenticated } = useAuthStore()
  const [searchQuery, setSearchQuery] = useState('')
  const [searchType, setSearchType] = useState('all')
  const [dateFilter, setDateFilter] = useState('all')
  const [sortBy, setSortBy] = useState('recent')
  const [showReplyForm, setShowReplyForm] = useState(false)
  const [replyContent, setReplyContent] = useState('')
  const [commentContent, setCommentContent] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [newPostTitle, setNewPostTitle] = useState('')
  const [newPostDescription, setNewPostDescription] = useState('')
  const [selectedForumId, setSelectedForumId] = useState(null)

  // Fetch forums for sidebar
  const { data: forumsData = [] } = useQuery({
    queryKey: ['forums'],
    queryFn: () => forumApi.getAll(),
  })

  const forums = Array.isArray(forumsData) ? forumsData : forumsData.data || []

  // Fetch posts for selected category
  const { data: postsData = [], isLoading } = useQuery({
    queryKey: ['posts', category],
    queryFn: () => 
      category === 'General' 
        ? forumApi.getAllPosts()
        : forumApi.getPostsByCategory(category),
    enabled: !postId,
  })

  const posts = Array.isArray(postsData) ? postsData : postsData.data || []

  // Fetch selected post details
  const { data: selectedPostData } = useQuery({
    queryKey: ['post', postId],
    queryFn: () => forumApi.getPost(postId),
    enabled: !!postId,
  })

  const selectedPost = selectedPostData?.data

  // Fetch comments for selected post
  const { data: commentsData = [] } = useQuery({
    queryKey: ['comments', postId],
    queryFn: () => commentApi.getComments(postId),
    enabled: !!postId,
  })

  const comments = Array.isArray(commentsData) ? commentsData : commentsData.data || []

  // Get current forum
  const currentForum = forums.find(f => f.title === category)

  // Filter and sort posts
  const filteredPosts = posts.filter(post => {
    if (!searchQuery) return true
    const query = searchQuery.toLowerCase()
    
    if (searchType === 'title') return post.name?.toLowerCase().includes(query)
    if (searchType === 'content') return post.description?.toLowerCase().includes(query)
    if (searchType === 'author') {
      const authorName = `${post.user?.firstName || ''} ${post.user?.lastName || ''}`.toLowerCase()
      return authorName.includes(query)
    }
    
    return post.name?.toLowerCase().includes(query) || post.description?.toLowerCase().includes(query)
  })

  const sortedPosts = [...filteredPosts].sort((a, b) => {
    if (sortBy === 'popular') return (b.likesCount || 0) - (a.likesCount || 0)
    if (sortBy === 'commented') return (b.commentsCount || 0) - (a.commentsCount || 0)
    return new Date(b.creationDate) - new Date(a.creationDate)
  })

  const createCommentMutation = useMutation({
    mutationFn: (data) => commentApi.createComment(data),
    onSuccess: () => {
      queryClient.invalidateQueries(['comments'])
      setCommentContent('')
    },
  })

  const createReplyMutation = useMutation({
    mutationFn: (data) => forumApi.createPost(data),
    onSuccess: () => {
      queryClient.invalidateQueries(['posts'])
      queryClient.invalidateQueries(['post'])
      setShowReplyForm(false)
      setReplyContent('')
    },
  })

  const likePostMutation = useMutation({
    mutationFn: (postId) => forumApi.likePost(postId),
    onSuccess: () => {
      queryClient.invalidateQueries(['post'])
      queryClient.invalidateQueries(['posts'])
    },
  })

  const likeCommentMutation = useMutation({
    mutationFn: (commentId) => commentApi.likeComment(commentId),
    onSuccess: () => {
      queryClient.invalidateQueries(['comments'])
    },
  })

  const createPostMutation = useMutation({
    mutationFn: (data) => forumApi.createPost(data),
    onSuccess: () => {
      queryClient.invalidateQueries(['posts'])
      setShowCreateModal(false)
      setNewPostTitle('')
      setNewPostDescription('')
      setSelectedForumId(null)
    },
  })

  const handleCreateComment = (e) => {
    e.preventDefault()
    createCommentMutation.mutate({ postId, body: commentContent })
  }

  const handleCreateReply = (e) => {
    e.preventDefault()
    const forumId = selectedPost.forum?.id
    createReplyMutation.mutate({
      name: `Re: ${selectedPost.name}`,
      description: replyContent,
      forumId,
      parentId: selectedPost.id
    })
  }

  const handleCreatePost = (e) => {
    e.preventDefault()
    const forumId = selectedForumId || currentForum?.id || forums[0]?.id
    createPostMutation.mutate({
      name: newPostTitle,
      description: newPostDescription,
      forumId
    })
  }

  const getAuthorName = (post) => {
    if (post.forum?.anonymous && (!user || user.userType !== 1)) {
      return getRandomPhilosopher()
    }
    return post.user ? `${post.user.firstName} ${post.user.lastName}` : 'Ancien utilisateur'
  }

  return (
    <div className="flex-1 flex flex-row justify-center items-start h-full my-11">
      {/* Categories Sidebar */}
      <div className="bg-white/45 backdrop-blur-sm shadow-xl m-5 p-2 rounded-lg">
        <div className="bg-white rounded-lg p-2">
          <div className="inline-block">
            <h2 className="text-2xl text-gray-700">Categories</h2>
            <div className="bg-blue-600 h-1 rounded-full my-1 w-full"></div>
          </div>
          <div className="flex flex-col">
            <Link
              to="/forums/General"
              className={`p-1 w-full ${
                category === 'General'
                  ? 'font-semibold bg-blue-50 text-blue-900'
                  : 'hover:bg-blue-50 text-gray-700 hover:text-blue-900'
              }`}
            >
              Toutes categories
            </Link>
            {forums.filter(f => !f.anonymous).map((forum) => (
              <Link
                key={forum.id}
                to={`/forums/${forum.title}`}
                className={`p-1 w-full ${
                  category === forum.title
                    ? 'font-semibold bg-blue-50 text-blue-900'
                    : 'hover:bg-blue-50 text-gray-700 hover:text-blue-900'
                }`}
              >
                <h3>{forum.title}</h3>
              </Link>
            ))}
          </div>
        </div>
      </div>

      {/* Music Player for Debussy */}
      {currentForum?.debussyClairDeLune && (
        <div className="fixed bottom-5 right-5 bg-white backdrop-blur-md border border-gray-200 shadow-lg rounded-xl p-4 w-72 flex flex-col items-center space-y-2 z-[10000]">
          <audio controls loop preload="none" className="w-full rounded-lg">
            <source src="/audio/clair_de_lune_debussy.mp3" type="audio/mpeg" />
            Votre navigateur ne supporte pas la balise audio.
          </audio>
          <p className="text-sm text-gray-600">🎶 Claude Debussy - Clair de Lune</p>
        </div>
      )}

      {/* Main Content */}
      <div className="m-5 max-w-5xl w-full bg-white/50 backdrop-blur-sm p-8 rounded-xl shadow-xl border border-white/20">
        {selectedPost ? (
          /* Post Detail View */
          <>
            <Link to={`/forums/${category}`} className="text-blue-600 m-2 block">
              ← Retour a la catégorie "{category}"
            </Link>

            {/* Main Post */}
            <div className="bg-white relative p-5 rounded-lg border-l-4 border-blue-500">
              <h2 className="text-2xl font-semibold text-gray-700">{selectedPost.name}</h2>
              <p className="text-gray-500">Par {getAuthorName(selectedPost)}</p>
              <p className="text-gray-500">
                {new Date(selectedPost.creationDate).toLocaleDateString('fr-FR')}
              </p>
              <div className="mt-5">
                <p className="text-gray-700">{selectedPost.description}</p>
              </div>

              {/* Actions */}
              <div className="flex items-center mt-3 space-x-4">
                <div className="flex items-center">
                  <button 
                    onClick={() => isAuthenticated && likePostMutation.mutate(selectedPost.id)}
                    className="text-gray-500 hover:text-red-500 text-lg mr-2 transition"
                    disabled={!isAuthenticated}
                  >
                    ♡
                  </button>
                  <span className="text-gray-600">{selectedPost.likesCount || 0}</span>
                </div>
                {isAuthenticated && (
                  <button
                    onClick={() => setShowReplyForm(!showReplyForm)}
                    className="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                  >
                    📝 Répondre
                  </button>
                )}
              </div>

              {/* Reply Form */}
              {showReplyForm && isAuthenticated && (
                <div className="mt-4 p-4 bg-gray-50 rounded-lg border">
                  <h4 className="font-semibold text-gray-700 mb-2">Répondre à ce post :</h4>
                  <div className="bg-white p-3 border-l-4 border-gray-300 mb-3 text-sm text-gray-600">
                    <div className="font-medium">{getAuthorName(selectedPost)} a écrit :</div>
                    <div className="italic mt-1">
                      {selectedPost.description.length > 200
                        ? selectedPost.description.slice(0, 200) + '...'
                        : selectedPost.description}
                    </div>
                  </div>
                  <form onSubmit={handleCreateReply}>
                    <textarea
                      value={replyContent}
                      onChange={(e) => setReplyContent(e.target.value)}
                      rows="4"
                      className="w-full p-2 border rounded-lg"
                      placeholder="Votre réponse..."
                      required
                    />
                    <div className="mt-2 flex space-x-2">
                      <button
                        type="submit"
                        className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
                      >
                        📤 Publier la réponse
                      </button>
                      <button
                        type="button"
                        onClick={() => setShowReplyForm(false)}
                        className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg"
                      >
                        Annuler
                      </button>
                    </div>
                  </form>
                </div>
              )}
            </div>

            {/* Comments Section */}
            <div className="mt-8">
              <h3 className="text-xl font-semibold text-gray-700">{comments.length} commentaires</h3>

              {isAuthenticated ? (
                <form onSubmit={handleCreateComment} className="mt-5">
                  <textarea
                    value={commentContent}
                    onChange={(e) => setCommentContent(e.target.value)}
                    rows="4"
                    className="w-full p-2 border rounded-lg"
                    placeholder="Ajouter un commentaire..."
                  />
                  <button
                    type="submit"
                    className="mt-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                  >
                    Commenter
                  </button>
                </form>
              ) : (
                <button
                  onClick={() => navigate('/login')}
                  className="text-white bg-blue-600 hover:bg-blue-700 mt-2 px-4 py-2 rounded-lg"
                >
                  + Ajouter un commentaire
                </button>
              )}

              {comments.map((comment) => (
                <div key={comment.id} className="flex flex-row items-center w-full my-2">
                  <div className="flex flex-col items-center text-gray-500 w-8 mx-2">
                    <button 
                      onClick={() => isAuthenticated && likeCommentMutation.mutate(comment.id)}
                      className="text-gray-500 hover:text-red-500 text-lg transition"
                      disabled={!isAuthenticated}
                    >
                      ♡
                    </button>
                    <p>{comment.likesCount || 0}</p>
                  </div>
                  <div className="bg-white text-gray-700 p-3 rounded-lg w-full">
                    <p>
                      <strong>{getAuthorName(comment)}</strong>
                    </p>
                    <p>{comment.body}</p>
                  </div>
                </div>
              ))}
            </div>
          </>
        ) : (
          /* Posts List View */
          <>
            <div className="flex flex-row items-center justify-between mb-5">
              <h1 className="text-2xl pb-2 text-gray-700">Forums</h1>
              <button
                onClick={() => {
                  if (!isAuthenticated) {
                    navigate('/login')
                  } else {
                    setSelectedForumId(currentForum?.id || null)
                    setShowCreateModal(true)
                  }
                }}
                className="mt-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
              >
                + Créer une publication
              </button>
            </div>

            {/* Search Bar */}
            <div className="mb-6 bg-white/70 backdrop-blur-sm p-6 rounded-xl shadow-sm border border-white/30">
              <div className="flex flex-col lg:flex-row gap-6 items-start lg:items-center w-full">
                <div className="w-full lg:flex-1">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Rechercher..."
                    className="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-white/80 backdrop-blur-sm text-gray-700 placeholder-gray-400"
                  />
                </div>
                <div className="w-full lg:w-2/3">
                  <div className="flex flex-row gap-6 w-full">
                    <select
                      value={searchType}
                      onChange={(e) => setSearchType(e.target.value)}
                      className="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white/80 backdrop-blur-sm text-gray-700"
                    >
                      <option value="all">Tout</option>
                      <option value="title">Titres</option>
                      <option value="content">Contenu</option>
                      <option value="author">Auteurs</option>
                    </select>
                    <select
                      value={dateFilter}
                      onChange={(e) => setDateFilter(e.target.value)}
                      className="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white/80 backdrop-blur-sm text-gray-700"
                    >
                      <option value="all">Toutes dates</option>
                      <option value="today">Aujourd'hui</option>
                      <option value="week">Semaine</option>
                      <option value="month">Mois</option>
                      <option value="year">Année</option>
                    </select>
                    <select
                      value={sortBy}
                      onChange={(e) => setSortBy(e.target.value)}
                      className="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white/80 backdrop-blur-sm text-gray-700"
                    >
                      <option value="recent">Récents</option>
                      <option value="popular">Populaires (likes)</option>
                      <option value="commented">Commentés</option>
                    </select>
                  </div>
                </div>
              </div>
              {searchQuery && (
                <div className="mt-4 text-sm text-gray-600">
                  <span>{sortedPosts.length}</span> résultat(s) trouvé(s)
                  <button
                    onClick={() => setSearchQuery('')}
                    className="ml-2 text-blue-600 hover:text-blue-800 text-xs underline"
                  >
                    Effacer
                  </button>
                </div>
              )}
            </div>

            {/* Posts List with Custom Scrollbar */}
            <div className="custom-scrollbar max-h-[600px] overflow-y-auto">
              {isLoading ? (
                <div className="text-center py-8">Chargement...</div>
              ) : sortedPosts.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  Aucune discussion disponible
                </div>
              ) : (
                sortedPosts.map((post) => (
                  <Link
                    key={post.id}
                    to={`/forums/${category}/${post.id}`}
                    className="block bg-white p-4 rounded-lg mb-3 border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200"
                  >
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">{post.name}</h3>
                    <p className="text-gray-600 mb-2 line-clamp-2">{post.description}</p>
                    <div className="flex items-center text-sm text-gray-500">
                      <span>Par {getAuthorName(post)}</span>
                      <span className="mx-2">•</span>
                      <span>{new Date(post.creationDate).toLocaleDateString('fr-FR')}</span>
                      <span className="mx-2">•</span>
                      <span>{post.commentsCount || 0} commentaires</span>
                    </div>
                  </Link>
                ))
              )}
            </div>
          </>
        )}
      </div>

      {/* Create Post Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 w-full h-full bg-black bg-opacity-50 flex justify-center items-center z-[1000]">
          <div className="bg-white/95 text-gray-700 p-5 rounded-lg w-[90%] max-w-[500px] shadow-lg relative">
            <span
              className="absolute top-2 right-2 text-2xl cursor-pointer hover:text-gray-600"
              onClick={() => setShowCreateModal(false)}
            >
              &times;
            </span>
            <h2 className="text-xl font-bold mb-4">Créer une publication</h2>

            <form onSubmit={handleCreatePost} className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Titre</label>
                <input
                  type="text"
                  value={newPostTitle}
                  onChange={(e) => setNewPostTitle(e.target.value)}
                  className="bg-white w-full border border-gray-300 rounded-md p-2"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Description</label>
                <textarea
                  value={newPostDescription}
                  onChange={(e) => setNewPostDescription(e.target.value)}
                  className="bg-white w-full border border-gray-300 rounded-md p-2"
                  rows="4"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Forum</label>
                <select
                  value={selectedForumId || ''}
                  onChange={(e) => setSelectedForumId(Number(e.target.value))}
                  className="bg-white w-full border border-gray-300 rounded-md p-2"
                  required
                >
                  <option value="">Sélectionnez un forum</option>
                  {forums.filter(f => !f.anonymous).map((forum) => (
                    <option key={forum.id} value={forum.id}>
                      {forum.title}
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex gap-2">
                <button
                  type="submit"
                  disabled={createPostMutation.isPending}
                  className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
                >
                  {createPostMutation.isPending ? 'Publication...' : 'Publier'}
                </button>
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <style>{`
        .custom-scrollbar::-webkit-scrollbar {
          width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
          background: #f1f5f9;
          border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
          background: #3b82f6;
          border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
          background: #2563eb;
        }
        .custom-scrollbar {
          scrollbar-width: thin;
          scrollbar-color: #3b82f6 #f1f5f9;
        }
      `}</style>
    </div>
  )
}
