import { useState, useEffect, useRef } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store'
import { getNotifications, acceptNetworkRequest, declineNetworkRequest } from '../services/networkApi'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'

export default function Navbar() {
  const { user, isAuthenticated, logout } = useAuthStore()
  const [isProfileOpen, setIsProfileOpen] = useState(false)
  const [isNotifOpen, setIsNotifOpen] = useState(false)
  const navigate = useNavigate()
  const dropdownRef = useRef(null)
  const notifRef = useRef(null)
  const queryClient = useQueryClient()

  const handleLogout = () => {
    logout()
    navigate('/login')
  }

  // Fetch notifications
  const { data: notificationsData } = useQuery({
    queryKey: ['notifications'],
    queryFn: getNotifications,
    enabled: isAuthenticated,
    refetchInterval: 30000, // Poll every 30 seconds
  })

  const notifications = notificationsData?.notifications || []
  const notifCount = notificationsData?.unread || 0

  // Accept network request
  const acceptMutation = useMutation({
    mutationFn: acceptNetworkRequest,
    onSuccess: () => {
      queryClient.invalidateQueries(['notifications'])
    },
  })

  // Decline network request
  const declineMutation = useMutation({
    mutationFn: declineNetworkRequest,
    onSuccess: () => {
      queryClient.invalidateQueries(['notifications'])
    },
  })

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsProfileOpen(false)
      }
      if (notifRef.current && !notifRef.current.contains(event.target)) {
        setIsNotifOpen(false)
      }
    }

    document.addEventListener('click', handleClickOutside)
    return () => document.removeEventListener('click', handleClickOutside)
  }, [])

  return (
    <nav className="sticky top-0 bg-white shadow-lg shadow-black/5" style={{ isolation: 'isolate', zIndex: 2147483647, pointerEvents: 'auto' }}>
      <div className="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4 h-20">
        {/* Logo */}
        <div className="flex-shrink-0 h-full">
          <Link to="/">
            <img src="/images/logo.png" alt="logo" className="h-full" />
          </Link>
        </div>

        {/* Right side */}
        <div className="flex items-center space-x-4 ml-auto">
          {isAuthenticated ? (
            <>
              {/* Notifications */}
              <div className="relative mr-3" ref={notifRef}>
                <button
                  onClick={() => setIsNotifOpen(!isNotifOpen)}
                  className="focus:outline-none"
                  aria-label="Notifications"
                  title="Notifications"
                  type="button"
                >
                  <svg className="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                  </svg>
                </button>
                {notifCount > 0 && (
                  <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1">
                    {notifCount}
                  </span>
                )}

                {isNotifOpen && (
                  <div className="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-[10000] border border-gray-200">
                    <div className="p-3 border-b border-gray-200">
                      <h3 className="font-semibold text-gray-800">Notifications</h3>
                    </div>
                    <div className="max-h-96 overflow-y-auto">
                      {notifications.length > 0 ? (
                        notifications.map((notif) => (
                          <div key={notif.id} className={`p-3 border-b border-gray-100 ${!notif.isRead ? 'bg-blue-50' : ''}`}>
                            <div className="flex items-start gap-3">
                              {notif.sender && (
                                <img
                                  src={notif.sender.profileImage || '/images/default-profile.png'}
                                  alt={notif.sender.username}
                                  className="w-10 h-10 rounded-full flex-shrink-0"
                                />
                              )}
                              <div className="flex-1 min-w-0">
                                <p className="text-sm text-gray-800">
                                  {notif.type === 'network_request' ? (
                                    <>
                                      <strong>{notif.sender?.username}</strong> souhaite rejoindre votre réseau
                                    </>
                                  ) : (
                                    notif.data?.message || 'Nouvelle notification'
                                  )}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                  {new Date(notif.createdAt).toLocaleString('fr-FR')}
                                </p>
                                
                                {notif.type === 'network_request' && notif.status === 'pending' && (
                                  <div className="flex gap-2 mt-2">
                                    <button
                                      onClick={() => acceptMutation.mutate(notif.id)}
                                      disabled={acceptMutation.isLoading}
                                      className="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                    >
                                      Accepter
                                    </button>
                                    <button
                                      onClick={() => declineMutation.mutate(notif.id)}
                                      disabled={declineMutation.isLoading}
                                      className="px-3 py-1 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 disabled:opacity-50"
                                    >
                                      Refuser
                                    </button>
                                  </div>
                                )}
                              </div>
                            </div>
                          </div>
                        ))
                      ) : (
                        <div className="text-sm text-gray-500 p-4 text-center">Aucune notification</div>
                      )}
                    </div>
                  </div>
                )}
              </div>

              {/* Profile dropdown */}
              <div className="relative flex items-center" ref={dropdownRef}>
                <button
                  onClick={() => setIsProfileOpen(!isProfileOpen)}
                  className="focus:outline-none"
                  type="button"
                  aria-haspopup="true"
                  aria-expanded={isProfileOpen}
                >
                  <img
                    src={user?.profileImage || '/images/default-profile.png'}
                    alt="Profil"
                    className="w-10 h-10 rounded-full"
                  />
                </button>
                {isProfileOpen && (
                  <div className="absolute top-full mt-2 w-48 bg-white rounded-lg shadow-xl z-[10000] border border-gray-200">
                    <Link
                      to={`/profile/${user?.username}`}
                      className="block px-4 py-2 text-black hover:bg-gray-100"
                      onClick={() => setIsProfileOpen(false)}
                    >
                      Mon Profil
                    </Link>
                    <Link
                      to="/settings"
                      className="block px-4 py-2 text-black hover:bg-gray-100"
                      onClick={() => setIsProfileOpen(false)}
                    >
                      Paramètres
                    </Link>
                    <button
                      onClick={() => {
                        setIsProfileOpen(false)
                        handleLogout()
                      }}
                      className="block w-full text-left px-4 py-2 text-black hover:bg-gray-100"
                    >
                      Se Déconnecter
                    </button>
                  </div>
                )}
              </div>
            </>
          ) : (
            <>
              <button
                type="button"
                onClick={() => navigate('/login')}
                className="text-gray-700 hover:text-blue-600 font-medium"
              >
                Se connecter
              </button>
              <button
                type="button"
                onClick={() => navigate('/register')}
                className="text-white bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-indigo-500 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none"
              >
                S'inscrire
              </button>
            </>
          )}
        </div>
      </div>
    </nav>
  )
}
