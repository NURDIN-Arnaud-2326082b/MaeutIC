import { useState } from 'react'
import Sidebar from './components/layout/Sidebar'
import SearchBar from './components/SearchBar'
import Overview from './pages/Overview'
import Concentration from './pages/Concentration'
import Temporel from './pages/Temporel'
import Reseau from './pages/Reseau'
import Disciplines from './pages/Disciplines'
import { useFilteredData } from './hooks/useFilteredData'
import { useThesisData } from './hooks/useThesisData'
import './styles.css'

const NAV_LABELS = {
  overview:      'Vue d\'ensemble',
  temporel:      'Évolution temporelle',
  concentration: 'Concentration',
  reseau:        'Réseau',
  disciplines:   'Regroupement par mot clé',
}

export default function App() {
  const [page, setPage] = useState('overview')
  const [filters, setFilters] = useState({ annee: null, cnu: null, etablissement: null, query: '' })
  const { data: allData, loading, error } = useThesisData()
  const data = useFilteredData(allData, filters)
  const isDarkMode = false // Light mode only
  const [isSidebarOpen, setIsSidebarOpen] = useState(false)

  if (loading) {
    return (
      <div className={`thesis-dashboard-root flex items-center justify-center h-full min-h-0 bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-300 ${isDarkMode ? 'dark' : ''}`}>
        Chargement du dashboard…
      </div>
    )
  }

  if (error) {
    return (
      <div className={`thesis-dashboard-root flex items-center justify-center h-full min-h-0 bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-300 ${isDarkMode ? 'dark' : ''}`}>
        Impossible de charger thesis-dashboard-data.json
      </div>
    )
  }

  return (
    <div className={`thesis-dashboard-root flex h-full min-h-0 overflow-hidden bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors ${isDarkMode ? 'dark' : ''}`}>
      <Sidebar
        filters={filters}
        allData={allData}
        onChange={setFilters}
        activePage={page}
        onNavigate={setPage}
        isDarkMode={isDarkMode}
        isTutorialActive={false}
        isOpen={isSidebarOpen}
        onClose={() => setIsSidebarOpen(false)}
      />
      <main className="flex-1 flex flex-col overflow-hidden min-w-0">
        {/* Topbar mobile */}
        <div className="md:hidden flex items-center gap-3 px-4 py-3 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shrink-0">
          <button
            onClick={() => setIsSidebarOpen(true)}
            className="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            aria-label="Ouvrir le menu"
          >
            <svg className="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <span className="font-semibold text-slate-800 dark:text-slate-100 text-sm truncate">
            {NAV_LABELS[page]}
          </span>
        </div>

        {page !== 'disciplines' && (
          <SearchBar
            query={filters.query}
            onQueryChange={q => setFilters(f => ({ ...f, query: q }))}
            data={data}
          />
        )}
        <div className="flex-1 overflow-y-auto">
          {page === 'overview'      && <Overview data={data} />}
          {page === 'temporel'      && <Temporel data={data} filters={filters} />}
          {page === 'concentration' && <Concentration data={data} />}
          {page === 'reseau'        && <Reseau data={data} filters={filters} isDarkMode={isDarkMode} />}
          {page === 'disciplines'   && <Disciplines data={data} filters={filters} isDarkMode={isDarkMode} />}
        </div>
      </main>
    </div>
  )
}
