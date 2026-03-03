import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getTags, createTag, updateTag, deleteTag } from '../services/adminApi'
import { useAuthStore } from '../store'
import { useNavigate } from 'react-router-dom'

export default function AdminInterface() {
  const { user } = useAuthStore()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchQuery, setSearchQuery] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [createTagName, setCreateTagName] = useState('')
  const [editingTagId, setEditingTagId] = useState(null)
  const [editTagName, setEditTagName] = useState('')

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

  const tags = tagsData?.tags || []

  if (!user || user.userType !== 1) {
    return null
  }

  return (
    <div className="flex-1">
      <div className="bg-white/45 text-gray-700 backdrop-blur-sm shadow-xl mx-auto my-5 p-4 rounded-lg max-w-lg">
        <h1 className="text-2xl font-bold mb-4">Gestion des Tags</h1>

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
