import { assetPath } from '../features/thesisDashboard/utils/assetPath'

export default function Footer() {
  return (
    <footer className="z-20 m-0 bg-white/90 backdrop-blur border-t-4 border-mandarine-500 shadow-[0_-12px_35px_rgba(1,109,118,0.06)]">
      <div className="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4 h-40">
        {/* Navigation à gauche */}
        <nav className="flex flex-row">
          <div className="flex flex-col mr-14 h-full text-slate-700">
            <h2 className="font-semibold mb-1 text-slate-900">Navigation</h2>
            <a href="/" className="text-slate-700 hover:text-canard-700">Accueil</a>
            <a href="/forums/General" className="text-slate-700 hover:text-canard-700">Forums</a>
            <a href="/library" className="text-slate-700 hover:text-canard-700">Bibliothèque</a>
          </div>
          <div className="flex flex-col mr-14 h-full text-slate-700">
            <h2 className="font-semibold mb-1 text-slate-900">Code source</h2>
            <a href="https://github.com/NURDIN-Arnaud-2326082b/MaeutIC" target="_blank" rel="noopener noreferrer" className="text-slate-700 hover:text-canard-700">GitHub</a>
          </div>
          <div className="flex flex-col h-full text-slate-700">
            <h2 className="font-semibold mb-1 text-slate-900">Nous contacter</h2>
            <a href="mailto:maieuticprojet@proton.me" className="text-slate-700 hover:text-canard-700">Email</a>
          </div>
        </nav>

        {/* Logos à droite */}
        <div className="flex flex-row h-20 items-center">
          <img src={assetPath('images/Aix-Marseille-Universite-se-dote-dune-nouvelle-charte-graphique-et-dun-nouveau-logo.png')} className="h-full mx-2" alt="AMU" />
          <img src={assetPath('images/cropped-cropped-cropped-cropped-cropped-cropped-cropped-Presentation1-1.jpg')} className="h-full mx-2" alt="Logo 2" />
          <img src={assetPath('images/lerass_90.png')} className="h-full mx-2" alt="LERASS" />
          <img src={assetPath('images/logo_udmpv.jpg')} className="h-full mx-2" alt="UDMPV" />
        </div>
      </div>
    </footer>
  )
}
