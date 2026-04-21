import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'

export default function Settings() {
  const [activeTab, setActiveTab] = useState('blocked')
  const queryClient = useQueryClient()

  // Fetch blocked users
  const { data: blockedData, isLoading, error } = useQuery({
    queryKey: ['blockedUsers'],
    queryFn: async () => {
      const response = await api.get('/blocked-users')
      return response.data
    }
  })

  // Unblock user mutation
  const unblockMutation = useMutation({
    mutationFn: async (userId) => {
      const response = await api.post(`/block/toggle/${userId}`)
      return response.data
    },
    onSuccess: () => {
      // Refresh blocked users list
      queryClient.invalidateQueries({ queryKey: ['blockedUsers'] })
    }
  })

  // Fetch RGPD data-access requests for current user
  const { data: dataAccessData, isLoading: dataAccessLoading } = useQuery({
    queryKey: ['my-data-access-requests'],
    queryFn: async () => {
      const response = await api.get('/privacy/data-access-requests/me')
      return response.data
    }
  })

  // Create RGPD data-access request
  const createDataAccessRequestMutation = useMutation({
    mutationFn: async () => {
      const response = await api.post('/privacy/data-access-requests')
      return response.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['my-data-access-requests'] })
      alert('Votre demande RGPD a été envoyée à l\'administration.')
    },
    onError: (err) => {
      alert(err.response?.data?.error || 'Impossible d\'envoyer la demande RGPD')
    }
  })

  const handleUnblock = async (userId) => {
    if (!confirm('Voulez-vous vraiment débloquer cet utilisateur ?')) {
      return
    }
    
    try {
      await unblockMutation.mutateAsync(userId)
    } catch (err) {
      console.error('Erreur lors du déblocage:', err)
      alert('Erreur lors du déblocage de l\'utilisateur')
    }
  }

  const blockedUsers = blockedData?.blockedUsers || []
  const dataAccessRequests = dataAccessData?.requests || []
  const latestDataAccessRequest = dataAccessRequests[0] || null

  return (
    <div className="flex-1 bg-blue-50 py-8">
      <div className="max-w-3xl mx-auto bg-white/45 p-6 rounded-lg shadow-xl">
        <h1 className="text-2xl font-bold mb-4">Paramètres</h1>

        <div className="flex border-b border-gray-300 mb-6">
          <button
            onClick={() => setActiveTab('blocked')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'blocked'
                ? 'text-blue-600 border-b-2 border-blue-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Utilisateurs bloqués
          </button>
          <button
            onClick={() => setActiveTab('privacy')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'privacy'
                ? 'text-blue-600 border-b-2 border-blue-600'
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Confidentialité et sécurité
          </button>
        </div>

        {activeTab === 'blocked' && (
        <section className="mb-6">
          <h2 className="text-lg font-semibold mb-2">Utilisateurs bloqués</h2>
          
          {isLoading && (
            <div className="text-gray-600">Chargement...</div>
          )}

          {error && (
            <div className="text-red-600">
              Erreur lors du chargement des utilisateurs bloqués
            </div>
          )}

          {!isLoading && !error && blockedUsers.length === 0 && (
            <div className="text-gray-600">Vous n'avez bloqué personne.</div>
          )}

          {!isLoading && !error && blockedUsers.length > 0 && (
            <ul className="space-y-3">
              {blockedUsers.map((user) => (
                <li
                  key={user.id}
                  className="flex items-center justify-between p-3 bg-white rounded border"
                >
                  <div className="flex items-center gap-3">
                    <img
                      src={user.profileImage || '/images/default-profile.png'}
                      alt="avatar"
                      className="w-10 h-10 rounded-full object-cover"
                    />
                    <div>
                      <div className="font-medium">
                        {user.firstName} {user.lastName}{' '}
                        <span className="text-sm text-gray-500">
                          (@{user.username})
                        </span>
                      </div>
                      <div className="text-sm text-gray-500">
                        {user.affiliationLocation}
                      </div>
                    </div>
                  </div>
                  <div>
                    <button
                      className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                      onClick={() => handleUnblock(user.id)}
                      disabled={unblockMutation.isPending}
                    >
                      {unblockMutation.isPending ? 'Traitement...' : 'Débloquer'}
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          )}
          </section>
        )}

        {activeTab === 'privacy' && (
          <section className="mb-6 space-y-4">
            <h2 className="text-lg font-semibold">Confidentialité et sécurité</h2>
            <p className="text-sm text-gray-700">
              Conformément au RGPD, vous pouvez demander l\'accès à l\'ensemble de vos données personnelles.
            </p>

            <div className="rounded-lg border border-gray-200 bg-white p-4">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <div className="font-medium text-gray-900">Demande d\'accès à mes données</div>
                  <div className="text-sm text-gray-600">
                    Un administrateur traitera votre demande et pourra exporter vos données en JSON.
                  </div>
                </div>
                <button
                  onClick={() => createDataAccessRequestMutation.mutate()}
                  disabled={createDataAccessRequestMutation.isPending || latestDataAccessRequest?.status === 'pending'}
                  className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                >
                  {createDataAccessRequestMutation.isPending ? 'Envoi...' : 'Demander mes données'}
                </button>
              </div>
            </div>

            <div className="rounded-lg border border-gray-200 bg-white p-4">
              <h3 className="font-medium text-gray-900 mb-3">Historique de mes demandes</h3>
              {dataAccessLoading ? (
                <div className="text-gray-600 text-sm">Chargement...</div>
              ) : dataAccessRequests.length === 0 ? (
                <div className="text-gray-600 text-sm">Aucune demande envoyée.</div>
              ) : (
                <ul className="space-y-2">
                  {dataAccessRequests.map((request) => (
                    <li key={request.id} className="flex items-center justify-between border rounded p-3">
                      <div>
                        <div className="text-sm font-medium">Demande #{request.id}</div>
                        <div className="text-xs text-gray-600">
                          Envoyée le {new Date(request.createdAt).toLocaleString('fr-FR')}
                        </div>
                        {request.adminNote && (
                          <div className="text-xs text-indigo-700 mt-1">Note admin: {request.adminNote}</div>
                        )}
                      </div>
                      <span className={`px-2 py-1 rounded text-xs font-medium ${
                        request.status === 'pending'
                          ? 'bg-yellow-100 text-yellow-800'
                          : request.status === 'processed'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-gray-100 text-gray-700'
                      }`}>
                        {request.status}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </section>
        )}
        </div>
      </div>
  )
}
