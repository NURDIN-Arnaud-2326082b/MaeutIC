import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'

export default function Settings() {
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

  return (
    <div className="flex-1 bg-blue-50 py-8">
      <div className="max-w-3xl mx-auto bg-white/45 p-6 rounded-lg shadow-xl">
        <h1 className="text-2xl font-bold mb-4">Paramètres</h1>

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
        </div>
      </div>
  )
}
