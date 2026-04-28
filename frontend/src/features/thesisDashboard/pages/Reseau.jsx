import { useMemo, useState, useRef, useEffect } from 'react'
import ForceGraph from '../components/charts/ForceGraph'
import ArcDiagram from '../components/charts/ArcDiagram'
import MapNetwork from '../components/charts/MapNetwork'

function KpiCard({ label, value, sub, color = 'indigo' }) {
  const colors = {
    indigo: 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border-indigo-100 dark:border-indigo-800/50',
    emerald: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/50',
    amber: 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-800/50',
    rose: 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border-rose-100 dark:border-rose-800/50',
    violet: 'bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400 border-violet-100 dark:border-violet-800/50',
  }
  return (
    <div className={`rounded-2xl border p-5 flex flex-col gap-1 ${colors[color]}`}>
      <span className="text-xs font-semibold uppercase tracking-widest opacity-60">{label}</span>
      <span className="text-3xl font-bold">{value}</span>
      {sub && <span className="text-xs opacity-70">{sub}</span>}
    </div>
  )
}

export default function Reseau({ data, filters, isDarkMode }) {
  const [graphWidth, setGraphWidth] = useState(800)
  const containerRef = useRef()

  useEffect(() => {
    const el = containerRef.current
    if (!el) return
    const obs = new ResizeObserver(([entry]) => {
      setGraphWidth(entry.contentRect.width - 40) // subtract padding
    })
    obs.observe(el)
    return () => obs.disconnect()
  }, [])

  const stats = useMemo(() => {
    if (!data.length) return null

    const directorTheses = {}
    const coDirections = {}
    const directorEtabs = {}

    data.forEach(d => {
      const dirs = d.directeurs ?? []
      dirs.forEach(dir => {
        directorTheses[dir] = (directorTheses[dir] || 0) + 1
        if (!directorEtabs[dir]) directorEtabs[dir] = new Set()
        directorEtabs[dir].add(d.etablissement_norm || d.etablissement)
      })
      if (dirs.length > 1) {
        for (let i = 0; i < dirs.length; i++) {
          for (let j = i + 1; j < dirs.length; j++) {
            const key = [dirs[i], dirs[j]].sort().join('|||')
            coDirections[key] = (coDirections[key] || 0) + 1
          }
        }
      }
    })

    const nbCoDir = data.filter(d => (d.directeurs?.length ?? 0) > 1).length
    const pctCoDir = ((nbCoDir / data.length) * 100).toFixed(1)
    const nbDirecteurs = Object.keys(directorTheses).length
    const nbPairs = Object.keys(coDirections).length
    const nbBridge = Object.values(directorEtabs).filter(s => s.size > 1).length

    return { nbCoDir, pctCoDir, nbDirecteurs, nbPairs, nbBridge }
  }, [data])

  if (!stats) return <p className="p-8 text-slate-400">Aucune donnée</p>

  return (
    <div className="p-4 md:p-8 flex flex-col gap-6 md:gap-8" ref={containerRef}>

      {/* Header */}
      <div>
        <h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Analyse de réseau</h2>
        <p className="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Collaborations entre directeurs, co-encadrements et liens inter-établissements
        </p>
      </div>

      {/* KPIs */}
      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div className="rounded-2xl border p-5 flex flex-col gap-1 bg-canard-50 text-canard-700 border-canard-100">
          <span className="text-xs font-semibold uppercase tracking-widest opacity-60">Directeurs distincts</span>
          <span className="text-3xl font-bold">{stats.nbDirecteurs.toLocaleString('fr-FR')}</span>
        </div>
        <div className="rounded-2xl border p-5 flex flex-col gap-1 bg-canard-100 text-canard-800 border-canard-200">
          <span className="text-xs font-semibold uppercase tracking-widest opacity-60">Thèses co-dirigées</span>
          <span className="text-3xl font-bold">{stats.nbCoDir.toLocaleString('fr-FR')}</span>
          <span className="text-xs opacity-70">{stats.pctCoDir}% du corpus</span>
        </div>
        <div className="rounded-2xl border p-5 flex flex-col gap-1 bg-emerald-50 text-emerald-700 border-emerald-100">
          <span className="text-xs font-semibold uppercase tracking-widest opacity-60">Paires de co-directeurs</span>
          <span className="text-3xl font-bold">{stats.nbPairs.toLocaleString('fr-FR')}</span>
        </div>
        <div className="rounded-2xl border p-5 flex flex-col gap-1 bg-amber-50 text-amber-700 border-amber-100">
          <span className="text-xs font-semibold uppercase tracking-widest opacity-60">Directeurs multi-étab.</span>
          <span className="text-3xl font-bold">{stats.nbBridge}</span>
          <span className="text-xs opacity-70">actifs dans 2+ établissements</span>
        </div>
      </div>

      {/* Chart 1: Arc Diagram */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm dark:shadow-xl">
        <p className="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-1">Réseau de co-direction (Top 100)</p>
        <p className="text-xs text-slate-500 dark:text-slate-400 mb-4">
          Chaque nœud représente l'un des directeurs ayant le plus de collaborations. Les arcs relient les auteurs lorsqu'ils co-dirigent des thèses ensemble. L'épaisseur indique le volume de collaborations.
        </p>
        <div className="rounded-xl overflow-hidden">
          <ArcDiagram data={data} filters={filters} isDarkMode={isDarkMode} />
        </div>
      </div>

      {/* Chart 1.5: Graphe de Force Restitué */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xl">
        <p className="text-sm font-semibold text-slate-700 dark:text-slate-100 mb-1">Exploration interactive des co-directions</p>
        <p className="text-xs text-slate-500 dark:text-slate-400 mb-4">
          Graphique de force (Nœud = Auteur). Déplacez le curseur pour filtrer et nettoyer le graphe.
        </p>
        <div className="rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800">
          <ForceGraph data={data} width={graphWidth > 0 ? graphWidth : 800} height={550} />
        </div>
      </div>

      {/* Chart 2: Map with inter-establishment links */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
        <p className="text-sm font-semibold text-slate-700 dark:text-slate-100 mb-4">Réseau inter-établissements</p>
        <MapNetwork data={data} filters={filters} />
      </div>

    </div>
  )
}
