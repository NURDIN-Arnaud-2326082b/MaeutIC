import { useMemo } from 'react'
import { MapContainer, TileLayer, CircleMarker, Tooltip } from 'react-leaflet'
import 'leaflet/dist/leaflet.css'
import SparkLine from '../components/charts/SparkLine'
import { allData } from '../hooks/useFilteredData'


function KpiCard({ label, value, sub, color = 'indigo' }) {
  const colors = {
    indigo:      'bg-canard-50 text-canard-700 border-canard-100',
    canardlight: 'bg-canard-100 text-canard-800 border-canard-200',
    orange:      'bg-mandarine-50 text-mandarine-700 border-mandarine-100',
    ocre:        'bg-mandarine-100 text-mandarine-700 border-mandarine-200',
    emerald: 'bg-emerald-50 text-emerald-700 border-emerald-100',
    amber:   'bg-amber-50 text-amber-700 border-amber-100',
    rose:    'bg-rose-50 text-rose-700 border-rose-100',
    bluegrey:'bg-slate-100 text-slate-600 border-slate-200',
    blue:    'bg-blue-50 text-blue-700 border-blue-100',
  }
  return (
    <div className={`rounded-2xl border p-3.5 flex flex-col gap-0.5 ${colors[color]}`}>
      <span className="text-xs font-semibold uppercase tracking-widest opacity-60">{label}</span>
      <span className="text-2xl font-bold">{value}</span>
      {sub && <span className="text-xs opacity-70">{sub}</span>}
    </div>
  )
}


export default function Overview({ data }) {
  // Si la recherche ne donne aucun résultat, les visuels restent sur les données globales
  const visData = data.length > 0 ? data : allData

  const stats = useMemo(() => {
    const nbTheses = visData.length
    const nbEtabs = new Set(visData.map(d => d.etablissement_norm)).size
    const tauxCoEnc = (visData.filter(d => d.directeurs?.length > 1).length / nbTheses * 100).toFixed(1)
    const nbDirecteurs = new Set(visData.flatMap(d => d.directeurs ?? [])).size

    // Sparkline : thèses par année
    const byYear = {}
    visData.forEach(d => { byYear[d.annee] = (byYear[d.annee] || 0) + 1 })
    const sparkData = Object.entries(byYear).sort().map(([annee, nb]) => ({ annee: +annee, nb }))

    // Carte : agrégation par établissement (lat/lon + count)
    const etabMap = {}
    visData.forEach(d => {
      if (d.lat == null || d.lon == null) return
      const key = d.etablissement_norm
      if (!etabMap[key]) etabMap[key] = { lat: d.lat, lon: d.lon, nb: 0, name: key }
      etabMap[key].nb++
    })
    const mapPoints = Object.values(etabMap)
    const maxNb = Math.max(...mapPoints.map(p => p.nb))

    // CNU distribution
    const byCnu = {}
    const cnuNormMap = {}
    visData.forEach(d => {
      byCnu[d.cnu] = (byCnu[d.cnu] || 0) + 1
      if (d.cnu && d.cnu_norm) cnuNormMap[d.cnu] = d.cnu_norm
    })
    const cnuData = Object.entries(byCnu)
      .sort((a, b) => b[1] - a[1])
      .map(([cnu, nb]) => ({ cnu, label: cnuNormMap[cnu] || cnu, nb, pct: (nb / nbTheses * 100).toFixed(1) }))

    return { nbTheses, nbEtabs, nbDirecteurs, tauxCoEnc, sparkData, mapPoints, maxNb, cnuData }
  }, [visData])

  return (
    <div className="p-4 md:p-8 flex flex-col gap-6 md:gap-8">

      {/* Header */}
      <div>
        <h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Vue d'ensemble</h2>
        <p className="text-slate-500 dark:text-slate-400 text-sm mt-1">
          {stats.nbTheses.toLocaleString('fr-FR')} thèses · 2021–2026 · {stats.nbEtabs} établissements
        </p>
      </div>

      {/* KPIs */}
      <div id="tour-kpis" className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <KpiCard label="Thèses totales" value={stats.nbTheses.toLocaleString('fr-FR')} color="indigo" />
        <KpiCard label="Établissements" value={stats.nbEtabs} color="orange" />
        <KpiCard label="Directeurs distincts" value={stats.nbDirecteurs.toLocaleString('fr-FR')} sub="chercheurs encadrants" color="indigo" />
        <KpiCard label="Co-encadrement" value={`${stats.tauxCoEnc}%`} sub="thèses avec plusieurs directeurs" color="orange" />
      </div>

      {/* Évolution annuelle */}
      <div id="tour-sparkline" className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
        <p className="text-sm font-semibold text-slate-700 dark:text-slate-100 mb-3">Évolution annuelle</p>
        <div className="flex items-end gap-6">
          <div className="flex-1">
            <SparkLine data={stats.sparkData} />
            <div className="flex justify-between mt-1">
              {stats.sparkData.map(d => (
                <span key={d.annee} className="text-xs text-slate-400 dark:text-slate-500">{d.annee}</span>
              ))}
            </div>
          </div>
          <div className="text-right shrink-0">
            {stats.sparkData.map(d => (
              <div key={d.annee} className="text-xs text-slate-600 dark:text-slate-300">
                <span className="font-medium">{d.nb.toLocaleString('fr-FR')}</span>
                <span className="text-slate-400 dark:text-slate-500 ml-1">en {d.annee}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Carte + CNU */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div id="tour-map" className="xl:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
          <p className="text-sm font-semibold text-slate-700 dark:text-slate-100 mb-3">Répartition géographique</p>
          <div className="rounded-xl overflow-hidden" style={{ height: 380, position: 'relative', zIndex: 0 }}>
            <MapContainer center={[46.5, 2.5]} zoom={5} style={{ height: '100%', width: '100%' }} scrollWheelZoom={false}>
              <TileLayer
                url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png"
                attribution='&copy; <a href="https://carto.com">CARTO</a>'
              />
              {stats.mapPoints.map(p => (
                <CircleMarker
                  key={p.name}
                  center={[p.lat, p.lon]}
                  radius={Math.max(4, Math.sqrt(p.nb / stats.maxNb) * 28)}
                  pathOptions={{ fillColor: '#016d76', fillOpacity: 0.65, color: '#014f57', weight: 1 }}
                >
                  <Tooltip>
                    <span className="font-semibold">{p.name}</span><br />
                    {p.nb.toLocaleString('fr-FR')} thèse{p.nb > 1 ? 's' : ''}
                  </Tooltip>
                </CircleMarker>
              ))}
            </MapContainer>
          </div>
        </div>

        <div id="tour-cnu" className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
          <p className="text-sm font-semibold text-slate-700 dark:text-slate-100 mb-4">Par section CNU</p>
          <div className="flex flex-col gap-3 overflow-y-auto" style={{ maxHeight: 380 }}>
            {stats.cnuData.map(({ cnu, label, nb, pct }) => (
              <div key={cnu}>
                <div className="flex justify-between text-xs mb-1">
                  <span className="text-slate-700 dark:text-slate-200 font-medium">{cnu} – {label}</span>
                  <span className="text-slate-500 dark:text-slate-400">{nb.toLocaleString('fr-FR')}</span>
                </div>
                <div className="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div className="h-full bg-mandarine-500 rounded-full transition-all" style={{ width: `${pct}%` }} />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

    </div>
  )
}
