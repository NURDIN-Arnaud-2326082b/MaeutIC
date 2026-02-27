import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { useAuthStore } from '../store'
import { getResources, createResource, updateResource, deleteResource } from '../services/resourceApi'

export default function ResourcePage({ 
  page, 
  title, 
  description, 
  forumLinks // array of { link, text } objects or single { link, text } object
}) {
  const { user } = useAuthStore()
  const queryClient = useQueryClient()
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [showEditModal, setShowEditModal] = useState(false)
  const [editingResource, setEditingResource] = useState(null)
  const [formData, setFormData] = useState({ title: '', description: '', link: '' })

  const isAdmin = user?.userType === 1

  // Normalize forumLinks to always be an array
  const normalizedForumLinks = forumLinks 
    ? (Array.isArray(forumLinks) ? forumLinks : [forumLinks])
    : []

  // Fetch resources
  const { data: resourcesData, isLoading } = useQuery({
    queryKey: ['resources', page],
    queryFn: () => getResources(page)
  })

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data) => createResource(page, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['resources', page])
      setShowCreateModal(false)
      setFormData({ title: '', description: '', link: '' })
    }
  })

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }) => updateResource(page, id, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['resources', page])
      setShowEditModal(false)
      setEditingResource(null)
      setFormData({ title: '', description: '', link: '' })
    }
  })

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id) => deleteResource(page, id),
    onSuccess: () => {
      queryClient.invalidateQueries(['resources', page])
    }
  })

  const handleCreateSubmit = (e) => {
    e.preventDefault()
    createMutation.mutate(formData)
  }

  const handleEditSubmit = (e) => {
    e.preventDefault()
    updateMutation.mutate({ id: editingResource.id, data: formData })
  }

  const handleDelete = (id) => {
    if (confirm('Voulez-vous vraiment supprimer cette ressource ?')) {
      deleteMutation.mutate(id)
    }
  }

  const openCreateModal = () => {
    setFormData({ title: '', description: '', link: '' })
    setShowCreateModal(true)
  }

  const openEditModal = (resource) => {
    setEditingResource(resource)
    setFormData({
      title: resource.title,
      description: resource.description || '',
      link: resource.link
    })
    setShowEditModal(true)
  }

  const extractYoutubeId = (url) => {
    if (!url) return null
    
    // youtube.com/watch?v=VIDEO_ID
    if (url.includes('youtube.com/watch?v=')) {
      const match = url.match(/v=([^&]+)/)
      return match ? match[1] : null
    }
    
    // youtu.be/VIDEO_ID
    if (url.includes('youtu.be/')) {
      const match = url.match(/youtu\.be\/([^?]+)/)
      return match ? match[1] : null
    }
    
    return null
  }

  const resources = resourcesData?.resources || []

  return (
    <div className="flex-1 mx-auto my-8 max-w-3xl w-11/12 bg-white/45 rounded-xl shadow-md p-8">
      <h1 className="text-3xl font-bold mb-2 text-blue-900">{title}</h1>
      
      {normalizedForumLinks.length > 0 && (
        <div className="mb-4 flex flex-wrap gap-3">
          {normalizedForumLinks.map((forum, index) => (
            <Link
              key={index}
              to={forum.link}
              className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
            >
              {forum.text}
            </Link>
          ))}
        </div>
      )}

      <div className="text-gray-600 mb-6">
        {description}
      </div>

      {isAdmin && (
        <div className="mb-6">
          <button
            onClick={openCreateModal}
            className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
          >
            Ajouter une ressource
          </button>
        </div>
      )}

      {isLoading ? (
        <div className="text-center py-8">Chargement...</div>
      ) : (
        <ul className="space-y-5">
          {resources.length > 0 ? (
            resources.map((resource) => {
              const youtubeId = extractYoutubeId(resource.link)
              
              return (
                <li key={resource.id} className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow relative">
                  <div className="text-xl font-semibold text-blue-800 mb-1">{resource.title}</div>
                  <div className="text-gray-700 mb-3">{resource.description}</div>
                  
                  {resource.link && (
                    <>
                      {youtubeId && (
                        <a href={resource.link} target="_blank" rel="noopener noreferrer" className="block mb-2">
                          <img
                            src={`https://img.youtube.com/vi/${youtubeId}/hqdefault.jpg`}
                            alt="Miniature YouTube"
                            className="rounded-md w-full max-w-xs mx-auto mb-2 shadow"
                          />
                        </a>
                      )}
                      <a
                        href={resource.link}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-600 hover:underline"
                      >
                        Voir la ressource
                      </a>
                    </>
                  )}

                  {isAdmin && (
                    <div className="absolute top-2 right-2 flex space-x-1">
                      <button
                        onClick={() => openEditModal(resource)}
                        className="bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1 rounded text-xs font-semibold transition"
                        title="Modifier"
                      >
                        ✏️
                      </button>
                      <button
                        onClick={() => handleDelete(resource.id)}
                        className="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold transition"
                        title="Supprimer"
                      >
                        🗑️
                      </button>
                    </div>
                  )}
                </li>
              )
            })
          ) : (
            <li className="text-gray-600">Aucune ressource disponible pour le moment.</li>
          )}
        </ul>
      )}

      {/* Create Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50" onClick={() => setShowCreateModal(false)}>
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4" onClick={(e) => e.stopPropagation()}>
            <h2 className="text-2xl font-bold mb-4">Ajouter une ressource</h2>
            <form onSubmit={handleCreateSubmit}>
              <div className="mb-4">
                <label className="block text-sm font-medium mb-1">Titre</label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  className="w-full border border-gray-300 rounded px-3 py-2"
                  required
                />
              </div>
              <div className="mb-4">
                <label className="block text-sm font-medium mb-1">Description</label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  className="w-full border border-gray-300 rounded px-3 py-2"
                  rows="3"
                />
              </div>
              <div className="mb-4">
                <label className="block text-sm font-medium mb-1">Lien</label>
                <input
                  type="url"
                  value={formData.link}
                  onChange={(e) => setFormData({ ...formData, link: e.target.value })}
                  className="w-full border border-gray-300 rounded px-3 py-2"
                  required
                />
              </div>
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={createMutation.isPending}
                  className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                >
                  {createMutation.isPending ? 'Création...' : 'Créer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit Modal */}
      {showEditModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50" onClick={() => setShowEditModal(false)}>
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4" onClick={(e) => e.stopPropagation()}>
            <h2 className="text-2xl font-bold mb-4">Modifier la ressource</h2>
            <form onSubmit={handleEditSubmit}>
              <div className="mb-4">
                <label className="block text-sm font-medium mb-1">Titre</label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  className="w-full border border-gray-300 rounded px-3 py-2"
                  required
                />
              </div>
              <div className="mb-4">
                <label className="block text-sm font-medium mb-1">Description</label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  className="w-full border border-gray-300 rounded px-3 py-2"
                  rows="3"
                />
              </div>
              <div className="mb-4">
                <label className="block text-sm font-medium mb-1">Lien</label>
                <input
                  type="url"
                  value={formData.link}
                  onChange={(e) => setFormData({ ...formData, link: e.target.value })}
                  className="w-full border border-gray-300 rounded px-3 py-2"
                  required
                />
              </div>
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowEditModal(false)}
                  className="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={updateMutation.isPending}
                  className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                >
                  {updateMutation.isPending ? 'Modification...' : 'Modifier'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
