import { useState, useEffect, useRef } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store'

export default function Navbar() {
  const { user, isAuthenticated, logout } = useAuthStore()
  const [isProfileOpen, setIsProfileOpen] = useState(false)
  const [isNotifOpen, setIsNotifOpen] = useState(false)
  const [notifications, setNotifications] = useState([])
  const [notifCount, setNotifCount] = useState(0)
  const navigate = useNavigate()
  const dropdownRef = useRef(null)
  const notifRef = useRef(null)

  const handleLogout = () => {
    logout()
    navigate('/login')
  }

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

  const fetchNotifications = async () => {
    // TODO: Implement notification fetch
    setNotifications([])
  }

  useEffect(() => {
    if (isNotifOpen) {
      fetchNotifications()
    }
  }, [isNotifOpen])

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
                  <div className="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-[10000] border border-gray-200">
                    <div className="max-h-64 overflow-auto p-2">
                      {notifications.length > 0 ? (
                        notifications.map((notif) => (
                          <div key={notif.id} className="p-2 hover:bg-gray-50 rounded">
                            {notif.message}
                          </div>
                        ))
                      ) : (
                        <div className="text-sm text-gray-500 p-2">Aucune notification</div>
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
                    src={user?.profileImage ? `/profile_images/${user.profileImage}` : '/images/default-profile.png'}
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
