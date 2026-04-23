import { useEffect, useRef, useState, useMemo, useCallback, lazy, Suspense } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import * as d3 from 'd3';
import { getMapUsers, searchUsers, filterByTags } from '../services/mapsApi';
import UserMapFilters from '../components/UserMapFilters';
const ThesisDashboard = lazy(() => import('../features/thesisDashboard/App'));

const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000';

const Maps = () => {
  const navigate = useNavigate();
  const containerRef = useRef(null);
  const svgRef = useRef(null);
  const simulationRef = useRef(null);
  const [hoveredUser, setHoveredUser] = useState(null);
  const [activeTab, setActiveTab] = useState('users');
  
  // Filter states
  const [userSearch, setUserSearch] = useState('');
  const [selectedTags, setSelectedTags] = useState([]);
  const [showFriends, setShowFriends] = useState(true);
  const [showRecommendations, setShowRecommendations] = useState(true);
  
  // Search pagination
  const [searchResults, setSearchResults] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [isSearching, setIsSearching] = useState(false);

  // Determine which data to fetch
  const shouldSearch = userSearch.trim().length > 0 || selectedTags.length > 0;

  // Fetch initial map users
  const { data: initialData, isLoading: isLoadingInitial } = useQuery({
    queryKey: ['mapUsers'],
    queryFn: getMapUsers,
    enabled: activeTab === 'users' && !shouldSearch,
  });

  // State for search/filter results
  const [mapData, setMapData] = useState(null);

  // Set initial data
  useEffect(() => {
    if (initialData && !shouldSearch) {
      setMapData(initialData);
      setSearchResults(null);
    }
  }, [initialData, shouldSearch]);

  // Handle search function
  const handleSearch = useCallback(async (navigation = null) => {
    if (!userSearch.trim() && selectedTags.length === 0) {
      setSearchResults(null);
      setMapData(initialData);
      return;
    }

    setIsSearching(true);

    let newPage = currentPage;
    if (navigation === 'prev' && currentPage > 1) {
      newPage = currentPage - 1;
    } else if (navigation === 'next' && searchResults && currentPage < searchResults.totalPages) {
      newPage = currentPage + 1;
    } else if (navigation !== 'prev' && navigation !== 'next') {
      newPage = 1; // Reset to page 1 for new searches
    }

    try {
      let result;
      const options = {
        friends: showFriends,
        recommendations: showRecommendations,
        page: newPage,
        limit: 20
      };

      if (selectedTags.length > 0) {
        const tagIds = selectedTags.map(t => t.id);
        result = await filterByTags(tagIds, options);
        setSearchResults({
          type: 'tag',
          ...result
        });
      } else if (userSearch.trim()) {
        result = await searchUsers(userSearch.trim(), options);
        setSearchResults({
          type: 'user',
          ...result
        });
      }

      setMapData(result);
      setCurrentPage(newPage);
    } catch (error) {
      console.error('Search error:', error);
    } finally {
      setIsSearching(false);
    }
  }, [userSearch, selectedTags, currentPage, searchResults, showFriends, showRecommendations, initialData]);

  const handleClearSearch = () => {
    setUserSearch('');
    setSelectedTags([]);
    setSearchResults(null);
    setCurrentPage(1);
    setMapData(initialData);
  };

  // Apply checkbox filters to current data
  const filteredUsers = useMemo(() => {
    if (!mapData?.users) return [];
    
    return mapData.users.filter(user => {
      // Always show current user
      if (user.isCurrentUser) return true;

      // Filter by checkboxes
      if (!showFriends && user.isFriend) return false;
      if (!showRecommendations && !user.isFriend && !user.isCurrentUser) return false;

      return true;
    });
  }, [mapData, showFriends, showRecommendations]);

  useEffect(() => {
    if (!mapData || !containerRef.current || !svgRef.current) return;
    if (filteredUsers.length === 0) return;

    const container = containerRef.current;
    const svg = d3.select(svgRef.current);
    const width = container.offsetWidth;
    const height = container.offsetHeight;

    const { friendIds, currentUserId, userScores } = mapData;

    // Clear previous elements
    svg.selectAll('*').remove();
    d3.select(container).selectAll('.bubble').remove();

    // Calculate initial positions
    const baseRadius = Math.min(width, height) * 0.3;
    const spacing = (2 * Math.PI) / filteredUsers.length;

    const nodes = filteredUsers.map((user, i) => {
      const angle = i * spacing;
      const radius = baseRadius + (Math.random() * baseRadius * 0.3);

      return {
        ...user,
        x: width / 2 + radius * Math.cos(angle),
        y: height / 2 + radius * Math.sin(angle),
        isFriend: friendIds.includes(user.id),
        isCurrentUser: user.id === currentUserId,
      };
    });

    // Create links for friends
    const links = [];
    if (currentUserId) {
      const currentUserNode = nodes.find(n => n.isCurrentUser);
      if (currentUserNode) {
        nodes.forEach(node => {
          if (node.isFriend && !node.isCurrentUser) {
            links.push({
              source: currentUserNode,
              target: node,
            });
          }
        });
      }
    }

    // Create simulation
    const simulation = d3.forceSimulation(nodes)
      .alpha(1)
      .alphaDecay(0.02)
      .velocityDecay(0.25)
      .force("x", d3.forceX(width / 2).strength(0.05))
      .force("y", d3.forceY(height / 2).strength(0.05))
      .force("charge", d3.forceManyBody().strength(-80))
      .force("collision", d3.forceCollide().radius(70).strength(0.8))
      .force("center", d3.forceCenter(width / 2, height / 2).strength(0.1));

    simulationRef.current = simulation;

    // Create connection lines
    const lineGroup = svg.append('g').attr('class', 'connection-lines');
    const linkElements = lineGroup.selectAll('line')
      .data(links)
      .enter()
      .append('line')
      .attr('stroke', '#3B82F6')
      .attr('stroke-width', 2)
      .attr('stroke-dasharray', '5,5')
      .attr('opacity', 0.6);

    // Create bubble elements
    const bubbles = d3.select(container)
      .selectAll('.bubble')
      .data(nodes)
      .enter()
      .append('div')
      .attr('class', (d) => {
        let classes = 'bubble absolute shadow-lg bg-white rounded-full flex items-center justify-center cursor-pointer transition duration-300 opacity-100 scale-100 group';
        if (d.isCurrentUser) classes += ' ring-4 ring-purple-500';
        else if (d.isFriend) classes += ' ring-2 ring-green-400';
        return classes;
      })
      .style('width', '80px')
      .style('height', '80px')
      .style('position', 'absolute')
      .html((d) => {
        const score = userScores[d.id];
        let borderColor = '#cccccc';
        let boxShadow = 'none';
        
        if (score !== null && score !== undefined && !d.isFriend && !d.isCurrentUser) {
          if (score >= 0.7) {
            borderColor = '#ff00ff'; // magenta
          } else if (score >= 0.4) {
            borderColor = '#ffcc00'; // yellow
          } else {
            borderColor = '#ff4500'; // orange-red
          }
          boxShadow = `0 0 15px ${borderColor}66`;
        }

        return `
          <img 
            src="${BACKEND_URL}${d.profileImage}" 
            alt="profile" 
            class="w-full h-full object-cover rounded-full pointer-events-none"
            style="border: 3px dashed ${borderColor}; box-shadow: ${boxShadow};"
          />
        `;
      })
      .on('click', (event, d) => {
        if (!d.isCurrentUser) {
          navigate(`/profile/${d.username}`);
        }
      })
      .on('mouseenter', (event, d) => {
        setHoveredUser(d);
        d3.select(event.currentTarget).style('z-index', 9999);
      })
      .on('mouseleave', (event) => {
        setHoveredUser(null);
        d3.select(event.currentTarget).style('z-index', '');
      });

    // Drag behavior
    const drag = d3.drag()
      .on('start', (event, d) => {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
        d3.select(event.sourceEvent.target.parentElement).style('z-index', 1000);
      })
      .on('drag', (event, d) => {
        d.fx = event.x;
        d.fy = event.y;
      })
      .on('end', (event, d) => {
        if (!event.active) simulation.alphaTarget(0);
        d.fx = null;
        d.fy = null;
        d3.select(event.sourceEvent.target.parentElement).style('z-index', '');
      });

    bubbles.call(drag);

    // Update positions on tick
    simulation.on('tick', () => {
      bubbles
        .style('left', (d) => {
          d.x = Math.max(50, Math.min(width - 50, d.x));
          return (d.x - 40) + 'px';
        })
        .style('top', (d) => {
          d.y = Math.max(50, Math.min(height - 50, d.y));
          return (d.y - 40) + 'px';
        });

      linkElements
        .attr('x1', d => d.source.x)
        .attr('y1', d => d.source.y)
        .attr('x2', d => d.target.x)
        .attr('y2', d => d.target.y);
    });

    // Cleanup
    return () => {
      if (simulationRef.current) {
        simulationRef.current.stop();
      }
    };
  }, [filteredUsers, mapData, navigate]);

  if (activeTab === 'users' && isLoadingInitial && !shouldSearch) {
    return (
      <div className="flex-1 flex items-center justify-center bg-gray-50">
        <div className="text-xl text-gray-600">Chargement de la carte...</div>
      </div>
    );
  }

  if (activeTab === 'users' && !mapData && !isLoadingInitial) {
    return (
      <div className="flex-1 flex items-center justify-center bg-gray-50">
        <div className="text-xl text-red-600">Erreur de chargement des données</div>
      </div>
    );
  }

  const getScoreColor = (score) => {
    if (!score) return '';
    if (score >= 0.7) return 'text-green-400';
    if (score >= 0.4) return 'text-yellow-400';
    return 'text-orange-400';
  };

  const getScorePercentage = (score) => {
    if (!score) return 0;
    return Math.round(score * 100);
  };

  return (
    <div className="flex-1 flex flex-col bg-gray-50" style={{ height: 'calc(100vh - 80px)' }}>
      <div className="mx-6 mt-6 mb-4">
        <div className="inline-flex bg-white rounded-xl p-1 shadow-sm border border-gray-200">
          <button
            type="button"
            onClick={() => setActiveTab('users')}
            className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
              activeTab === 'users'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            Map des utilisateurs
          </button>
          <button
            type="button"
            onClick={() => setActiveTab('project')}
            className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
              activeTab === 'project'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            Dashboard thèses
          </button>
        </div>
      </div>

      {activeTab === 'users' ? (
        <>
      {/* Filters Section */}
      <div className="mx-6">
        <UserMapFilters
          userSearch={userSearch}
          onUserSearchChange={setUserSearch}
          selectedTags={selectedTags}
          onTagsChange={setSelectedTags}
          showFriends={showFriends}
          onShowFriendsChange={setShowFriends}
          showRecommendations={showRecommendations}
          onShowRecommendationsChange={setShowRecommendations}
          searchResults={searchResults}
          onSearch={handleSearch}
          onClearSearch={handleClearSearch}
          isLoading={isSearching}
        />
      </div>

      {/* Map Container */}
      <div
        ref={containerRef}
        className="flex-1 relative mx-6 mb-6 overflow-hidden bg-white rounded-2xl shadow-lg flex items-center justify-center"
      >
        <svg
          ref={svgRef}
          className="absolute top-0 left-0 w-full h-full pointer-events-none z-0"
        />

          {/* Tooltip */}
          {hoveredUser && (
            <div
              className="absolute bg-black/90 text-white px-3 py-1 rounded-xl text-sm whitespace-nowrap shadow-lg border border-white z-[10000] pointer-events-none"
              style={{
                left: '50%',
                top: '20px',
                transform: 'translateX(-50%)',
              }}
            >
              {hoveredUser.firstName} {hoveredUser.lastName}
              {hoveredUser.score && !hoveredUser.isFriend && !hoveredUser.isCurrentUser && (
                <span className={`ml-1 font-semibold ${getScoreColor(hoveredUser.score)}`}>
                  {getScorePercentage(hoveredUser.score)}%
                </span>
              )}
              {hoveredUser.isFriend && !hoveredUser.isCurrentUser && (
                <span className="ml-1 text-green-400">✓</span>
              )}
            </div>
          )}
      </div>
        </>
      ) : (
        <div className="flex-1 min-h-0 mx-6 mb-6 overflow-hidden bg-white rounded-2xl shadow-lg">
          <Suspense fallback={<div className="h-full flex items-center justify-center text-gray-600">Chargement du dashboard...</div>}>
            <ThesisDashboard />
          </Suspense>
        </div>
      )}
    </div>
  );
};

export default Maps;
