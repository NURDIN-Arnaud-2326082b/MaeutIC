import React, { useMemo, useState, useRef, useEffect } from 'react'

// Utilitaire pour des couleurs consistantes
const CNU_PALETTE = [
  '#6366f1', '#ec4899', '#f59e0b', '#10b981', '#06b6d4',
  '#8b5cf6', '#f43f5e', '#14b8a6', '#f97316', '#84cc16',
  '#3b82f6', '#0ea5e9', '#d946ef', '#ef4444', '#eab308',
  '#22c55e', '#a855f7', '#3f6212', '#9f1239', '#1e3a8a'
]
const getColorForCnu = (cnuStr) => {
  let hash = 0;
  for (let i = 0; i < cnuStr.length; i++) {
    hash = cnuStr.charCodeAt(i) + ((hash << 5) - hash);
  }
  return CNU_PALETTE[Math.abs(hash) % CNU_PALETTE.length];
}

export default function ArcDiagram({ data, filters, isDarkMode }) {
  const containerRef = useRef()
  const [dims, setDims] = useState({ w: 800, h: 800 })
  const [hoveredNode, setHoveredNode] = useState(null)

  useEffect(() => {
    if (!containerRef.current) return
    const obs = new ResizeObserver(([entry]) => {
      const size = Math.min(entry.contentRect.width, 900)
      setDims({
        w: entry.contentRect.width,
        h: size
      })
    })
    obs.observe(containerRef.current)
    return () => obs.disconnect()
  }, [])

  const graph = useMemo(() => {
    if (!data || !data.length) return null

    let baseData = data
    if (filters?.annee) baseData = baseData.filter(d => d.annee === filters.annee)
    if (filters?.etablissement) baseData = baseData.filter(d => d.etablissement_norm === filters.etablissement)
    if (filters?.cnu) baseData = baseData.filter(d => d.cnu_norm === filters.cnu)

    // 1. Assigner chaque directeur à son CNU principal et compter ses thèses
    const dirCNUs = {}
    baseData.forEach(d => {
      if (!d.directeurs || !d.cnu_norm) return
      const cnu = d.cnu_norm
      d.directeurs.forEach(dirRaw => {
        const dir = dirRaw.trim()
        if (!dir) return
        if (!dirCNUs[dir]) dirCNUs[dir] = { total: 0, cnus: {} }
        dirCNUs[dir].cnus[cnu] = (dirCNUs[dir].cnus[cnu] || 0) + 1
        dirCNUs[dir].total += 1
      })
    })

    const dirHome = {}
    Object.entries(dirCNUs).forEach(([dir, info]) => {
      let maxCnu = null
      let maxVal = -1
      Object.entries(info.cnus).forEach(([cnu, count]) => {
        if (count > maxVal) { maxVal = count; maxCnu = cnu }
      })
      dirHome[dir] = maxCnu
    })

    // 2. Extraire les paires (co-directions) inter-directeurs
    const linkMap = {}
    baseData.forEach(d => {
      if (!d.directeurs || d.directeurs.length < 2) return
      const dirs = d.directeurs.map(x => x.trim()).filter(Boolean)
      for(let i=0; i<dirs.length; i++) {
        for(let j=i+1; j<dirs.length; j++) {
          if (dirs[i] === dirs[j]) continue
          const pair = [dirs[i], dirs[j]].sort()
          const key = pair.join('|||')
          if (!linkMap[key]) linkMap[key] = { source: pair[0], target: pair[1], weight: 0 }
          linkMap[key].weight += 1
        }
      }
    })

    // 3. Préparer les Nœuds : Sélectionner les 100 directeurs les plus "centraux"
    // On prend les 400 directeurs avec le plus de liens au total
    const allLinks = Object.values(linkMap)
    const dirTotalLinks = {}
    allLinks.forEach(l => {
      dirTotalLinks[l.source] = (dirTotalLinks[l.source] || 0) + l.weight
      dirTotalLinks[l.target] = (dirTotalLinks[l.target] || 0) + l.weight
    })
    
    // On identifie les 100 directeurs les plus prolifiques en terme de liens
    const candidateDirs = Object.keys(dirTotalLinks)
      .sort((a,b) => dirTotalLinks[b] - dirTotalLinks[a])
      .slice(0, 400)
    
    const candidateSet = new Set(candidateDirs)
    
    // On calcule la densité de liens interne à ce groupe de 100
    const internalDirWeight = {}
    allLinks.forEach(l => {
      if (candidateSet.has(l.source) && candidateSet.has(l.target)) {
        internalDirWeight[l.source] = (internalDirWeight[l.source] || 0) + l.weight
        internalDirWeight[l.target] = (internalDirWeight[l.target] || 0) + l.weight
      }
    })
    
    // On garde enfin les 100 qui ont le plus de liens ENTRE EUX
    const topDirs = Object.keys(internalDirWeight)
      .sort((a,b) => internalDirWeight[b] - internalDirWeight[a])
      .slice(0, 100)
    
    // Trier par CNU pour grouper visuellement
    const sortedDirs = topDirs.sort((a,b) => (dirHome[a]||'').localeCompare(dirHome[b]||''))
    
    const activeDirSet = new Set(sortedDirs)
    const links = allLinks.filter(l => activeDirSet.has(l.source) && activeDirSet.has(l.target))
    
    const maxWeight = Math.max(1, ...links.map(l => l.weight))
    const maxNodeCount = Math.max(1, ...sortedDirs.map(d => dirCNUs[d]?.total || 1))

    return { 
      nodes: sortedDirs.map(d => ({ id: d, value: dirCNUs[d]?.total || 1, cnu: dirHome[d] })), 
      links, 
      maxWeight, 
      maxNodeCount 
    }
  }, [data, filters])

  if (!graph) return null

  const { w, h } = dims
  const centerX = w / 2
  const centerY = h / 2
  const radius = Math.min(w, h) / 2 - 140

  const nodeMap = {}
  graph.nodes.forEach((node, idx) => {
    const angle = (idx / graph.nodes.length) * 2 * Math.PI - Math.PI / 2
    node.angle = angle
    node.x = centerX + radius * Math.cos(angle)
    node.y = centerY + radius * Math.sin(angle)
    node.r = 3 + Math.sqrt(node.value / graph.maxNodeCount) * 5
    nodeMap[node.id] = node
  })

  return (
    <div ref={containerRef} className="w-full h-full relative" style={{ minHeight: '600px' }}>
      <svg width={w} height={h} className="block bg-slate-50 dark:bg-slate-950 rounded-xl overflow-hidden transition-colors border border-slate-200 dark:border-slate-800">
        
        {/* Cercles de guidage subtils */}
        <circle cx={centerX} cy={centerY} r={radius} fill="none" stroke="currentColor" strokeWidth={0.5} className="text-slate-300 dark:text-slate-800" strokeDasharray="4 4" />
        <circle cx={centerX} cy={centerY} r={radius * 0.5} fill="none" stroke="currentColor" strokeWidth={0.2} className="text-slate-200 dark:text-slate-800" />

        {/* --- DESSIN DES LIENS (BUNDLING) --- */}
        <g id="links">
          {graph.links.map((link, i) => {
            const src = nodeMap[link.source]
            const tgt = nodeMap[link.target]
            if (!src || !tgt) return null

            // Point de controle vers le centre pour l'effet de bundling
            // On utilise un point situé entre le milieu du segment et le centre du cercle
            const midX = (src.x + tgt.x) / 2
            const midY = (src.y + tgt.y) / 2
            
            // On tire le point de contrôle vers le centre (0.6 donne un bundling élégant)
            const bundlingStrength = 0.65
            const cpX = midX * (1 - bundlingStrength) + centerX * bundlingStrength
            const cpY = midY * (1 - bundlingStrength) + centerY * bundlingStrength

            const pathData = `M ${src.x} ${src.y} Q ${cpX} ${cpY} ${tgt.x} ${tgt.y}`

            const isHovered = hoveredNode === src.id || hoveredNode === tgt.id
            // Visibilité accrue : base opacity à 0.4 si pas de survol
            const opacity = hoveredNode ? (isHovered ? 0.9 : 0.05) : 0.45 + (link.weight / graph.maxWeight) * 0.4
            
            // Couleur plus contrastée
            const defaultLinkColor = isDarkMode ? '#475569' : '#94a3b8'
            const color = isHovered ? getColorForCnu(src.cnu || '') : defaultLinkColor
            const strokeWidth = 1.8 + (link.weight / graph.maxWeight) * 6

            return (
              <path
                key={i}
                d={pathData}
                fill="none"
                stroke={color}
                strokeWidth={strokeWidth}
                strokeOpacity={opacity}
                style={{ transition: 'stroke-opacity 0.3s, stroke 0.3s' }}
              />
            )
          })}
        </g>

        {/* --- DESSIN DES NŒUDS --- */}
        <g id="nodes">
          {graph.nodes.map(node => {
            const isRelated = hoveredNode && (
              node.id === hoveredNode || 
              graph.links.some(l => (l.source === hoveredNode && l.target === node.id) || (l.target === hoveredNode && l.source === node.id))
            )
            const opacity = !hoveredNode || isRelated ? 1 : 0.2
            const color = getColorForCnu(node.cnu || '')
            
            // Rotation du texte pour qu'il soit radial
            const deg = (node.angle * 180) / Math.PI
            const isRightSide = node.angle > -Math.PI/2 && node.angle < Math.PI/2
            const textRotation = isRightSide ? deg : deg + 180
            const textPadding = node.r + 10

            return (
              <g 
                key={node.id} 
                onMouseEnter={() => setHoveredNode(node.id)}
                onMouseLeave={() => setHoveredNode(null)}
                style={{ cursor: 'pointer', transition: 'opacity 0.3s' }}
                opacity={opacity}
              >
                {/* Point */}
                <circle
                  cx={node.x}
                  cy={node.y}
                  r={node.r}
                  fill={isRelated ? color : (isDarkMode ? '#1e293b' : '#334155')}
                  stroke={color}
                  strokeWidth={2}
                />
                
                {/* Label radial */}
                <g transform={`translate(${node.x}, ${node.y}) rotate(${textRotation})`}>
                  <text
                    x={isRightSide ? textPadding : -textPadding}
                    y={4}
                    textAnchor={isRightSide ? "start" : "end"}
                    fill="currentColor"
                    className="text-slate-600 dark:text-slate-300 font-medium pointer-events-none"
                    style={{ fontSize: hoveredNode === node.id ? '12px' : '9px', transition: 'font-size 0.2s' }}
                  >
                    {node.id}
                  </text>
                </g>
              </g>
            )
          })}
        </g>
      </svg>
    </div>
  )
}

