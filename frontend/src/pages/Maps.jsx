import { useEffect, useRef, useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import * as d3 from 'd3';
import { getMapUsers } from '../services/mapsApi';

const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000';

const Maps = () => {
  const navigate = useNavigate();
  const containerRef = useRef(null);
  const svgRef = useRef(null);
  const simulationRef = useRef(null);
  const [hoveredUser, setHoveredUser] = useState(null);
  const [userSearch, setUserSearch] = useState('');
  const [tagSearch, setTagSearch] = useState('');
  const [showFriends, setShowFriends] = useState(true);
  const [showRecommendations, setShowRecommendations] = useState(true);

  const { data, isLoading } = useQuery({
    queryKey: ['mapUsers'],
    queryFn: getMapUsers,
  });

  // Filter users based on search and checkboxes
  const filteredUsers = useMemo(() => {
    if (!data?.users) return [];
    
    return data.users.filter(user => {
      // Always show current user
      if (user.isCurrentUser) return true;

      // Filter by checkboxes
      if (!showFriends && user.isFriend) return false;
      if (!showRecommendations && !user.isFriend && !user.isCurrentUser) return false;

      // Filter by user search
      if (userSearch) {
        const search = userSearch.toLowerCase();
        const fullName = `${user.firstName} ${user.lastName}`.toLowerCase();
        const username = user.username.toLowerCase();
        if (!fullName.includes(search) && !username.includes(search)) {
          return false;
        }
      }

      return true;
    });
  }, [data, showFriends, showRecommendations, userSearch]);

  useEffect(() => {
    if (!data || !containerRef.current || !svgRef.current) return;
    if (filteredUsers.length === 0) return;

    const container = containerRef.current;
    const svg = d3.select(svgRef.current);
    const width = container.offsetWidth;
    const height = container.offsetHeight;

    const { friendIds, currentUserId, userScores } = data;

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
        navigate(`/profile/${d.username}`);
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
  }, [filteredUsers, data, navigate]);

  if (isLoading) {
    return (
      <div className="flex-1 flex items-center justify-center bg-gray-50">
        <div className="text-xl text-gray-600">Chargement de la carte...</div>
      </div>
    );
  }

  if (!data) {
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
      {/* Filters Section */}
      <div className="bg-white/90 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-gray-100 mb-6 mx-6 mt-6" style={{ position: 'relative', zIndex: 40, flexShrink: 0 }}>
        <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          {/* Search Bars */}
          <div className="flex-1 w-full lg:w-auto">
            <div className="flex flex-row gap-6">
              {/* User Search */}
              <div className="flex-1">
                <input
                  type="text"
                  value={userSearch}
                  onChange={(e) => setUserSearch(e.target.value)}
                  placeholder="Rechercher par pseudo, nom, prénom, location, spécialisation, sujet de recherche..."
                  className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50/50 transition-all duration-200"
                />
              </div>

              {/* Tag Search */}
              <div className="flex-1">
                <input
                  type="text"
                  value={tagSearch}
                  onChange={(e) => setTagSearch(e.target.value)}
                  placeholder="Rechercher un mot-clé..."
                  className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50/50 transition-all duration-200"
                />
              </div>
            </div>
          </div>

          {/* Filter Checkboxes */}
          <div className="flex items-center gap-4 bg-gray-50/50 rounded-xl p-4 border border-gray-200">
            <label className="flex items-center gap-3 cursor-pointer px-4">
              <input
                type="checkbox"
                checked={showFriends}
                onChange={(e) => setShowFriends(e.target.checked)}
                className="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200"
              />
              <span className="text-sm font-medium text-gray-700">Réseau</span>
            </label>

            <div className="h-8 w-px bg-gray-300/50"></div>

            <label className="flex items-center gap-3 cursor-pointer px-4">
              <input
                type="checkbox"
                checked={showRecommendations}
                onChange={(e) => setShowRecommendations(e.target.checked)}
                className="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200"
              />
              <span className="text-sm font-medium text-gray-700">Recommandations</span>
            </label>
          </div>
        </div>
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
    </div>
  );
};

export default Maps;
