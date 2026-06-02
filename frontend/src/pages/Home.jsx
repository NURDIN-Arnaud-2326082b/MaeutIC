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
      <div className="relative overflow-hidden">
        <div className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(1,141,150,0.16),transparent_28%),radial-gradient(circle_at_top_right,rgba(237,138,39,0.14),transparent_24%),linear-gradient(180deg,rgba(255,255,255,0.9),rgba(245,250,250,1))]" />
        {/* Popup notification */}
        {showPopup && (
        <div className="fixed top-28 right-5 z-50 max-w-80 overflow-hidden rounded-2xl border border-red-200 bg-red-200 px-6 py-4 text-gray-700 shadow-2xl shadow-red-950/10 backdrop-blur">
          <span className="mr-4 block text-sm leading-6">
            <b className="text-canard-800">Important :</b> Ce site est toujours en développement, certaines fonctionnalités ne sont donc pas encore disponibles.<br />
            Si vous rencontrez des bugs ou des problèmes, n'hésitez pas à nous contacter à l'adresse suivante : maieuticprojet@proton.me
          </span>
            <button
            onClick={closePopup}
            className="ml-auto mt-2 text-gray-700 font-bold text-xl leading-none hover:text-black"
          >
            &times;
          </button>
          <div
            className="absolute left-0 bottom-0 h-1 bg-red-400"
            style={{
              animation: `progress ${popupDuration}ms linear forwards`
            }}
          ></div>
        </div>
        )}

        <div className="relative flex-1 flex flex-col items-center max-w-7xl mx-auto px-4 py-12">
          <div className="mb-8 max-w-3xl rounded-3xl border border-white/70 bg-white/75 px-6 py-8 text-center shadow-2xl shadow-canard-950/10 backdrop-blur">
            <h1 className="brand-title text-center text-5xl leading-none text-canard-900 md:text-7xl">M@ieutIC</h1>
            <p className="mt-4 text-center text-lg text-slate-600 md:text-xl">La plateforme facilitant l'échange entre doctorants.</p>
          </div>

          <ul className="grid w-full grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            {/* Bureau */}
          <li>
            <Link
              to={isAuthenticated ? `/profile/${user?.username}` : '/login'}
              className="relative block group"
            >
              <img
                src="/images/bureau.jpg"
                alt="bureau"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Bureau</div>
                <div className="text-sm text-slate-600">Profil</div>
              </div>
            </Link>
          </li>

          {/* Salon */}
          <li>
            <Link to="/forums/General" className="relative block group">
              <img
                src="/images/salon.jpg"
                alt="salon"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Salon</div>
                <div className="text-sm text-slate-600">Forums</div>
              </div>
            </Link>
          </li>

          {/* Salle à manger */}
          <li>
            <Link to="/maps" className="relative block group">
              <img
                src="/images/salle_a_manger.png"
                alt="salle à manger"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Salle à manger</div>
                <div className="text-sm text-slate-600">Cartes de concepts et liens</div>
              </div>
            </Link>
          </li>

          {/* Bibliothèque */}
          <li>
            <Link to="/library" className="relative block group">
              <img
                src="/images/bibliotèque.jpg"
                alt="bibliotèque"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Bibliothèque</div>
                <div className="text-sm text-slate-600">Auteurs et oeuvres</div>
              </div>
            </Link>
          </li>

          {/* Cuisine */}
          <li>
            <Link to="/methodology" className="relative block group">
              <img
                src="/images/cuisine.jpg"
                alt="cuisine"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Cuisine</div>
                <div className="text-sm text-slate-600">Partie méthodologie</div>
              </div>
            </Link>
          </li>

          {/* Détente */}
          <li>
            <Link to="/chill" className="relative block group">
              <img
                src="/images/détente.jpeg"
                alt="salle à manger"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Détente</div>
                <div className="text-sm text-slate-600">Gestion du stress, sophrologie</div>
              </div>
            </Link>
          </li>

          {/* Administratif */}
          <li>
            <Link to="/administrative" className="relative block group">
              <img
                src="/images/administratif.png"
                alt="administratif"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Administratif</div>
              </div>
            </Link>
          </li>

          {/* Discussions */}
          <li>
            <Link to={isAuthenticated ? '/chat' : '/login'} className="relative block group">
              <img
                src="/images/discussion.jpg"
                alt="discussion"
                className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
              <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                <div className="text-xl font-bold text-canard-800">Discussions</div>
                <div className="text-sm text-slate-600">Messagerie</div>
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
                  className="h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
                />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-slate-900 shadow-lg backdrop-blur transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-canard-800">Interface Administrateur</div>
                  <div className="text-sm text-slate-600">Gestion administrative</div>
                </div>
              </Link>
            </li>
          )}
        </ul>
        </div>
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
