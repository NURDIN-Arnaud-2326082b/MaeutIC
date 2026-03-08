import { useState } from 'react'
import { useForumStore } from '../store'

export default function SearchBar() {
  const { searchQuery, searchFilters, setSearchQuery, setSearchFilters, resetFilters } = useForumStore()
  const [localQuery, setLocalQuery] = useState(searchQuery)

  const handleSearch = (e) => {
    e.preventDefault()
    setSearchQuery(localQuery)
  }

  return (
    <div className="bg-white p-4 rounded-lg shadow-sm mb-6">
      <form onSubmit={handleSearch} className="space-y-4">
        <div className="flex gap-2">
          <input
            type="text"
            value={localQuery}
            onChange={(e) => setLocalQuery(e.target.value)}
            placeholder="Rechercher dans les forums..."
            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <button
            type="submit"
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Rechercher
          </button>
        </div>

        <div className="flex flex-wrap gap-4">
          <select
            value={searchFilters.type}
            onChange={(e) => setSearchFilters({ type: e.target.value })}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">Tous les types</option>
            <option value="title">Titre uniquement</option>
            <option value="content">Contenu uniquement</option>
          </select>

          <select
            value={searchFilters.date}
            onChange={(e) => setSearchFilters({ date: e.target.value })}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">Toutes les dates</option>
            <option value="today">Aujourd'hui</option>
            <option value="week">Cette semaine</option>
            <option value="month">Ce mois</option>
          </select>

          <select
            value={searchFilters.sort}
            onChange={(e) => setSearchFilters({ sort: e.target.value })}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="recent">Plus récent</option>
            <option value="oldest">Plus ancien</option>
            <option value="popular">Plus populaire</option>
          </select>

          {(searchQuery || searchFilters.type !== 'all' || searchFilters.date !== 'all' || searchFilters.sort !== 'recent') && (
            <button
              type="button"
              onClick={resetFilters}
              className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
            >
              Réinitialiser
            </button>
          )}
        </div>
      </form>
    </div>
  )
}
