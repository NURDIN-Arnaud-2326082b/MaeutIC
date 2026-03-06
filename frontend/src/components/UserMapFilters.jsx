import { useState, useEffect, useMemo, useRef } from 'react';
import TagSearch from './TagSearch';

const UserMapFilters = ({
  userSearch,
  onUserSearchChange,
  selectedTags,
  onTagsChange,
  showFriends,
  onShowFriendsChange,
  showRecommendations,
  onShowRecommendationsChange,
  searchResults,
  onSearch,
  onClearSearch,
  isLoading
}) => {
  const [debouncedUserSearch, setDebouncedUserSearch] = useState(userSearch);
  const isFirstRender = useRef(true);

  // Debounce user search
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedUserSearch(userSearch);
    }, 300);

    return () => clearTimeout(timer);
  }, [userSearch]);

  // Create stable tag key for dependency
  const tagKey = useMemo(() => selectedTags.map(t => t.id).sort().join(','), [selectedTags]);

  // Trigger search when debounced value changes
  useEffect(() => {
    if (debouncedUserSearch) {
      onSearch();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debouncedUserSearch]);

  // Trigger search when tags change (including when cleared)
  useEffect(() => {
    // Skip first render
    if (isFirstRender.current) {
      isFirstRender.current = false;
      return;
    }
    
    // Trigger on any tag change
    onSearch();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tagKey]);

  const handleClearUserSearch = () => {
    onUserSearchChange('');
    onClearSearch();
  };

  const handleClearTagSearch = () => {
    onTagsChange([]);
    onClearSearch();
  };

  const hasUserSearchResults = searchResults?.type === 'user' && searchResults?.totalUsers > 0;
  const hasTagSearchResults = searchResults?.type === 'tag' && searchResults?.totalUsers > 0;

  return (
    <div className="bg-white/90 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-gray-100 mb-6" style={{ position: 'relative', zIndex: 40, flexShrink: 0 }}>
      <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        {/* Search Bars */}
        <div className="flex-1 w-full lg:w-auto">
          <div className="flex flex-row gap-6">
            {/* User Search */}
            <div className="flex-1">
              <input
                type="text"
                value={userSearch}
                onChange={(e) => onUserSearchChange(e.target.value)}
                placeholder="Rechercher par pseudo, nom, prénom, location, spécialisation, sujet de recherche..."
                className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50/50 transition-all duration-200"
              />
            </div>

            {/* Tag Search */}
            <TagSearch
              selectedTags={selectedTags}
              onTagsChange={onTagsChange}
            />
          </div>
        </div>

        {/* Filter Checkboxes */}
        <div className="flex items-center gap-4 bg-gray-50/50 rounded-xl p-4 border border-gray-200">
          <label className="flex items-center gap-3 cursor-pointer px-4">
            <input
              type="checkbox"
              checked={showFriends}
              onChange={(e) => onShowFriendsChange(e.target.checked)}
              className="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200"
            />
            <span className="text-sm font-medium text-gray-700">Réseau</span>
          </label>

          <div className="h-8 w-px bg-gray-300/50"></div>

          <label className="flex items-center gap-3 cursor-pointer px-4">
            <input
              type="checkbox"
              checked={showRecommendations}
              onChange={(e) => onShowRecommendationsChange(e.target.checked)}
              className="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200"
            />
            <span className="text-sm font-medium text-gray-700">Recommandations</span>
          </label>
        </div>
      </div>

      {/* User Search Results Pagination */}
      {hasUserSearchResults && (
        <div className="mt-4">
          <div className="flex items-center justify-between bg-green-50/50 rounded-xl p-3 border border-green-200">
            <span className="text-sm text-green-700 font-medium">
              {searchResults.totalUsers} utilisateur(s) trouvé(s)
            </span>
            <div className="flex items-center gap-2">
              <button
                onClick={() => onSearch('prev')}
                disabled={searchResults.currentPage === 1}
                className="px-3 py-1 text-sm bg-white border border-green-300 rounded-lg text-green-700 hover:bg-green-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Précédent
              </button>
              <span className="text-sm text-green-700 mx-2">
                Page {searchResults.currentPage} / {searchResults.totalPages}
              </span>
              <button
                onClick={() => onSearch('next')}
                disabled={searchResults.currentPage === searchResults.totalPages}
                className="px-3 py-1 text-sm bg-white border border-green-300 rounded-lg text-green-700 hover:bg-green-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Suivant
              </button>
              <button
                onClick={handleClearUserSearch}
                className="px-3 py-1 text-sm bg-red-100 border border-red-300 rounded-lg text-red-700 hover:bg-red-200 ml-2"
              >
                Effacer
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Tag Search Results Pagination */}
      {hasTagSearchResults && (
        <div className="mt-4">
          <div className="flex items-center justify-between bg-blue-50/50 rounded-xl p-3 border border-blue-200">
            <span className="text-sm text-blue-700 font-medium">
              {searchResults.totalUsers} utilisateur(s) avec ces tags
            </span>
            <div className="flex items-center gap-2">
              <button
                onClick={() => onSearch('prev')}
                disabled={searchResults.currentPage === 1}
                className="px-3 py-1 text-sm bg-white border border-blue-300 rounded-lg text-blue-700 hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Précédent
              </button>
              <span className="text-sm text-blue-700 mx-2">
                Page {searchResults.currentPage} / {searchResults.totalPages}
              </span>
              <button
                onClick={() => onSearch('next')}
                disabled={searchResults.currentPage === searchResults.totalPages}
                className="px-3 py-1 text-sm bg-white border border-blue-300 rounded-lg text-blue-700 hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Suivant
              </button>
              <button
                onClick={handleClearTagSearch}
                className="px-3 py-1 text-sm bg-red-100 border border-red-300 rounded-lg text-red-700 hover:bg-red-200 ml-2"
              >
                Effacer
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Loading Indicator */}
      {isLoading && (
        <div className="mt-4">
          <div className="flex items-center justify-center gap-2 text-blue-600">
            <div className="animate-spin h-5 w-5 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            <span className="text-sm font-medium">Mise à jour de la carte...</span>
          </div>
        </div>
      )}
    </div>
  );
};

export default UserMapFilters;
