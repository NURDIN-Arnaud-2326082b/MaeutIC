import { useMemo, useRef, useCallback, useEffect, useState } from 'react'
import ForceGraph2D from 'react-force-graph-2d'

const COLORS = [
  '#016d76', '#ec8927', '#a855f7', '#d946ef', '#ec4899',
  '#f43f5e', '#ef4444', '#f97316', '#eab308', '#22c55e',
  '#14b8a6', '#06b6d4', '#3b82f6', '#1d4ed8', '#7c3aed',
]

export default function ForceGraph({ data, width = 800, height = 550 }) {
  const fgRef = useRef()
  const [minTheses, setMinTheses] = useState(3)

  const graphData = useMemo(() => {
    if (!data.length) return { nodes: [], links: [] }

    const directorTheses = {}
    const coDirectionsList = []

    data.forEach(d => {
      const dirs = d.directeurs ?? []
      dirs.forEach(dir => {
        directorTheses[dir] = (directorTheses[dir] || 0) + 1
      })
      if (dirs.length > 1) {
        for (let i = 0; i < dirs.length; i++) {
          for (let j = i + 1; j < dirs.length; j++) {
            coDirectionsList.push([dirs[i], dirs[j]])
          }
        }
      }
    })

    // Only keep directors above threshold
    const kept = new Set(
      Object.entries(directorTheses)
        .filter(([, count]) => count >= minTheses)
        .map(([name]) => name)
    )

    // Aggregate links only between kept directors
    const linkMap = {}
    coDirectionsList.forEach(([a, b]) => {
      if (!kept.has(a) || !kept.has(b)) return
      const key = [a, b].sort().join('|||')
      linkMap[key] = (linkMap[key] || 0) + 1
    })

    // Build nodes from directors that actually appear in links
    const linkedDirectors = new Set()
    Object.keys(linkMap).forEach(key => {
      const [a, b] = key.split('|||')
      linkedDirectors.add(a)
      linkedDirectors.add(b)
    })

    const nodes = [...linkedDirectors].map((name, i) => ({
      id: name,
      name,
      val: directorTheses[name] || 1,
      color: COLORS[i % COLORS.length],
    }))

    const links = Object.entries(linkMap).map(([key, count]) => {
      const [a, b] = key.split('|||')
      return { source: a, target: b, value: count }
    })

    return { nodes, links }
  }, [data, minTheses])

  useEffect(() => {
    if (fgRef.current && graphData.nodes.length > 0) {
      fgRef.current.d3Force('charge').strength(-30)
    }
  }, [graphData])

  const handleNodeHover = useCallback(node => {
    document.body.style.cursor = node ? 'pointer' : 'default'
  }, [])

  const paintNode = useCallback((node, ctx, globalScale) => {
    const r = Math.sqrt(node.val || 1) * 2
    // Glow
    ctx.beginPath()
    ctx.arc(node.x, node.y, r + 2, 0, 2 * Math.PI)
    ctx.fillStyle = `${node.color}33`
    ctx.fill()
    // Node
    ctx.beginPath()
    ctx.arc(node.x, node.y, r, 0, 2 * Math.PI)
    ctx.fillStyle = node.color
    ctx.fill()
    ctx.strokeStyle = '#fff'
    ctx.lineWidth = 0.5
    ctx.stroke()
    // Label (show for zoomed in or high-value nodes)
    if (globalScale > 2.5 || (node.val >= 8 && globalScale > 1)) {
      ctx.font = `${Math.max(3, 10 / globalScale)}px Inter, sans-serif`
      ctx.textAlign = 'center'
      ctx.textBaseline = 'top'
      ctx.fillStyle = '#334155'
      ctx.fillText(node.name, node.x, node.y + r + 2)
    }
  }, [])

  return (
    <div>
      <div className="flex items-center gap-3 mb-3">
        <label className="text-xs text-slate-500">Seuil minimum de thèses par directeur :</label>
        <input
          type="range"
          min={1}
          max={10}
          value={minTheses}
          onChange={e => setMinTheses(+e.target.value)}
          className="w-32"
        />
        <span className="text-xs font-medium text-canard-600 bg-canard-50 px-2 py-0.5 rounded-full">
          ≥ {minTheses} thèse{minTheses > 1 ? 's' : ''}
        </span>
        <span className="text-xs text-slate-400">
          ({graphData.nodes.length} directeurs, {graphData.links.length} liens)
        </span>
      </div>

      {graphData.nodes.length === 0 ? (
        <p className="text-slate-400 text-sm p-4">Aucune co-direction trouvée avec les filtres actuels. Essayez de baisser le seuil.</p>
      ) : (
        <ForceGraph2D
          ref={fgRef}
          width={width}
          height={height}
          graphData={graphData}
          nodeCanvasObject={paintNode}
          nodePointerAreaPaint={(node, color, ctx) => {
            const r = Math.sqrt(node.val || 1) * 2
            ctx.beginPath()
            ctx.arc(node.x, node.y, r + 4, 0, 2 * Math.PI)
            ctx.fillStyle = color
            ctx.fill()
          }}
          linkWidth={link => Math.sqrt(link.value) * 1.5}
          linkColor={() => 'rgba(1, 109, 118, 0.20)'}
          onNodeHover={handleNodeHover}
          cooldownTicks={100}
          backgroundColor="transparent"
          nodeLabel={node => `${node.name} — ${node.val} thèse${node.val > 1 ? 's' : ''}`}
        />
      )}
    </div>
  )
}
