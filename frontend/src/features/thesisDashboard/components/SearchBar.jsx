import { useState } from 'react'

const CNU_LABELS = {
  '04': 'Science politique',
  '06': 'Sciences de gestion',
  '17': 'Philosophie',
  '18': 'Arts',
  '19': 'Sociologie',
  '20': 'Ethnologie',
  '70': "Sciences de l'éducation",
  '71': 'Info-com',
  '72': 'Épistémologie',
}

function highlight(text, query) {
  if (!query || !text) return text
  const parts = text.split(new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'))
  return parts.map((part, i) =>
    part.toLowerCase() === query.toLowerCase()
      ? <mark key={i} className="bg-yellow-200 text-yellow-900 rounded px-0.5">{part}</mark>
      : part
  )
}

function ThesisCard({ thesis, query }) {
  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-col gap-2 hover:shadow-md transition-shadow">
      <p className="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug">
        {highlight(thesis.titre, query)}
      </p>
      <div className="flex flex-wrap gap-2 text-xs">
        <span className="bg-canard-50 text-canard-700 border border-canard-100 rounded-full px-2.5 py-0.5 font-medium">{thesis.annee}</span>
        <span className="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-full px-2.5 py-0.5">{thesis.etablissement_norm}</span>
        <span className="bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-100 dark:border-amber-800/50 rounded-full px-2.5 py-0.5">{thesis.cnu} – {CNU_LABELS[thesis.cnu] || thesis.cnu}</span>
        {thesis.accessible && <span className="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/50 rounded-full px-2.5 py-0.5">Open Access</span>}
      </div>
      {thesis.directeurs?.length > 0 && (
        <p className="text-xs text-slate-500 dark:text-slate-400">
          <span className="font-medium text-slate-600 dark:text-slate-300">Directeur{thesis.directeurs.length > 1 ? 's' : ''} : </span>
          {thesis.directeurs.join(', ')}
        </p>
      )}
    </div>
  )
}

export default function SearchBar({ query, onQueryChange, data }) {
  const [showResults, setShowResults] = useState(false)

  return (
    <div id="tour-search" className="z-20 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 px-4 md:px-8 pt-4 md:pt-6 pb-3 md:pb-4 flex flex-col gap-3">
      {/* Input */}
      <div className="relative">
        <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          value={query}
          onChange={e => onQueryChange(e.target.value)}
          placeholder="Rechercher des thèses…"
          className="w-full pl-12 pr-10 py-3 text-base border border-slate-300 dark:border-slate-700 rounded-2xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-canard-400 focus:border-transparent placeholder-slate-400 dark:placeholder-slate-600 transition-colors"
        />
        {query && (
          <button
            onClick={() => { onQueryChange(''); setShowResults(false) }}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300"
          >✕</button>
        )}
      </div>

      {/* Résultats */}
      {query.trim().length >= 2 && (
        <div className="flex flex-col gap-3">
          <button
            onClick={() => setShowResults(v => !v)}
            className="flex items-center justify-between w-full bg-canard-50 dark:bg-canard-900/20 border border-canard-200 dark:border-canard-800/50 rounded-2xl px-5 py-3 text-left hover:bg-canard-100 dark:hover:bg-canard-900/30 transition-colors"
          >
            <span className="text-sm text-canard-700 dark:text-canard-300">
              {data.length === 0
                ? 'Aucun résultats pour votre recherche'
                : <><span className="font-bold text-canard-800 dark:text-canard-200">{data.length.toLocaleString('fr-FR')}</span> thèse{data.length > 1 ? 's' : ''} trouvée{data.length > 1 ? 's' : ''}</>
              }
            </span>
            <svg
              className={`w-4 h-4 text-canard-500 transition-transform ${showResults ? 'rotate-180' : ''}`}
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          {showResults && data.length > 0 && (
            <div className="max-h-96 overflow-y-auto flex flex-col gap-3 pr-1">
              {data.slice(0, 100).map(thesis => (
                <ThesisCard key={thesis.id} thesis={thesis} query={query.trim()} />
              ))}
              {data.length > 100 && (
                <p className="text-center text-sm text-slate-400 py-2">
                  Affichage des 100 premiers résultats — affinez votre recherche
                </p>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  )
}
