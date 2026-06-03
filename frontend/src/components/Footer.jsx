import { assetPath } from '../features/thesisDashboard/utils/assetPath'

export default function Footer() {
  return (
    <footer className="z-20 m-0 bg-white/90 backdrop-blur border-t-4 border-mandarine-500 shadow-[0_-12px_35px_rgba(1,109,118,0.06)]">
      <div className="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4 py-6">
        {/* Navigation à gauche */}
        <nav className="flex flex-col sm:flex-row w-full sm:w-auto gap-4 sm:gap-10 text-slate-700">
          <div className="flex flex-col">
            <h2 className="font-semibold mb-1 text-slate-900">Navigation</h2>
            <a href="/chill" className="text-slate-700 hover:text-canard-700">Détente</a>
            <a href="/administrative" className="text-slate-700 hover:text-canard-700">Administratif</a>
            <a href="/chat" className="text-slate-700 hover:text-canard-700">Discussions</a>
            <a href="/settings" className="text-slate-700 hover:text-canard-700">Paramètres</a>
          </div>
          <div className="flex flex-col">
             <br />
            <a href="/chill" className="text-slate-700 hover:text-canard-700">Détente</a>
            <a href="/administrative" className="text-slate-700 hover:text-canard-700">Administratif</a>
            <a href="/chat" className="text-slate-700 hover:text-canard-700">Discussions</a>
            <a href="/settings" className="text-slate-700 hover:text-canard-700">Paramètres</a>
          </div>
          <div className="flex flex-col">
            <h2 className="font-semibold mb-1 text-slate-900">Code source</h2>
            <a href="https://github.com/NURDIN-Arnaud-2326082b/MaeutIC" target="_blank" rel="noopener noreferrer" className="text-slate-700 hover:text-canard-700">GitHub</a>
          </div>
          <div className="flex flex-col">
            <h2 className="font-semibold mb-1 text-slate-900">Nous contacter</h2>
            <a href="mailto:maieuticprojet@proton.me" className="text-slate-700 hover:text-canard-700">Email</a>
          </div>
        </nav>

        {/* Logos à droite */}
        <div className="flex flex-row flex-wrap items-center gap-2 mt-4 sm:mt-0">
          <img src={assetPath('images/Aix-Marseille-Universite-se-dote-dune-nouvelle-charte-graphique-et-dun-nouveau-logo.png')} className="h-8 sm:h-12 md:h-16 mx-2" alt="AMU" />
          <img src={assetPath('images/cropped-cropped-cropped-cropped-cropped-cropped-cropped-Presentation1-1.jpg')} className="h-8 sm:h-12 md:h-16 mx-2" alt="Logo 2" />
          <img src={assetPath('images/lerass_90.png')} className="h-8 sm:h-12 md:h-16 mx-2" alt="LERASS" />
          <img src={assetPath('images/logo_udmpv.jpg')} className="h-8 sm:h-12 md:h-16 mx-2" alt="UDMPV" />
        </div>
      </div>
    </footer>
  )
}
