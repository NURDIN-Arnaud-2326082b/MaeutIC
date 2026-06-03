import { Link } from 'react-router-dom'
import { useAuthStore } from '../store'

export default function Home() {
  const { user, isAuthenticated } = useAuthStore()

  return (
    <>
      <div className="relative overflow-hidden">
        <div className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(1,141,150,0.16),transparent_28%),radial-gradient(circle_at_top_right,rgba(237,138,39,0.14),transparent_24%),linear-gradient(180deg,rgba(255,255,255,0.9),rgba(245,250,250,1))]" />

        <div className="relative flex-1 flex flex-col items-center max-w-7xl mx-auto px-4 py-8">
          <div className="mb-6 flex flex-col items-center text-center">
            <img
              src={`${import.meta.env.BASE_URL}images/logo2.png`}
              alt="M@ieutIC"
              className="mx-auto h-auto w-full max-w-full sm:max-w-[360px] object-contain"
            />
            <p className="mt-1 text-center text-lg font-semibold text-canard-600 md:text-xl">La plateforme par et pour les doctorant·es.</p>
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
                  className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
                />
                  <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                    <div className="text-xl font-bold text-white">Bureau</div>
                    <div className="text-sm text-white/90">Profil</div>
                  </div>
            </Link>
          </li>

          {/* Salon */}
          <li>
            <Link to="/forums/General" className="relative block group">
                <img
                src="/images/salon.jpg"
                alt="salon"
                className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Salon</div>
                  <div className="text-sm text-white/90">Forums</div>
                </div>
            </Link>
          </li>

          {/* Salle à manger */}
          <li>
            <Link to="/maps" className="relative block group">
                <img
                src="/images/salle_a_manger.png"
                alt="salle à manger"
                className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Salle à manger</div>
                  <div className="text-sm text-white/90">Cartes de concepts et liens</div>
                </div>
            </Link>
          </li>

          {/* Bibliothèque */}
          <li>
            <Link to="/library" className="relative block group">
                <img
                src="/images/bibliotèque.jpg"
                alt="bibliotèque"
                className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Bibliothèque</div>
                  <div className="text-sm text-white/90">Auteurs et oeuvres</div>
                </div>
            </Link>
          </li>

          {/* Cuisine */}
          <li>
            <Link to="/methodology" className="relative block group">
                <img
                src="/images/cuisine.jpg"
                alt="cuisine"
                className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Cuisine</div>
                  <div className="text-sm text-white/90">Partie méthodologie</div>
                </div>
            </Link>
          </li>

          {/* Détente */}
          <li>
            <Link to="/chill" className="relative block group">
                <img
                src="/images/détente.jpeg"
                alt="salle à manger"
                className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Détente</div>
                  <div className="text-sm text-white/90">Gestion du stress, sophrologie</div>
                </div>
            </Link>
          </li>

          {/* Administratif */}
          <li>
            <Link to="/administrative" className="relative block group">
                <img
                src="/images/administratif.png"
                alt="administratif"
                className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Administratif</div>
                </div>
            </Link>
          </li>

          {/* Discussions */}
          <li>
            <Link to={isAuthenticated ? '/chat' : '/login'} className="relative block group">
                <img
                src="/images/discussion.jpg"
                alt="discussion"
                className="h-48 md:h-64 w-full rounded-3xl border border-white/70 object-cover shadow-xl shadow-canard-950/10 brightness-[0.58] transition-all duration-300 group-hover:brightness-100 group-hover:-translate-y-1"
              />
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Discussions</div>
                  <div className="text-sm text-white/90">Messagerie</div>
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
                <div className="absolute bottom-5 left-5 rounded-2xl border border-transparent bg-transparent px-4 py-3 text-white shadow-lg transition-opacity duration-200 group-hover:opacity-0">
                  <div className="text-xl font-bold text-white">Interface Administrateur</div>
                  <div className="text-sm text-white/90">Gestion administrative</div>
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
