import { useState, useMemo } from 'react'
import { allData } from '../hooks/useFilteredData'

const CNU_LABELS = {
  '04': 'Science politique', '06': 'Sciences de gestion',
  '17': 'Philosophie', '18': 'Arts', '19': 'Sociologie',
  '20': 'Ethnologie', '70': "Sciences de l'éducation",
  '71': 'Info-com', '72': 'Épistémologie',
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
    <div className="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
      <p className="text-sm font-semibold text-slate-800 leading-snug">
        {highlight(thesis.titre, query)}
      </p>

      <div className="flex flex-wrap gap-2 text-xs">
        <span className="bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-full px-2.5 py-0.5 font-medium">
          {thesis.annee}
        </span>
        <span className="bg-slate-100 text-slate-600 rounded-full px-2.5 py-0.5">
          {thesis.etablissement_norm}
        </span>
        <span className="bg-amber-50 text-amber-700 border border-amber-100 rounded-full px-2.5 py-0.5">
          {thesis.cnu} – {CNU_LABELS[thesis.cnu] || thesis.cnu}
        </span>
        {thesis.accessible &&
          <span className="bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full px-2.5 py-0.5">Open Access</span>
        }
      </div>

      {thesis.directeurs?.length > 0 && (
        <div className="text-xs text-slate-500">
          <span className="font-medium text-slate-600">Directeur{thesis.directeurs.length > 1 ? 's' : ''} : </span>
          {thesis.directeurs.join(', ')}
        </div>
      )}
    </div>
  )
}

export default function Search() {
  const [query, setQuery] = useState('')

  const results = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (q.length < 2) return []
    return allData
      .filter(d =>
        d.titre?.toLowerCase().includes(q)
      )
      .sort((a, b) => b.annee - a.annee)
  }, [query])

  const hasQuery = query.trim().length >= 2

  return (
    <div className="p-8 flex flex-col gap-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-800">Recherche</h2>
        <p className="text-slate-500 text-sm mt-1">Recherchez par mot-clé dans les titres</p>
      </div>

      {/* Barre de recherche */}
      <div className="relative">
        <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          value={query}
          onChange={e => setQuery(e.target.value)}
          placeholder="Ex : philosophie, intelligence artificielle, sport…"
          className="w-full pl-12 pr-4 py-4 text-base border border-slate-300 rounded-2xl bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent placeholder-slate-400"
          autoFocus
        />
        {query && (
          <button
            onClick={() => setQuery('')}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
          >
            ✕
          </button>
        )}
      </div>

      {/* Résultats */}
      {hasQuery && (
        <div className="flex flex-col gap-3">
          <p className="text-sm text-slate-500">
            {results.length === 0
              ? 'Aucun résultat'
              : <><span className="font-semibold text-slate-700">{results.length.toLocaleString('fr-FR')}</span> thèse{results.length > 1 ? 's' : ''} trouvée{results.length > 1 ? 's' : ''}</>
            }
          </p>

          {results.slice(0, 200).map(thesis => (
            <ThesisCard key={thesis.id} thesis={thesis} query={query.trim()} />
          ))}

          {results.length > 200 && (
            <p className="text-center text-sm text-slate-400 py-4">
              Affichage des 200 premiers résultats sur {results.length.toLocaleString('fr-FR')} — affinez votre recherche
            </p>
          )}
        </div>
      )}

      {!hasQuery && (
        <div className="flex flex-col items-center justify-center py-20 text-slate-400 gap-3">
          <svg className="w-12 h-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <p className="text-sm">Tapez au moins 2 caractères pour lancer la recherche</p>
        </div>
      )}
    </div>
  )
}
