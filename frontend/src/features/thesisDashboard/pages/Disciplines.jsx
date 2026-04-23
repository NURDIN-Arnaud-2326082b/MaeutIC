import { useEffect, useRef, useState, useMemo } from 'react'
import { forceSimulation, forceCollide, forceX, forceY } from 'd3-force'
import KeywordTrends from '../components/charts/KeywordTrends'
import KeywordDrillDown from '../components/charts/KeywordDrillDown'
import { allData } from '../hooks/useFilteredData'

// Couleurs par section CNU (cohérentes avec le reste du dashboard)
const CNU_COLORS = {
  'Science politique':                              '#6366f1',
  'Sciences de gestion':                           '#f97316',
  'Philosophie':                                   '#8b5cf6',
  'Arts':                                          '#ec4899',
  'Sociologie':                                    '#14b8a6',
  'Ethnologie':                                    '#f59e0b',
  "Sciences de l'éducation":                       '#10b981',
  'Sciences de l\'information et de la communication': '#3b82f6',
  'Épistémologie, histoire des sciences et des techniques': '#64748b',
}
const DEFAULT_COLOR = '#94a3b8'

function getColor(cnuLabel) {
  if (!cnuLabel) return DEFAULT_COLOR
  const match = Object.keys(CNU_COLORS).find(k => cnuLabel.includes(k.split(' ')[0]))
  return CNU_COLORS[cnuLabel] ?? CNU_COLORS[match] ?? DEFAULT_COLOR
}

const MAX_R = 60
const MIN_R = 14

export default function Disciplines({ data, filters, isDarkMode }) {
  const [clusters, setClusters] = useState(null)
  const [error, setError] = useState(null)
  const [hovered, setHovered] = useState(null)
  const [selected, setSelected] = useState(null)
  const [showTheses, setShowTheses] = useState(false)
  const [dims, setDims] = useState({ w: 900, h: 680 })
  const [pan, setPan] = useState({ x: 0, y: 0 })
  const dragRef = useRef({ active: false, startX: 0, startY: 0, panX: 0, panY: 0, moved: false })
  const svgRef = useRef()
  const containerRef = useRef()

  // Charger clusters.json
  useEffect(() => {
    fetch('/clusters.json')
      .then(r => r.json())
      .then(data => setClusters(data))
      .catch(() => setError(true))
  }, [])

  // Observer la taille du conteneur
  useEffect(() => {
    if (!containerRef.current) return
    const obs = new ResizeObserver(([e]) => {
      setDims({ w: e.contentRect.width, h: Math.max(600, e.contentRect.width * 0.75) })
    })
    obs.observe(containerRef.current)
    return () => obs.disconnect()
  }, [])

  // Calculer les rayons
  const maxNb = useMemo(() => clusters ? Math.max(...clusters.map(c => c.nb)) : 1, [clusters])
  const scaleR = (nb) => MIN_R + (MAX_R - MIN_R) * Math.sqrt(nb / maxNb)

  // Positionner les bulles depuis les coordonnées UMAP + résolution des chevauchements
  const nodes = useMemo(() => {
    if (!clusters?.length) return []
    const MARGIN = 80
    const cxMin = Math.min(...clusters.map(c => c.cx))
    const cxMax = Math.max(...clusters.map(c => c.cx))
    const cyMin = Math.min(...clusters.map(c => c.cy))
    const cyMax = Math.max(...clusters.map(c => c.cy))
    const mapX = cx => MARGIN + (cx - cxMin) / (cxMax - cxMin) * (dims.w - MARGIN * 2)
    const mapY = cy => MARGIN + (cy - cyMin) / (cyMax - cyMin) * (dims.h - MARGIN * 2)

    const pts = clusters.map(c => ({
      ...c,
      r: scaleR(c.nb),
      tx: mapX(c.cx), // position cible (UMAP)
      ty: mapY(c.cy),
      x: mapX(c.cx),  // position initiale (sera mutée)
      y: mapY(c.cy),
    }))

    // Simulation one-shot : collision uniquement, attraction vers la position UMAP
    forceSimulation(pts)
      .force('collide', forceCollide(d => d.r + 3).strength(1))
      .force('x', forceX(d => d.tx).strength(0.15))
      .force('y', forceY(d => d.ty).strength(0.15))
      .stop()
      .tick(300)

    return pts
  }, [clusters, dims])

  // Thèses du cluster sélectionné (hook avant les early returns)
  const selectedCluster = clusters?.find(c => c.id === selected) ?? null
  const clusterTheses = useMemo(() => {
    if (!selectedCluster) return []
    return allData.filter(d => d.cluster_id === selectedCluster.id)
  }, [selectedCluster])

  // --- États de chargement / erreur / vide ---
  if (error) return (
    <div className="p-8 flex items-center justify-center h-full">
      <p className="text-slate-400 dark:text-slate-500">Impossible de charger clusters.json</p>
    </div>
  )

  if (clusters === null) return (
    <div className="p-8 flex items-center justify-center h-full">
      <p className="text-slate-400 dark:text-slate-500">Chargement…</p>
    </div>
  )

  if (clusters.length === 0) return (
    <div className="p-8 flex flex-col items-center justify-center gap-4 h-full">
      <div className="text-center max-w-md">
        <p className="text-slate-700 dark:text-slate-200 font-semibold text-lg mb-2">Aucune donnée de clustering</p>
        <p className="text-slate-400 dark:text-slate-500 text-sm">
          Lance d'abord le notebook Python <code className="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">notebooks/02_clustering_titres.ipynb</code> pour générer <code className="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">clusters.json</code>, puis place le fichier dans <code className="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">dashboard/public/</code>.
        </p>
      </div>
    </div>
  )

  return (
    <div className="p-4 md:p-8 flex flex-col gap-6">

      {/* Header */}
      <div>
        <h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Disciplines</h2>
        <p className="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Clustering sémantique des titres — {clusters.length} groupes · chaque bulle représente un cluster thématique
        </p>
      </div>

      <div className="flex flex-col lg:flex-row gap-6 items-start">

        {/* Bubble chart */}
        <div
          ref={containerRef}
          className="flex-1 relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm"
        >
          {(pan.x !== 0 || pan.y !== 0) && (
            <button
              onClick={() => setPan({ x: 0, y: 0 })}
              className="absolute top-3 right-3 z-10 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 shadow-sm"
            >
              Recentrer
            </button>
          )}
          <svg
            ref={svgRef}
            width={dims.w}
            height={dims.h}
            className="block"
            style={{ cursor: 'grab' }}
            onMouseDown={e => {
              dragRef.current = { active: true, startX: e.clientX, startY: e.clientY, panX: pan.x, panY: pan.y, moved: false }
            }}
            onMouseMove={e => {
              if (!dragRef.current.active) return
              const dx = e.clientX - dragRef.current.startX
              const dy = e.clientY - dragRef.current.startY
              if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
                dragRef.current.moved = true
                setPan({ x: dragRef.current.panX + dx, y: dragRef.current.panY + dy })
              }
            }}
            onMouseUp={() => { dragRef.current.active = false }}
            onMouseLeave={() => { dragRef.current.active = false }}
          >
            <g transform={`translate(${pan.x},${pan.y})`}>
            {/* Toutes les bulles (sans tooltip) */}
            {nodes.map(node => {
              const isHov = hovered === node.id
              const isSel = selected === node.id
              const color = getColor(node.cnu_dominant)
              const label = node.label
              const fontSize = Math.max(9, Math.min(13, node.r / 4.5))

              return (
                <g
                  key={node.id}
                  transform={`translate(${node.x},${node.y})`}
                  style={{ cursor: 'pointer' }}
                  onMouseEnter={() => setHovered(node.id)}
                  onMouseLeave={() => setHovered(null)}
                  onClick={() => { if (dragRef.current.moved) return; setSelected(prev => { const next = prev === node.id ? null : node.id; if (next !== prev) setShowTheses(false); return next }) }}
                >
                  <circle
                    r={node.r}
                    fill={color}
                    fillOpacity={isSel ? 0.9 : isHov ? 0.75 : 0.45}
                    stroke={color}
                    strokeWidth={isSel ? 2.5 : isHov ? 1.5 : 0.5}
                    style={{ transition: 'fill-opacity 0.15s, stroke-width 0.15s' }}
                  />
                  {node.r > 28 && (() => {
                    // Word-wrap : max ~(2*r * 0.85) px de large, ~charsPerLine caractères
                    const maxW = node.r * 1.7
                    const charsPerLine = Math.floor(maxW / (fontSize * 0.55))
                    const words = label.split(' ')
                    const lines = []
                    let current = ''
                    for (const w of words) {
                      const candidate = current ? `${current} ${w}` : w
                      if (candidate.length > charsPerLine && current) {
                        lines.push(current)
                        current = w
                      } else {
                        current = candidate
                      }
                    }
                    if (current) lines.push(current)
                    const lineH = fontSize * 1.25
                    const totalH = lines.length * lineH
                    return (
                      <text
                        textAnchor="middle"
                        fontSize={fontSize}
                        fontWeight={isSel ? 700 : 500}
                        fill={isDarkMode ? (isSel || isHov ? '#f1f5f9' : '#cbd5e1') : (isSel || isHov ? '#1e293b' : '#334155')}
                        style={{ pointerEvents: 'none', userSelect: 'none' }}
                      >
                        {lines.map((line, i) => (
                          <tspan key={i} x={0} dy={i === 0 ? -totalH / 2 + fontSize * 0.85 : lineH}>
                            {line}
                          </tspan>
                        ))}
                      </text>
                    )
                  })()}
                  {node.r > 36 && (
                    <text
                      y={node.r - 10}
                      textAnchor="middle"
                      fontSize={9}
                      fill={color}
                      fontWeight={600}
                      style={{ pointerEvents: 'none', userSelect: 'none' }}
                    >
                      {node.nb}
                    </text>
                  )}
                </g>
              )
            })}

            {/* Tooltip rendu en dernier = toujours au-dessus de toutes les bulles */}
            {(() => {
              const node = nodes.find(n => n.id === hovered)
              if (!node) return null
              const color = getColor(node.cnu_dominant)
              const tooltipW = 200
              const tooltipH = 16 + (node.keywords?.slice(0, 6).length ?? 0) * 14 + 18
              // Évite de sortir du SVG à droite
              const tx = node.x + node.r + 8 + tooltipW > dims.w ? node.x - node.r - 8 - tooltipW : node.x + node.r + 8
              const ty = Math.max(8, Math.min(node.y - node.r / 2, dims.h - tooltipH - 8))
              return (
                <g transform={`translate(${tx},${ty})`} style={{ pointerEvents: 'none' }}>
                  <rect
                    x={0} y={0}
                    width={tooltipW}
                    height={tooltipH}
                    rx={8}
                    fill={isDarkMode ? '#1e293b' : '#ffffff'}
                    fillOpacity={1}
                    stroke={isDarkMode ? '#334155' : '#cbd5e1'}
                    strokeWidth={1}
                    filter="drop-shadow(0 4px 12px rgba(0,0,0,0.25))"
                  />
                  <text x={10} y={14} fontSize={11} fontWeight={700} fill={isDarkMode ? '#f1f5f9' : '#1e293b'}>
                    {node.label}
                  </text>
                  {node.keywords?.slice(0, 6).map((kw, i) => (
                    <text key={kw} x={10} y={14 + (i + 1) * 14} fontSize={10} fill={isDarkMode ? '#94a3b8' : '#64748b'}>
                      • {kw}
                    </text>
                  ))}
                  <text
                    x={10}
                    y={14 + (node.keywords?.slice(0, 6).length ?? 0) * 14 + 14}
                    fontSize={10}
                    fill={color}
                    fontWeight={600}
                  >
                    {node.nb} thèses · {node.cnu_dominant ?? '—'}
                  </text>
                </g>
              )
            })()}
            </g>
          </svg>
        </div>

        {/* Panneau latéral : détail du cluster sélectionné */}
        <div className="w-72 shrink-0 flex flex-col gap-4">

          {/* Légende CNU */}
          <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">
              Sections CNU
            </p>
            <div className="flex flex-col gap-1.5">
              {Object.entries(CNU_COLORS).map(([label, color]) => (
                <div key={label} className="flex items-center gap-2">
                  <span
                    className="w-3 h-3 rounded-full shrink-0"
                    style={{ backgroundColor: color }}
                  />
                  <span className="text-xs text-slate-600 dark:text-slate-300 leading-tight">{label}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Détail cluster sélectionné */}
          {selectedCluster && (
            <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex flex-col gap-3 shadow-sm">
              <div className="flex items-start justify-between gap-2">
                <p className="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug">
                  {selectedCluster.label}
                </p>
                <button
                  onClick={() => { setSelected(null); setShowTheses(false) }}
                  className="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 text-xs shrink-0"
                >
                  ✕
                </button>
              </div>

              <div className="flex items-center gap-2">
                <span
                  className="w-2.5 h-2.5 rounded-full"
                  style={{ backgroundColor: getColor(selectedCluster.cnu_dominant) }}
                />
                <span className="text-xs text-slate-500 dark:text-slate-400">{selectedCluster.cnu_dominant ?? '—'}</span>
                <span className="ml-auto text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                  {selectedCluster.nb} thèses
                </span>
              </div>

              <div>
                <p className="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-2">
                  Mots-clés TF-IDF
                </p>
                <div className="flex flex-wrap gap-1.5">
                  {selectedCluster.keywords?.map((kw, i) => (
                    <span
                      key={kw}
                      className="text-xs px-2 py-0.5 rounded-full border dark:text-slate-200"
                      style={{
                        backgroundColor: getColor(selectedCluster.cnu_dominant) + '18',
                        borderColor: getColor(selectedCluster.cnu_dominant) + '40',
                        opacity: 1 - i * 0.06,
                      }}
                    >
                      {kw}
                    </span>
                  ))}
                </div>
              </div>

              {selectedCluster.cnu_dist && (
                <div>
                  <p className="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-2">
                    Répartition disciplines
                  </p>
                  <div className="flex flex-col gap-1">
                    {Object.entries(selectedCluster.cnu_dist)
                      .sort(([, a], [, b]) => b - a)
                      .map(([cnu, nb]) => {
                        const pct = Math.round(nb / selectedCluster.nb * 100)
                        return (
                          <div key={cnu} className="flex items-center gap-2 text-xs">
                            <span className="text-slate-500 dark:text-slate-400 truncate flex-1">{cnu}</span>
                            <div className="w-20 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                              <div
                                className="h-1.5 rounded-full"
                                style={{
                                  width: `${pct}%`,
                                  backgroundColor: getColor(cnu),
                                }}
                              />
                            </div>
                            <span className="text-slate-400 w-6 text-right">{pct}%</span>
                          </div>
                        )
                      })}
                  </div>
                </div>
              )}

              <button
                onClick={() => setShowTheses(v => !v)}
                className="w-full mt-1 text-xs font-semibold py-2 px-3 rounded-xl border transition-colors"
                style={{
                  backgroundColor: getColor(selectedCluster.cnu_dominant) + '18',
                  borderColor: getColor(selectedCluster.cnu_dominant) + '50',
                  color: getColor(selectedCluster.cnu_dominant),
                }}
              >
                {showTheses ? '▲ Masquer les thèses' : `▼ Voir les ${clusterTheses.length} thèses`}
              </button>
            </div>
          )}

          {!selectedCluster && (
            <div className="bg-slate-50 dark:bg-slate-950 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 p-4 text-center">
              <p className="text-xs text-slate-400 dark:text-slate-500">Clique sur une bulle pour voir le détail du cluster</p>
            </div>
          )}

        </div>
      </div>

      {/* Liste des thèses du cluster sélectionné */}
      {showTheses && selectedCluster && clusterTheses.length > 0 && (
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100 dark:border-slate-800">
            <div>
              <span className="text-sm font-semibold text-slate-800 dark:text-slate-100">{selectedCluster.label}</span>
              <span className="ml-2 text-xs text-slate-400">{clusterTheses.length} thèses</span>
            </div>
            <button onClick={() => setShowTheses(false)} className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xs">✕</button>
          </div>
          <div className="divide-y divide-slate-100 dark:divide-slate-800 max-h-80 overflow-y-auto">
            {clusterTheses.map(t => (
              <div key={t.id} className="px-5 py-3 flex items-start gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <div className="flex-1 min-w-0">
                  <p className="text-sm text-slate-800 dark:text-slate-100 leading-snug">{t.titre}</p>
                  <div className="flex flex-wrap gap-2 mt-1.5">
                    <span className="text-xs text-slate-400 dark:text-slate-500">{t.annee}</span>
                    <span className="text-xs text-slate-400 dark:text-slate-500">·</span>
                    <span className="text-xs text-slate-500 dark:text-slate-400">{t.etablissement_norm}</span>
                    {t.directeurs?.length > 0 && (
                      <>
                        <span className="text-xs text-slate-400 dark:text-slate-500">·</span>
                        <span className="text-xs text-slate-400 dark:text-slate-500">{t.directeurs.join(', ')}</span>
                      </>
                    )}
                  </div>
                </div>
                {t.accessible && (
                  <span className="shrink-0 text-xs bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/50 rounded-full px-2 py-0.5">OA</span>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Chart 2 & 3: Keyword Trends and Drill-Down */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-8 min-h-[500px] mt-4">
        {/* Trend Chart */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 flex flex-col shadow-sm">
          <p className="text-sm font-semibold text-slate-700 dark:text-slate-100 mb-1">Tendances des mots-clés</p>
          <p className="text-xs text-slate-500 dark:text-slate-400 mb-4 flex-none">
            {filters?.annee 
              ? `Top mot-clé par domaine (CNU) pour l'année ${filters.annee}.`
              : "Chaque courbe représente une discipline (CNU). Survolez les points pour découvrir quel était le mot le plus utilisé cette année-là !"}
          </p>
          <div className="flex-1 w-full relative">
            <KeywordTrends data={data || []} selectedYear={filters?.annee} />
          </div>
        </div>

        {/* Drill-down Chart */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 flex flex-col shadow-sm">
          <p className="text-sm font-semibold text-slate-700 dark:text-slate-100 mb-4">Top 10 des mots clés les plus utilisés par CNU</p>
          <div className="flex-1 w-full relative">
            <KeywordDrillDown filters={filters} isDarkMode={isDarkMode} />
          </div>
        </div>
      </div>

    </div>
  )
}
