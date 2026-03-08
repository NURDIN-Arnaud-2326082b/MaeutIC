import { useState, useEffect } from 'react'
import { Link, useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { forumApi, commentApi } from '../services/apis'
import { useAuthStore } from '../store'
import { checkSensitiveContent } from '../utils/sensitiveContentDetector'

const getRandomAnonymousId = () => {
  const letters = 'abcdefghijklmnopqrstuvwxyz'
  const randomLetter = () => letters[Math.floor(Math.random() * letters.length)]
  const randomDigit = () => Math.floor(Math.random() * 10)
  
  return `${randomLetter()}${randomLetter()}${randomLetter()}.${randomDigit()}${randomDigit()}${randomDigit()}`
}

export default function Forums({ specialCategory = null }) {
  const params = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { user, isAuthenticated } = useAuthStore()
  
  // Si pas de category dans URL, utiliser specialCategory (methodology, detente, etc.) ou 'General'
  const category = params.category || (specialCategory || 'General')
  const postId = params.postId
  
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
  const [preventionAlerts, setPreventionAlerts] = useState([])
  
  // Edit post states
  const [showEditModal, setShowEditModal] = useState(false)
  const [editingPost, setEditingPost] = useState(null)
  const [editPostTitle, setEditPostTitle] = useState('')
  const [editPostDescription, setEditPostDescription] = useState('')
  const [editPostForumId, setEditPostForumId] = useState(null)

  // Fetch forums for sidebar
  const { data: forumsData = [] } = useQuery({
    queryKey: ['forums', specialCategory],
    queryFn: () => forumApi.getAll(),
  })

  const allForums = Array.isArray(forumsData) ? forumsData : forumsData.data || []
  
  // Filter forums by special category for sidebar only
  const forums = specialCategory 
    ? allForums.filter(f => f.special === specialCategory)
    : allForums.filter(f => f.special !== 'cafe_des_lumieres')
  
  // Determine base path for links based on special category
  const getForumBasePath = () => {
    if (!specialCategory) return '/forums'
    const paths = {
      'methodology': '/methodology-forums',
      'detente': '/detente-forums',
      'cafe_des_lumieres': '/cafe_des_lumieres-forums',
      'administratif': '/administratif-forums'
    }
    return paths[specialCategory] || '/forums'
  }
  
  const basePath = getForumBasePath()
  
  // Get sidebar title based on special category
  const getSidebarTitle = () => {
    if (!specialCategory) return 'Categories'
    const titles = {
      'methodology': 'Catégories',
      'detente': 'Catégories',
      'cafe_des_lumieres': 'Catégories',
      'administratif': 'Catégories'
    }
    return titles[specialCategory] || 'Categories'
  }
  
  const getAllCategoriesLabel = () => {
    switch(specialCategory) {
      case 'methodology': return 'Toutes les méthodologies'
      case 'detente': return 'Tous les forums détente'
      case 'administratif': return 'Tous les forums administratifs'
      default: return 'Toutes categories'
    }
  }
  
  const getBackLabel = () => {
    switch(specialCategory) {
      case 'methodology': return 'toutes les méthodologies'
      case 'detente': return 'tous les forums détente'
      case 'cafe_des_lumieres': return 'tous les forums cafe_des_lumieress'
      case 'administratif': return 'tous les forums administratifs'
      default: return 'toutes categories'
    }
  }
  
  const showAllCategoriesLink = () => {
    // Café des lumières n'affiche pas le lien "Toutes"
    return specialCategory !== 'cafe_des_lumieres'
  }
  
  // Convertir les noms de catégories techniques en noms d'affichage
  const getCategoryDisplayName = (categoryName) => {
    const displayNames = {
      'cafe_des_lumieres': 'café des lumières',
      'detente': 'détente',
      'methodology': 'méthodologie',
      'administratif': 'administratif',
      'General': 'General'
    }
    return displayNames[categoryName] || categoryName
  }

  // Fetch posts for selected category
  const { data: postsData = [], isLoading } = useQuery({
    queryKey: ['posts', category, specialCategory],
    queryFn: () => {
      // Si category === specialCategory (detente, cafe_des_lumieres, methodology, administratif),
      // récupérer tous les posts pour les filtrer ensuite
      if (category === specialCategory) {
        return forumApi.getAllPosts()
      }
      
      // Si c'est 'General' (forums généraux), récupérer tous les posts
      if (category === 'General') {
        return forumApi.getAllPosts()
      }
      
      // Sinon, récupérer les posts de la catégorie spécifique
      return forumApi.getPostsByCategory(category)
    },
    enabled: !postId,
  })

  const allPosts = Array.isArray(postsData) ? postsData : postsData.data || []
  
  // Filter posts by special category if provided
  const forumIds = forums.map(f => f.id)
  const posts = specialCategory 
    ? allPosts.filter(post => post.forum && forumIds.includes(post.forum.id))
    : allPosts.filter(post => post.forum && post.forum.special !== 'cafe_des_lumieres')

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

  // Get current forum (null si on affiche tous les forums d'une catégorie spéciale)
  const currentForum = category === specialCategory ? null : forums.find(f => f.title === category)

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
      closeCreateModal()
    },
  })
  
  const updatePostMutation = useMutation({
    mutationFn: ({ id, data }) => 
      fetch(`http://localhost:8000/api/posts/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(data)
      }).then(r => r.json()),
    onSuccess: () => {
      queryClient.invalidateQueries(['posts'])
      queryClient.invalidateQueries(['post'])
      closeEditModal()
    },
  })

  const deletePostMutation = useMutation({
    mutationFn: (postId) =>
      fetch(`http://localhost:8000/api/posts/${postId}`, {
        method: 'DELETE',
        credentials: 'include'
      }).then(r => r.json()),
    onSuccess: () => {
      queryClient.invalidateQueries(['posts'])
      navigate(basePath)
    },
  })
  
  const toggleLikeMutation = useMutation({
    mutationFn: (postId) =>
      fetch(`http://localhost:8000/api/posts/${postId}/like`, {
        method: 'POST',
        credentials: 'include'
      }).then(r => r.json()),
    onSuccess: () => {
      queryClient.invalidateQueries(['post'])
      queryClient.invalidateQueries(['posts'])
    },
  })
  
  const closeCreateModal = () => {
    setShowCreateModal(false)
    setNewPostTitle('')
    setNewPostDescription('')
    setSelectedForumId(null)
    setPreventionAlerts([])
  }
  
  const openEditModal = async (post) => {
    setEditingPost(post)
    setEditPostTitle(post.name)
    setEditPostDescription(post.description)
    setEditPostForumId(post.forum?.id)
    setShowEditModal(true)
  }
  
  const closeEditModal = () => {
    setShowEditModal(false)
    setEditingPost(null)
    setEditPostTitle('')
    setEditPostDescription('')
    setEditPostForumId(null)
  }
  
  const handleEditPost = (e) => {
    e.preventDefault()
    if (!editingPost) return
    
    updatePostMutation.mutate({
      id: editingPost.id,
      data: {
        name: editPostTitle,
        description: editPostDescription,
        forumId: editPostForumId
      }
    })
  }
  
  const handleDeletePost = (postId) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce post ?')) {
      deletePostMutation.mutate(postId)
    }
  }

  // Détecter le contenu sensible lors de la saisie
  useEffect(() => {
    if (!showCreateModal) return
    
    const timeout = setTimeout(() => {
      const fullText = `${newPostTitle} ${newPostDescription}`.trim()
      const warnings = checkSensitiveContent(fullText)
      setPreventionAlerts(warnings)
    }, 300) // Debounce de 300ms
    
    return () => clearTimeout(timeout)
  }, [newPostTitle, newPostDescription, showCreateModal])

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

  const getAuthorName = (item, forumOverride = null) => {
    // Obtenir le forum depuis l'item (post/reply) ou depuis le paramètre (pour les commentaires)
    const forum = forumOverride || item.forum
    
    // Si le forum est anonyme ET que l'utilisateur n'est pas admin, afficher un ID anonyme
    if (forum?.anonymous && (!user || user.userType !== 1)) {
      return getRandomAnonymousId()
    }
    // Sinon afficher le vrai nom (ou "Ancien utilisateur" si pas d'auteur)
    return item.user ? `${item.user.firstName} ${item.user.lastName}` : 'Ancien utilisateur'
  }

  return (
    <div className="flex-1 flex flex-row justify-center items-start h-full my-11">
      {/* Categories Sidebar */}
      <div className="bg-white/45 backdrop-blur-sm shadow-xl m-5 p-2 rounded-lg">
        <div className="bg-white rounded-lg p-2">
          <div className="inline-block">
            <h2 className="text-2xl text-gray-700">{getSidebarTitle()}</h2>
            <div className="bg-blue-600 h-1 rounded-full my-1 w-full"></div>
          </div>
          <div className="flex flex-col">
            {showAllCategoriesLink() && (
              <Link
                to={basePath}
                className={`p-1 w-full ${
                  category === specialCategory || (!specialCategory && category === 'General')
                    ? 'font-semibold bg-blue-50 text-blue-900'
                    : 'hover:bg-blue-50 text-gray-700 hover:text-blue-900'
                }`}
              >
                {getAllCategoriesLabel()}
              </Link>
            )}
            {forums.filter(f => specialCategory === 'cafe_des_lumieres' || !f.anonymous).map((forum) => (
              <Link
                key={forum.id}
                to={`${basePath}/${forum.title}`}
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
            <Link 
              to={category === specialCategory ? basePath : `${basePath}/${category}`} 
              className="text-blue-600 m-2 block"
            >
              {category === specialCategory ? (
                `← Retour à ${getBackLabel()}`
              ) : (
                `← Retour a la catégorie "${getCategoryDisplayName(category)}"`
              )}
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
              <div className="flex items-center justify-between mt-3">
                <div className="flex items-center space-x-4">
                  <div className="flex items-center">
                    <button 
                      onClick={() => isAuthenticated && toggleLikeMutation.mutate(selectedPost.id)}
                      className={`text-lg mr-2 transition ${
                        selectedPost.isLiked ? 'text-red-500' : 'text-gray-500 hover:text-red-500'
                      }`}
                      disabled={!isAuthenticated}
                    >
                      {selectedPost.isLiked ? '♥' : '♡'}
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
                
                {/* Edit/Delete buttons for post owner or admin */}
                {isAuthenticated && (user?.id === selectedPost.user?.id || user?.userType === 1) && (
                  <div className="flex items-center space-x-2">
                    <button
                      onClick={() => openEditModal(selectedPost)}
                      className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
                    >
                      ✏️ Modifier
                    </button>
                    <button
                      onClick={() => handleDeletePost(selectedPost.id)}
                      className="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm"
                      disabled={deletePostMutation.isPending}
                    >
                      🗑️ Supprimer
                    </button>
                  </div>
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
                      <strong>{getAuthorName(comment, selectedPost?.forum)}</strong>
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
                    // Pré-sélectionner le forum actuel si on est sur une catégorie spécifique
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
                  <div
                    key={post.id}
                    className="bg-white p-4 rounded-lg mb-3 border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200"
                  >
                    <Link to={`${basePath}/${category}/${post.id}`}>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2 hover:text-blue-600">{post.name}</h3>
                    </Link>
                    <p className="text-gray-600 mb-2 line-clamp-2">{post.description}</p>
                    <div className="flex items-center justify-between text-sm text-gray-500">
                      <div className="flex items-center">
                        <span>Par {getAuthorName(post)}</span>
                        <span className="mx-2">•</span>
                        <span>{new Date(post.creationDate).toLocaleDateString('fr-FR')}</span>
                        <span className="mx-2">•</span>
                        <span>{post.commentsCount || 0} commentaires</span>
                      </div>
                      {isAuthenticated && (user?.id === post.user?.id || user?.userType === 1) && (
                        <div className="flex gap-2">
                          <button
                            onClick={(e) => {
                              e.preventDefault()
                              openEditModal(post)
                            }}
                            className="text-blue-600 hover:text-blue-800 text-xs font-medium"
                          >
                            Modifier
                          </button>
                          <button
                            onClick={(e) => {
                              e.preventDefault()
                              handleDeletePost(post.id)
                            }}
                            className="text-red-600 hover:text-red-800 text-xs font-medium"
                          >
                            Supprimer
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                ))
              )}
            </div>
          </>
        )}
      </div>

      {/* Create Post Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 w-full h-full bg-black bg-opacity-50 flex justify-center items-center z-[1000]">
          <div className="bg-white/95 text-gray-700 p-5 rounded-lg w-[90%] max-w-[500px] max-h-[90vh] overflow-y-auto shadow-lg relative">
            <span
              className="absolute top-2 right-2 text-2xl cursor-pointer hover:text-gray-600"
              onClick={closeCreateModal}
            >
              &times;
            </span>
            <h2 className="text-xl font-bold mb-4">Créer une publication</h2>

            {/* Alertes de prévention */}
            {preventionAlerts.length > 0 && (
              <div className="space-y-2 mb-4 max-h-40 overflow-y-auto">
                {/* Alerte générale */}
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
                  <div className="flex items-start">
                    <div className="flex-shrink-0 mt-0.5">💙</div>
                    <div className="ml-3 flex-1">
                      <h3 className="font-medium text-blue-800">Nous avons détecté du contenu sensible</h3>
                      <p className="text-blue-700 mt-1 text-xs">
                        Votre bien-être est important. Voici des ressources qui peuvent vous aider
                      </p>
                    </div>
                  </div>
                </div>

                {/* Alertes spécifiques */}
                {preventionAlerts.map((warning, index) => (
                  <div key={index} className="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm">
                    <div className="flex items-start">
                      <div className="flex-shrink-0 mt-0.5">⚠️</div>
                      <div className="ml-3 flex-1">
                        <p className="text-yellow-800 text-xs leading-relaxed">
                          {warning.message}
                          {warning.link && (
                            <>
                              <br />
                              <a 
                                href={warning.link} 
                                target="_blank" 
                                rel="noopener noreferrer"
                                className="underline text-yellow-900 hover:text-yellow-700 text-xs mt-1 inline-block"
                              >
                                Plus d'informations
                              </a>
                            </>
                          )}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}

                {/* Message de soutien */}
                <div className="bg-green-50 border border-green-200 rounded-lg p-3 text-sm">
                  <div className="flex items-start">
                    <div className="flex-shrink-0 mt-0.5">🤝</div>
                    <div className="ml-3">
                      <p className="text-green-800 text-xs">
                        <strong>Vous n'êtes pas seul(e).</strong> Demander de l'aide est un signe de force.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}

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
                <label className="block text-sm font-medium mb-1">Salon</label>
                <select
                  value={selectedForumId || ''}
                  onChange={(e) => setSelectedForumId(Number(e.target.value))}
                  className="bg-white w-full border border-gray-300 rounded-md p-2"
                  required
                >
                  <option value="">-- Choisir un salon --</option>
                  {allForums.map((forum) => (
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
                  onClick={closeCreateModal}
                  className="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit Post Modal */}
      {showEditModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <h2 className="text-2xl font-bold mb-4">Modifier la publication</h2>

            <form onSubmit={handleEditPost} className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Titre</label>
                <input
                  type="text"
                  value={editPostTitle}
                  onChange={(e) => setEditPostTitle(e.target.value)}
                  className="bg-white w-full border border-gray-300 rounded-md p-2"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Description</label>
                <textarea
                  value={editPostDescription}
                  onChange={(e) => setEditPostDescription(e.target.value)}
                  className="bg-white w-full border border-gray-300 rounded-md p-2"
                  rows="4"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Salon</label>
                <select
                  value={editPostForumId || ''}
                  onChange={(e) => setEditPostForumId(Number(e.target.value))}
                  className="bg-white w-full border border-gray-300 rounded-md p-2"
                  required
                >
                  <option value="">-- Choisir un salon --</option>
                  {allForums.map((forum) => (
                    <option key={forum.id} value={forum.id}>
                      {forum.title}
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex gap-2">
                <button
                  type="submit"
                  disabled={updatePostMutation.isPending}
                  className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
                >
                  {updatePostMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
                </button>
                <button
                  type="button"
                  onClick={closeEditModal}
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
