import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useAuthStore } from '../store'

export default function Home() {
  const { user, isAuthenticated } = useAuthStore()
  const [showPopup, setShowPopup] = useState(true)
  const popupDuration = 10000

  useEffect(() => {
    const timer = setTimeout(() => {
      setShowPopup(false)
    }, popupDuration)

    return () => clearTimeout(timer)
  }, [])

  const closePopup = () => {
    setShowPopup(false)
  }

  return (
    <>
      {/* Popup notification */}
      {showPopup && (
        <div className="fixed top-32 right-5 bg-red-200 text-gray-700 max-w-80 px-6 py-4 rounded-lg shadow-lg flex items-start z-50 transition-opacity duration-500 overflow-hidden">
          <span className="mr-4">
            <b>Important : </b>Ce site est toujours en développement, certaines fonctionnalités ne sont donc pas encore disponibles.<br />
            Si vous rencontrez des bugs ou des problèmes, n'hésitez pas à nous contacter à l'adresse suivante : maieuticprojet@proton.me
          </span>
          <button
            onClick={closePopup}
            className="ml-auto text-gray-700 font-bold text-xl leading-none hover:text-black"
          >
            &times;
          </button>
          <div
            className="absolute left-0 bottom-0 h-1 bg-red-400 transition-all duration-100"
            style={{
              width: `${((popupDuration - (Date.now() % popupDuration)) / popupDuration) * 100}%`,
              animation: `progress ${popupDuration}ms linear`
            }}
          ></div>
        </div>
      )}

      <div className="flex-1 flex flex-col items-center max-w-7xl mx-auto my-10">
        <h1 className="mt-6 text-center text-4xl font-black text-gray-900 tracking-tight">MaieutIC</h1>
        <p className="mt-2 text-center text-gray-600">La plateforme facilitant l'échange entre doctorants !</p>
        
        <ul className="flex flex-wrap justify-center my-5">
          {/* Bureau */}
          <li>
            <Link
              to={isAuthenticated ? `/profile/${user?.username}` : '/login'}
              className="relative block group"
            >
              <img
                src="/images/bureau.jpg"
                alt="bureau"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Bureau</div>
                <div>Profil</div>
              </div>
            </Link>
          </li>

          {/* Salon */}
          <li>
            <Link to="/forums/General" className="relative block group">
              <img
                src="/images/salon.jpg"
                alt="salon"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Salon</div>
                <div>Forums</div>
              </div>
            </Link>
          </li>

          {/* Salle à manger */}
          <li>
            <Link to="/maps" className="relative block group">
              <img
                src="/images/salle_a_manger.png"
                alt="salle à manger"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Salle à manger</div>
                <div>Cartes de concepts et liens</div>
              </div>
            </Link>
          </li>

          {/* Bibliothèque */}
          <li>
            <Link to="/library" className="relative block group">
              <img
                src="/images/bibliotèque.jpg"
                alt="bibliotèque"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Bibliothèque</div>
                <div>Auteurs et oeuvres</div>
              </div>
            </Link>
          </li>

          {/* Cuisine */}
          <li>
            <Link to="/methodology" className="relative block group">
              <img
                src="/images/cuisine.jpg"
                alt="cuisine"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Cuisine</div>
                <div>Partie méthodologie</div>
              </div>
            </Link>
          </li>

          {/* Détente */}
          <li>
            <Link to="/chill" className="relative block group">
              <img
                src="/images/détente.jpeg"
                alt="salle à manger"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Détente</div>
                <div>Gestion du stress, sophrologie</div>
              </div>
            </Link>
          </li>

          {/* Administratif */}
          <li>
            <Link to="/administrative" className="relative block group">
              <img
                src="/images/administratif.png"
                alt="administratif"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Administratif</div>
              </div>
            </Link>
          </li>

          {/* Discussions */}
          <li>
            <Link to={isAuthenticated ? '/chat' : '/login'} className="relative block group">
              <img
                src="/images/discussion.jpg"
                alt="discussion"
                className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
              />
              <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold">Discussions</div>
                <div>Messagerie</div>
              </div>
            </Link>
          </li>

          {/* Admin Interface (seulement pour les admins) */}
          {isAuthenticated && user?.userType === 1 && (
            <li>
              <Link to="/admin" className="relative block group">
                <img
                  src="/images/administration.jpg"
                  alt="interface administrateur"
                  className="max-h-64 m-3 rounded-lg shadow-xl shadow-black/40 brightness-50 transition-all duration-200 group-hover:brightness-100"
                />
                <div className="absolute bottom-5 left-9 text-white transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold">Interface Administrateur</div>
                  <div>Gestion administrative</div>
                </div>
              </Link>
            </li>
          )}
        </ul>
      </div>

      <style>{`
        @keyframes progress {
          from { width: 100%; }
          to { width: 0%; }
        }
      `}</style>
    </>
  )
}
