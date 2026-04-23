import { useMemo } from 'react'
import { MapContainer, TileLayer, CircleMarker, Polyline, Tooltip } from 'react-leaflet'
import 'leaflet/dist/leaflet.css'
import { allData } from '../../hooks/useFilteredData'

export default function MapNetwork({ data, filters }) {
  const { etabPoints, etabLinks, maxLinkWeight, selectedEtab } = useMemo(() => {
    let baseData = allData
    if (filters?.annee) baseData = baseData.filter(d => d.annee === filters.annee)
    if (filters?.cnu) baseData = baseData.filter(d => d.cnu_norm === filters.cnu)

    if (!baseData.length) return { etabPoints: [], etabLinks: [], maxLinkWeight: 1, selectedEtab: null }

    const directorEtabs = {}
    baseData.forEach(d => {
      const dirs = d.directeurs ?? []
      const etab = d.etablissement_norm || d.etablissement
      dirs.forEach(dir => {
        if (!directorEtabs[dir]) directorEtabs[dir] = new Set()
        directorEtabs[dir].add(etab)
      })
    })

    const etabMap = {}
    baseData.forEach(d => {
      if (d.lat == null || d.lon == null) return
      const key = d.etablissement_norm || d.etablissement
      if (!etabMap[key]) etabMap[key] = { lat: d.lat, lon: d.lon, nb: 0, name: key }
      etabMap[key].nb++
    })

    const linkMap = {}
    Object.values(directorEtabs).forEach(etabs => {
      if (etabs.size < 2) return
      const arr = [...etabs]
      for (let i = 0; i < arr.length; i++) {
        for (let j = i + 1; j < arr.length; j++) {
          const key = [arr[i], arr[j]].sort().join('|||')
          linkMap[key] = (linkMap[key] || 0) + 1
        }
      }
    })

    let links = Object.entries(linkMap)
      .map(([key, count]) => {
        const [a, b] = key.split('|||')
        const pa = etabMap[a]
        const pb = etabMap[b]
        if (!pa || !pb) return null
        return { from: pa, to: pb, weight: count, label: `${a} ↔ ${b}`, a, b }
      })
      .filter(Boolean)

    const selectedEtab = filters?.etablissement
    if (selectedEtab) {
      links = links.filter(l => l.a === selectedEtab || l.b === selectedEtab)
      const linkedEtabs = new Set([selectedEtab])
      links.forEach(l => {
        linkedEtabs.add(l.a)
        linkedEtabs.add(l.b)
      })
      Object.keys(etabMap).forEach(key => {
        if (!linkedEtabs.has(key)) {
          delete etabMap[key]
        }
      })
    }

    const etabPts = Object.values(etabMap)
    links.sort((x, y) => y.weight - x.weight)
    const maxW = links.length ? links[0].weight : 1

    return { etabPoints: etabPts, etabLinks: links, maxLinkWeight: maxW, selectedEtab }
  }, [filters, data])

  if (!etabPoints.length) {
    return <p className="text-slate-400 text-sm p-4">Aucune donnée géolocalisée disponible. Veuillez sélectionner un autre établissement ou modifier vos filtres.</p>
  }

  let centerPosition = [46.8, 2.5]
  let zoomLevel = 6
  if (selectedEtab && etabPoints.length > 0) {
    const mainNode = etabPoints.find(p => p.name === selectedEtab)
    if (mainNode) {
      centerPosition = [mainNode.lat, mainNode.lon]
    }
  }

  return (
    <div className="rounded-xl overflow-hidden" style={{ height: 500, position: 'relative', zIndex: 0 }}>
      {/* Set key to centerPosition so MapContainer updates its center properly when selecting an etab. However, forcing full recreate might flash. We'll skip keying the map and let the user pan or rely on Leaflet's setView if we added a hook, but setting center natively sometimes flies if we don't care about panning. Since they just want it highlighted, preserving standard view or jumping is fine. Using a simple approach without key: */}
      <MapContainer
        key={selectedEtab || 'default'} // Forces re-center when selecting a specific establishment
        center={centerPosition}
        zoom={zoomLevel}
        style={{ height: '100%', width: '100%' }}
        scrollWheelZoom={true}
      >
        <TileLayer
          url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png"
          attribution='&copy; <a href="https://carto.com">CARTO</a>'
        />

        {/* Links between establishments */}
        {etabLinks.map(({ from, to, weight, label }, i) => {
          const opacity = Math.min(0.8, 0.15 + (weight / maxLinkWeight) * 0.65)
          const w = Math.max(1, Math.sqrt(weight / maxLinkWeight) * 6)
          return (
            <Polyline
              key={i}
              positions={[[from.lat, from.lon], [to.lat, to.lon]]}
              pathOptions={{
                color: '#6366f1',
                weight: w,
                opacity,
                dashArray: weight < 3 ? '4 6' : undefined,
              }}
            >
              <Tooltip sticky>
                <span className="font-semibold">{label}</span><br />
                {weight} directeur{weight > 1 ? 's' : ''} partagé{weight > 1 ? 's' : ''}
              </Tooltip>
            </Polyline>
          )
        })}

        {/* Establishment nodes */}
        {etabPoints.map(p => {
          const isSelected = p.name === selectedEtab
          return (
            <CircleMarker
              key={p.name}
              center={[p.lat, p.lon]}
              radius={Math.max(5, Math.sqrt(p.nb) * 1.8)}
              pathOptions={{
                fillColor: isSelected ? '#22c55e' : '#4f46e5',
                fillOpacity: isSelected ? 0.9 : 0.75,
                color: isSelected ? '#166534' : '#3730a3',
                weight: isSelected ? 2.5 : 1.5,
              }}
            >
              <Tooltip>
                <span className="font-semibold">{p.name} {isSelected && '(Sélectionné)'}</span><br />
                {p.nb.toLocaleString('fr-FR')} thèse{p.nb > 1 ? 's' : ''}
              </Tooltip>
            </CircleMarker>
          )
        })}
      </MapContainer>
    </div>
  )
}
