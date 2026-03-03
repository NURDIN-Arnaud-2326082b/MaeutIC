import { useState, useEffect, useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
import { searchTags } from '../services/mapsApi';

const TagSearch = ({ selectedTags, onTagsChange }) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [showSuggestions, setShowSuggestions] = useState(false);
  const inputRef = useRef(null);
  const suggestionsRef = useRef(null);

  const { data: suggestions = [], isLoading } = useQuery({
    queryKey: ['tagSuggestions', searchQuery],
    queryFn: () => searchTags(searchQuery),
    enabled: searchQuery.length >= 2,
    staleTime: 30000, // 30 seconds
  });

  // Close suggestions when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (
        inputRef.current &&
        !inputRef.current.contains(event.target) &&
        suggestionsRef.current &&
        !suggestionsRef.current.contains(event.target)
      ) {
        setShowSuggestions(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleAddTag = (tag) => {
    if (!selectedTags.find(t => t.id === tag.id)) {
      onTagsChange([...selectedTags, tag]);
    }
    setSearchQuery('');
    setShowSuggestions(false);
    inputRef.current?.focus();
  };

  const handleRemoveTag = (tagId) => {
    onTagsChange(selectedTags.filter(t => t.id !== tagId));
  };

  const handleInputChange = (e) => {
    const value = e.target.value;
    setSearchQuery(value);
    setShowSuggestions(value.length >= 2);
  };

  const handleInputFocus = () => {
    if (searchQuery.length >= 2) {
      setShowSuggestions(true);
    }
  };

  return (
    <div className="flex-1 relative">
      <div className="relative">
        <input
          ref={inputRef}
          type="text"
          value={searchQuery}
          onChange={handleInputChange}
          onFocus={handleInputFocus}
          placeholder="Rechercher un mot-clé..."
          className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50/50 transition-all duration-200"
          autoComplete="off"
        />

        {/* Suggestions dropdown */}
        {showSuggestions && (
          <div
            ref={suggestionsRef}
            className="absolute left-0 right-0 top-full mt-2 bg-white border border-gray-200 rounded-xl shadow-2xl z-[9999] max-h-60 overflow-y-auto"
          >
            {isLoading ? (
              <div className="p-4 text-center text-gray-500">
                <div className="animate-spin h-5 w-5 border-2 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
              </div>
            ) : suggestions.length > 0 ? (
              <ul className="py-2">
                {suggestions
                  .filter(tag => !selectedTags.find(t => t.id === tag.id))
                  .map((tag) => (
                    <li key={tag.id}>
                      <button
                        onClick={() => handleAddTag(tag)}
                        className="w-full px-4 py-2 text-left hover:bg-blue-50 transition-colors duration-150 flex items-center gap-2"
                      >
                        <span className="text-blue-600">#</span>
                        <span className="text-gray-700">{tag.name}</span>
                      </button>
                    </li>
                  ))}
              </ul>
            ) : (
              <div className="p-4 text-center text-gray-500">
                Aucun tag trouvé
              </div>
            )}
          </div>
        )}
      </div>

      {/* Active tags */}
      {selectedTags.length > 0 && (
        <div className="flex flex-wrap gap-2 mt-2">
          {selectedTags.map((tag) => (
            <div
              key={tag.id}
              className="inline-flex items-center gap-2 bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium"
            >
              <span>#{tag.name}</span>
              <button
                onClick={() => handleRemoveTag(tag.id)}
                className="hover:bg-blue-200 rounded-full w-5 h-5 flex items-center justify-center transition-colors"
                aria-label={`Retirer ${tag.name}`}
              >
                ×
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default TagSearch;
