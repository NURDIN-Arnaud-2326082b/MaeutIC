import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getTags, createTag, updateTag, deleteTag, getReports, processReport, autoActionReport } from '../services/adminApi'
import api from '../services/api'
import { useAuthStore } from '../store'
import { useNavigate } from 'react-router-dom'

export default function AdminInterface() {
  const { user } = useAuthStore()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState('tags')
  const [searchQuery, setSearchQuery] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [createTagName, setCreateTagName] = useState('')
  const [editingTagId, setEditingTagId] = useState(null)
  const [editTagName, setEditTagName] = useState('')
  const [banSearchQuery, setBanSearchQuery] = useState('')
  const [reportsStatusFilter, setReportsStatusFilter] = useState('')

  // Redirect if not admin
  useEffect(() => {
    if (user && user.userType !== 1) {
      navigate('/')
    }
  }, [user, navigate])

  // Fetch tags with search query
  const { data: tagsData, isLoading } = useQuery({
    queryKey: ['admin-tags', searchQuery],
    queryFn: () => getTags(searchQuery),
    enabled: user?.userType === 1
  })

  // Fetch banned users
  const { data: bannedData, isLoading: bannedLoading } = useQuery({
    queryKey: ['admin-banned-users'],
    queryFn: async () => {
      const response = await api.get('/admin/banned-users')
      return response.data
    },
    enabled: user?.userType === 1
  })

  // Search users for banning
  const { data: searchUsersData, isLoading: searchUsersLoading } = useQuery({
    queryKey: ['admin-search-users', banSearchQuery],
    queryFn: async () => {
      if (!banSearchQuery.trim()) return { users: [] }
      const response = await api.get('/admin/search-users', {
        params: { q: banSearchQuery }
      })
      return response.data
    },
    enabled: user?.userType === 1 && banSearchQuery.trim().length > 0
  })

  // Fetch reports for moderation queue
  const { data: reportsData, isLoading: reportsLoading } = useQuery({
    queryKey: ['admin-reports', reportsStatusFilter],
    queryFn: () => getReports(reportsStatusFilter),
    enabled: user?.userType === 1
  })

  // Create mutation
  const createMutation = useMutation({
    mutationFn: createTag,
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-tags'])
      setShowCreateModal(false)
      setCreateTagName('')
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors de la création du tag')
    }
  })

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, name }) => updateTag(id, name),
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-tags'])
      setEditingTagId(null)
      setEditTagName('')
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors de la modification du tag')
    }
  })

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: deleteTag,
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-tags'])
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors de la suppression du tag')
    }
  })

  // Ban user mutation
  const banUserMutation = useMutation({
    mutationFn: async (userId) => {
      const response = await api.post(`/admin/users/${userId}/ban`)
      return response.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-banned-users'])
      alert('Utilisateur banni avec succès')
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors du bannissement')
    }
  })

  // Unban user mutation
  const unbanUserMutation = useMutation({
    mutationFn: async (userId) => {
      const response = await api.post(`/admin/users/${userId}/unban`)
      return response.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-banned-users'])
      alert('Utilisateur débanni avec succès')
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors du débannissement')
    }
  })

  // Process report mutation
  const processReportMutation = useMutation({
    mutationFn: ({ id, status, adminNote }) => processReport(id, status, adminNote),
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-reports'])
      alert('Signalement mis à jour')
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors du traitement du signalement')
    }
  })

  const autoActionMutation = useMutation({
    mutationFn: ({ id, action, adminNote }) => autoActionReport(id, action, adminNote),
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-reports'])
      queryClient.invalidateQueries(['admin-banned-users'])
      alert('Action automatique appliquée')
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors de l\'action automatique')
    }
  })

  const handleCreateSubmit = (e) => {
    e.preventDefault()
    if (createTagName.trim()) {
      createMutation.mutate(createTagName.trim())
    }
  }

  const handleEditSubmit = (e, id) => {
    e.preventDefault()
    if (editTagName.trim()) {
      updateMutation.mutate({ id, name: editTagName.trim() })
    }
  }

  const handleDelete = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce tag ?')) {
      deleteMutation.mutate(id)
    }
  }

  const openEditForm = (id, name) => {
    setEditingTagId(id)
    setEditTagName(name)
  }

  const closeEditForm = () => {
    setEditingTagId(null)
    setEditTagName('')
  }

  const handleBan = (userId) => {
    if (confirm('Êtes-vous sûr de vouloir bannir cet utilisateur ?')) {
      banUserMutation.mutate(userId)
    }
  }

  const handleUnban = (userId) => {
    if (confirm('Êtes-vous sûr de vouloir débannir cet utilisateur ?')) {
      unbanUserMutation.mutate(userId)
    }
  }

  const handleProcessReport = (reportId, status) => {
    const adminNote = globalThis.prompt('Note admin (optionnel)') || ''
    processReportMutation.mutate({ id: reportId, status, adminNote })
  }

  const handleAutoAction = (report, action) => {
    const confirmationText = action === 'delete_target'
      ? 'Confirmer la suppression du contenu signalé ?'
      : 'Confirmer le bannissement de l\'auteur signalé ?'

    const confirmed = globalThis.confirm(confirmationText)
    if (!confirmed) {
      return
    }

    const adminNote = globalThis.prompt('Note admin (optionnel)') || ''
    autoActionMutation.mutate({ id: report.id, action, adminNote })
  }

  const tags = tagsData?.tags || []
  const bannedUsers = bannedData?.bannedUsers || []
  const reports = reportsData?.reports || []

  if (!user || user.userType !== 1) {
    return null
  }

  return (
    <div className="flex-1">
      <div className="bg-white/45 text-gray-700 backdrop-blur-sm shadow-xl mx-auto my-5 p-4 rounded-lg">
        <h1 className="text-2xl font-bold mb-4">Panneau d'Administration</h1>

        {/* Tabs */}
        <div className="flex border-b border-gray-300 mb-4">
          <button
            onClick={() => setActiveTab('tags')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'tags'
                ? 'text-blue-600 border-b-2 border-blue-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Gestion des Tags
          </button>
          <button
            onClick={() => setActiveTab('banned')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'banned'
                ? 'text-blue-600 border-b-2 border-blue-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Utilisateurs Bannis ({bannedUsers.length})
          </button>
          <button
            onClick={() => setActiveTab('reports')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'reports'
                ? 'text-blue-600 border-b-2 border-blue-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Signalements ({reports.length})
          </button>
        </div>

        {/* Tags Tab */}
        {activeTab === 'tags' && (
          <div className="max-w-lg">
            <div className="flex flex-row items-center justify-between mb-4">
              {/* Search bar */}
              <input
                type="text"
                id="search-bar"
                placeholder="Rechercher un tag"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full p-2 border border-gray-300 rounded max-w-md"
              />

              {/* Button to open create modal */}
              <button
                onClick={() => setShowCreateModal(true)}
                className="ml-4 text-white bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-indigo-500 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none whitespace-nowrap"
              >
                Ajouter un nouveau tag
              </button>
            </div>

            {/* Tags list */}
            <div className="flex flex-col w-auto mb-4">
              <div className="flex flex-row justify-between text-sm max-w-xl px-4">
                <p>Nom</p>
                <p>Action</p>
              </div>

              {isLoading ? (
                <div className="text-center py-4">Chargement...</div>
              ) : tags.length > 0 ? (
                tags.map((tag) => (
                  <div
                    key={tag.id}
                    className="bg-white hover:bg-blue-50 rounded-lg mb-1 flex flex-col justify-between"
                    id={`tag-${tag.id}`}
                  >
                    <div className="flex flex-row justify-between">
                      <p className="px-4 py-2">{tag.name}</p>
                      <div className="px-4 py-2">
                        <button
                          onClick={() => openEditForm(tag.id, tag.name)}
                          className="text-blue-500 hover:underline mr-2"
                        >
                          Modifier
                        </button>
                        <button
                          onClick={() => handleDelete(tag.id)}
                          className="text-red-500 hover:underline"
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>

                    {/* Inline edit form */}
                    {editingTagId === tag.id && (
                      <form
                        onSubmit={(e) => handleEditSubmit(e, tag.id)}
                        className="p-2"
                      >
                        <input
                          type="text"
                          value={editTagName}
                          onChange={(e) => setEditTagName(e.target.value)}
                          className="w-full p-2 border border-gray-300 rounded mb-4"
                          autoFocus
                        />
                        <div className="flex flex-row items-center">
                          <button
                            type="submit"
                            disabled={updateMutation.isPending}
                            className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-4 disabled:opacity-50"
                          >
                            {updateMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
                          </button>
                          <button
                            type="button"
                            onClick={closeEditForm}
                            className="text-gray-700 hover:text-blue-600 font-medium"
                          >
                            Annuler
                          </button>
                        </div>
                      </form>
                    )}
                  </div>
                ))
              ) : (
                <div className="text-center py-4">
                  Aucun tag trouvé pour votre recherche.
                </div>
              )}
            </div>
          </div>
        )}

        {/* Banned Users Tab */}
        {activeTab === 'banned' && (
          <div className="max-w-2xl">
            <div className="mb-6 pb-4 border-b border-gray-300">
              <h3 className="text-lg font-semibold mb-3">Bannir un utilisateur</h3>
              <div className="flex gap-2">
                <input
                  type="text"
                  placeholder="Rechercher un utilisateur (nom, email, username)..."
                  value={banSearchQuery}
                  onChange={(e) => setBanSearchQuery(e.target.value)}
                  className="flex-1 p-2 border border-gray-300 rounded"
                />
              </div>

              {banSearchQuery.trim().length > 0 && (
                <div className="mt-3">
                  {searchUsersLoading ? (
                    <div className="text-gray-600">Recherche en cours...</div>
                  ) : (searchUsersData?.users || []).length === 0 ? (
                    <div className="text-gray-600">Aucun utilisateur trouvé.</div>
                  ) : (
                    <div className="space-y-2">
                      {(searchUsersData?.users || []).map((searchUser) => {
                        const isBanned = bannedUsers.some((b) => b.id === searchUser.id)
                        return (
                          <div
                            key={searchUser.id}
                            className="flex items-center justify-between p-3 bg-gray-50 rounded border"
                          >
                            <div className="flex items-center gap-2 flex-1">
                              <img
                                src={searchUser.profileImage || '/images/default-profile.png'}
                                alt="avatar"
                                className="w-10 h-10 rounded-full object-cover"
                              />
                              <div>
                                <div className="font-medium text-sm">
                                  {searchUser.firstName} {searchUser.lastName}
                                </div>
                                <div className="text-xs text-gray-600">
                                  @{searchUser.username}
                                </div>
                              </div>
                            </div>
                            {!isBanned && (
                              <button
                                onClick={() => handleBan(searchUser.id)}
                                disabled={banUserMutation.isPending}
                                className="ml-2 px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 disabled:opacity-50"
                              >
                                Bannir
                              </button>
                            )}
                            {isBanned && (
                              <span className="ml-2 px-3 py-1 bg-red-100 text-red-700 rounded text-sm">
                                Déjà banni
                              </span>
                            )}
                          </div>
                        )
                      })}
                    </div>
                  )}
                </div>
              )}
            </div>

            <h3 className="text-lg font-semibold mb-3">Utilisateurs actuellement bannis</h3>
            {bannedLoading ? (
              <div className="text-center py-4">Chargement...</div>
            ) : bannedUsers.length === 0 ? (
              <div className="text-center py-4 text-gray-600">
                Aucun utilisateur banni.
              </div>
            ) : (
              <div className="space-y-3">
                {bannedUsers.map((bannedUser) => (
                  <div
                    key={bannedUser.id}
                    className="flex items-center justify-between p-4 bg-white rounded border border-red-200"
                  >
                    <div className="flex items-center gap-3 flex-1">
                      <img
                        src={bannedUser.profileImage || '/images/default-profile.png'}
                        alt="avatar"
                        className="w-12 h-12 rounded-full object-cover"
                      />
                      <div className="flex-1">
                        <div className="font-medium">
                          {bannedUser.firstName} {bannedUser.lastName}{' '}
                          <span className="text-sm text-gray-500">
                            (@{bannedUser.username})
                          </span>
                        </div>
                        <div className="text-sm text-gray-600">
                          {bannedUser.email}
                        </div>
                        {bannedUser.affiliationLocation && (
                          <div className="text-sm text-gray-500">
                            {bannedUser.affiliationLocation}
                          </div>
                        )}
                      </div>
                    </div>
                    <button
                      onClick={() => handleUnban(bannedUser.id)}
                      disabled={unbanUserMutation.isPending}
                      className="ml-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                    >
                      {unbanUserMutation.isPending ? '...' : 'Débannir'}
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Reports Tab */}
        {activeTab === 'reports' && (
          <div className="max-w-4xl">
            <div className="flex items-center gap-3 mb-4">
              <label htmlFor="reports-status-filter" className="text-sm font-medium text-gray-700">Filtrer par statut:</label>
              <select
                id="reports-status-filter"
                value={reportsStatusFilter}
                onChange={(e) => setReportsStatusFilter(e.target.value)}
                className="p-2 border border-gray-300 rounded"
              >
                <option value="">Tous</option>
                <option value="pending">En attente</option>
                <option value="reviewed">Traités</option>
                <option value="rejected">Rejetés</option>
              </select>
            </div>

            {reportsLoading ? (
              <div className="text-center py-4">Chargement...</div>
            ) : reports.length === 0 ? (
              <div className="text-center py-4 text-gray-600">Aucun signalement.</div>
            ) : (
              <div className="space-y-3">
                {reports.map((report) => (
                  <div key={report.id} className="bg-white border rounded p-4">
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex-1">
                        <div className="text-sm text-gray-500 mb-1">
                          #{report.id} • {report.targetType} #{report.targetId} • {new Date(report.createdAt).toLocaleString('fr-FR')}
                        </div>
                        <div className="font-semibold text-gray-900">
                          {report.reason}
                        </div>
                        {report.details && (
                          <div className="text-sm text-gray-700 mt-1">{report.details}</div>
                        )}
                        <div className="text-sm text-gray-600 mt-2">
                          Signalé par @{report.reporter?.username || 'inconnu'}
                        </div>
                        <div className="text-sm text-gray-600 mt-1">
                          Cible: {report.targetSummary?.label || 'Inconnue'}
                          {report.targetSummary?.author ? ` (auteur: @${report.targetSummary.author})` : ''}
                        </div>
                        {report.adminNote && (
                          <div className="text-sm text-indigo-700 mt-2">Note admin: {report.adminNote}</div>
                        )}
                      </div>
                      <div className="flex flex-col items-end gap-2 min-w-[160px]">
                        <span className={`px-2 py-1 rounded text-xs font-medium ${
                          report.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                          report.status === 'reviewed' ? 'bg-green-100 text-green-800' :
                          'bg-gray-100 text-gray-700'
                        }`}>
                          {report.status}
                        </span>

                        {report.status === 'pending' && (
                          <>
                            <button
                              onClick={() => handleProcessReport(report.id, 'reviewed')}
                              disabled={processReportMutation.isPending}
                              className="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 disabled:opacity-50"
                            >
                              Marquer traité
                            </button>
                            <button
                              onClick={() => handleProcessReport(report.id, 'rejected')}
                              disabled={processReportMutation.isPending}
                              className="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 disabled:opacity-50"
                            >
                              Rejeter
                            </button>
                            {(report.targetType === 'post' || report.targetType === 'comment') && (
                              <button
                                onClick={() => handleAutoAction(report, 'delete_target')}
                                disabled={autoActionMutation.isPending}
                                className="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 disabled:opacity-50"
                              >
                                Supprimer contenu
                              </button>
                            )}
                            <button
                              onClick={() => handleAutoAction(report, 'ban_author')}
                              disabled={autoActionMutation.isPending}
                              className="px-3 py-1 bg-amber-600 text-white rounded text-sm hover:bg-amber-700 disabled:opacity-50"
                            >
                              Bannir auteur
                            </button>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Create Modal */}
      {showCreateModal && (
        <div
          className="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
          onClick={() => setShowCreateModal(false)}
        >
          <div
            className="bg-white rounded-lg p-6 max-w-md w-full mx-4"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-bold">Ajouter un nouveau tag</h2>
              <button
                onClick={() => setShowCreateModal(false)}
                className="text-gray-500 hover:text-gray-700 text-2xl"
              >
                &times;
              </button>
            </div>
            <form onSubmit={handleCreateSubmit}>
              <input
                type="text"
                value={createTagName}
                onChange={(e) => setCreateTagName(e.target.value)}
                placeholder="Nom du tag"
                className="w-full p-2 border border-gray-300 rounded my-2"
                autoFocus
                required
              />
              <div className="flex flex-row items-center mt-4">
                <button
                  type="submit"
                  disabled={createMutation.isPending}
                  className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mr-4 disabled:opacity-50"
                >
                  {createMutation.isPending ? 'Ajout...' : 'Ajouter'}
                </button>
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="text-gray-700 hover:text-blue-600 font-medium"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
