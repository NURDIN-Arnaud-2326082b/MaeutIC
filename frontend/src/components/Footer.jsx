export default function Footer() {
  return (
    <footer className="z-20 m-0 bg-white shadow-lg shadow-black/5">
      <div className="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4 h-40">
        {/* Navigation à gauche */}
        <nav className="flex flex-row">
          <div className="flex flex-col mr-14 h-full text-gray-700">
            <h2 className="font-medium mb-1">Navigation</h2>
            <a href="/" className="text-gray-700 hover:text-blue-600">Accueil</a>
            <a href="/forums/General" className="text-gray-700 hover:text-blue-600">Forums</a>
            <a href="/library" className="text-gray-700 hover:text-blue-600">Bibliothèque</a>
          </div>
          <div className="flex flex-col mr-14 h-full text-gray-700">
            <h2 className="font-medium mb-1">Code source</h2>
            <a href="https://github.com/NURDIN-Arnaud-2326082b/MaeutIC" target="_blank" rel="noopener noreferrer" className="text-gray-700 hover:text-blue-600">Github</a>
          </div>
          <div className="flex flex-col h-full text-gray-700">
            <h2 className="font-medium mb-1">Nous contacter</h2>
            <a href="mailto:maieuticprojet@proton.me" className="text-gray-700 hover:text-blue-600">Email</a>
          </div>
        </nav>

        {/* Logos à droite */}
        <div className="flex flex-row h-20 items-center">
          <img src="/images/Aix-Marseille-Universite-se-dote-dune-nouvelle-charte-graphique-et-dun-nouveau-logo.png" className="h-full mx-2" alt="AMU" />
          <img src="/images/cropped-cropped-cropped-cropped-cropped-cropped-cropped-Presentation1-1.jpg" className="h-full mx-2" alt="Logo 2" />
          <img src="/images/lerass_90.png" className="h-full mx-2" alt="LERASS" />
          <img src="/images/logo_udmpv.jpg" className="h-full mx-2" alt="UDMPV" />
        </div>
      </div>
    </footer>
  )
}
