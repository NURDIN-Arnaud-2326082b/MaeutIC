import { useMemo } from 'react'
import { assetPath } from '../../utils/assetPath'

const NAV = [
  { id: 'overview',      label: 'Vue d\'ensemble' },
  { id: 'temporel',      label: 'Évolution temporelle' },
  { id: 'concentration', label: 'Concentration' },
  { id: 'reseau',        label: 'Réseau' },
  { id: 'disciplines',   label: 'Regroupement par mot clé' },
]

export default function Sidebar({ filters, allData = [], onChange, activePage, onNavigate, isDarkMode, isTutorialActive, isOpen, onClose }) {
  const { years, cnus, etabs } = useMemo(() => {
    const safeData = Array.isArray(allData) ? allData : []
    return {
      years: [...new Set(safeData.map(d => d.annee))].sort(),
      cnus: [...new Set(safeData.map(d => d.cnu_norm).filter(Boolean))].sort(),
      etabs: [...new Set(safeData.map(d => d.etablissement_norm).filter(Boolean))].sort(),
    }
  }, [allData])

  const set = (key) => (e) => {
    if (isTutorialActive) return // bloquer les filtres pendant le tutoriel
    onChange({ ...filters, [key]: e.target.value || null })
  }

  const handleNavigate = (id) => {
    if (isTutorialActive) return // bloquer la navigation pendant le tutoriel
    onNavigate(id)
    onClose()
  }

  return (
    <>
      {/* Backdrop mobile */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/40 z-30 md:hidden"
          onClick={onClose}
        />
      )}

      <aside className={`
        fixed md:static inset-y-0 left-0 z-40
        w-64 shrink-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800
        h-screen md:h-auto md:rounded-2xl md:m-4 md:max-h-[calc(100vh-2rem)]
        flex flex-col transition-transform duration-300 ease-in-out
        ${isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}
      `}>
        {/* Bouton fermer (mobile uniquement) */}
        <button
          onClick={onClose}
          className="md:hidden absolute top-3 right-3 p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 z-50"
          aria-label="Fermer le menu"
        >
          <svg className="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <div className="px-6 pt-6 pb-6 overflow-y-auto custom-scrollbar flex flex-col gap-6 flex-1">
          <h1 className="text-xl font-bold text-slate-800 dark:text-slate-100 leading-tight">Cartographie des thèses</h1>

          <nav id="tour-nav" className="flex flex-col gap-1">
            {NAV.map(({ id, label, soon }) => (
              <button
                key={id}
                disabled={soon || isTutorialActive}
                onClick={() => !soon && handleNavigate(id)}
                className={`text-sm text-left rounded-lg px-3 py-2 transition-colors flex items-center justify-between
                  ${isTutorialActive && activePage !== id
                    ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed opacity-50'
                    : soon
                      ? 'text-slate-300 dark:text-slate-600 cursor-default'
                      : activePage === id
                        ? 'bg-canard-50 text-canard-700 font-medium dark:bg-canard-900/30 dark:text-canard-400'
                        : 'text-slate-600 dark:text-slate-300 hover:text-canard-600 dark:hover:text-canard-400 hover:bg-canard-50 dark:hover:bg-slate-800'
                  }`}
              >
                {label}
                {soon && <span className="text-xs bg-slate-100 text-slate-400 rounded px-1.5 py-0.5">bientôt</span>}
              </button>
            ))}
          </nav>

          <div id="tour-filters" className="border-t border-slate-100 dark:border-slate-800 pt-4 flex flex-col gap-4">
            <p className="text-xs font-semibold uppercase tracking-widest text-slate-400">Filtres</p>

            <label className="flex flex-col gap-1">
              <span className="text-xs text-slate-500">Année</span>
              <select value={filters.annee ?? ''} onChange={set('annee')}
                className="text-sm border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 outline-none">
                <option value="">Toutes</option>
                {years.map(y => <option key={y} value={y}>{y}</option>)}
              </select>
            </label>

            {activePage !== 'disciplines' && (
              <label className="flex flex-col gap-1">
                <span className="text-xs text-slate-500">Section CNU</span>
                <select value={filters.cnu ?? ''} onChange={set('cnu')}
                  className="text-sm border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 outline-none">
                  <option value="">Toutes</option>
                  {cnus.map(c => <option key={c} value={c}>{c}</option>)}
                </select>
              </label>
            )}

            {activePage !== 'disciplines' && (
              <label className="flex flex-col gap-1">
                <span className="text-xs text-slate-500">Établissement</span>
                <select value={filters.etablissement ?? ''} onChange={set('etablissement')}
                  className="text-sm border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 outline-none">
                  <option value="">Tous</option>
                  {etabs.map(e => <option key={e} value={e}>{e}</option>)}
                </select>
              </label>
            )}

            {(filters.annee || filters.cnu || filters.etablissement) && (
              <button onClick={() => onChange({ ...filters, annee: null, cnu: null, etablissement: null })}
                className="text-xs text-red-500 hover:text-red-700 text-left">
                Réinitialiser les filtres
              </button>
            )}
          </div>
        </div>

      </aside>
    </>
  )
}
