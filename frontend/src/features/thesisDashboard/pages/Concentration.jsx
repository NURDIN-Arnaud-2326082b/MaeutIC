import { useMemo, useState } from 'react'
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid } from 'recharts'

const ETAB_NORM = {
  'Université de Brest (2025-....)':  'Université de Brest',
  'Université de Brest (2025-….)':   'Université de Brest',
  'Brest':                            'Université de Brest',

  'Université de Lille (2018-2021)':  'Université de Lille',
  'Université de Lille (2022-....)':  'Université de Lille',

  'Rennes 1':                         'Université de Rennes',
  'Université de Rennes (2023-....)': 'Université de Rennes',
  'Rennes 2':                         'Université Rennes 2',

  'Toulouse 1':                       'Université Toulouse Capitole',
  'Toulouse 2':                       'Université Toulouse - Jean Jaurès',
  'Toulouse 3':                       'Université Toulouse III - Paul Sabatier',
  'Université de Toulouse (2023-....)': 'Université de Toulouse',

  'Montpellier':                      'Université de Montpellier',
  'Université de Montpellier (2022-....)': 'Université de Montpellier',
  'Montpellier 3':                    'Université Paul-Valéry Montpellier 3',
  'Université de Montpellier Paul-Valéry': 'Université Paul-Valéry Montpellier 3',

  'Saint-Etienne':                    'Université Jean Monnet Saint-Étienne',
  'Saint-Etienne, Université Jean Monnet (2025-....)': 'Université Jean Monnet Saint-Étienne',

  'Nîmes':                            'Université de Nîmes',
  'Nîmes Université':                 'Université de Nîmes',

  'université Paris-Saclay':          'Université Paris-Saclay',

  'Dijon, Université Bourgogne Europe': 'Université de Bourgogne',

  'Nantes':                           'Nantes Université',

  'Lyon 1':                           'Université Claude Bernard Lyon 1',
  'Lyon 2':                           'Université Lumière Lyon 2',
  'Lyon 3':                           'Université Jean Moulin Lyon 3',

  'Paris 1':                          'Université Paris 1 Panthéon-Sorbonne',
  'Paris 2':                          'Université Paris-Panthéon-Assas',
  'Paris 3':                          'Université Sorbonne Nouvelle',
  'Paris 8':                          'Université Paris 8 Vincennes-Saint-Denis',
  'Paris 10':                         'Université Paris Nanterre',
  'Paris 12':                         'Université Paris-Est Créteil',
  'Paris 13':                         'Université Sorbonne Paris Nord',
}

const normEtab = (raw) => ETAB_NORM[raw] ?? raw

const TooltipStyle = {
  contentStyle: { fontSize: 11, padding: '4px 10px', border: 'none', background: '#1e293b', color: '#fff', borderRadius: 6 },
  itemStyle: { color: '#fff' },
}

function TopSelector({ value, onChange }) {
  return (
    <div className="flex items-center gap-2">
      <span className="text-xs text-slate-500 dark:text-slate-400">Top</span>
      <select
        value={value}
        onChange={e => onChange(Number(e.target.value))}
        className="text-sm border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 outline-none"
      >
        {[5, 10, 20, 30, 50].map(n => (
          <option key={n} value={n}>{n}</option>
        ))}
      </select>
    </div>
  )
}

function Card({ children, className = '' }) {
  return (
    <div className={`bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 ${className}`}>
      {children}
    </div>
  )
}

export default function Concentration({ data }) {
  const [topEtab, setTopEtab] = useState(10)
  const [topDir, setTopDir] = useState(10)

  const stats = useMemo(() => {
    if (!data.length) return null

    const etabCount = {}
    data.forEach(d => {
      const e = normEtab(d.etablissement)
      etabCount[e] = (etabCount[e] || 0) + 1
    })
    const etabEntries = Object.entries(etabCount).sort((a, b) => b[1] - a[1])

    const dirCount = {}
    data.forEach(d => d.directeurs?.forEach(dir => {
      dirCount[dir] = (dirCount[dir] || 0) + 1
    }))
    const dirEntries = Object.entries(dirCount).sort((a, b) => b[1] - a[1])

    return { etabEntries, dirEntries }
  }, [data])

  if (!stats) return <p className="p-8 text-slate-400">Aucune donnée</p>

  const etabData = stats.etabEntries.slice(0, topEtab).map(([name, nb]) => ({ name, nb }))
  const dirData  = stats.dirEntries.slice(0, topDir).map(([name, nb]) => ({ name, nb }))

  return (
    <div className="p-4 md:p-8 flex flex-col gap-6 md:gap-8">

      <div>
        <h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Concentration</h2>
        <p className="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Distribution des thèses par établissement et directeur
        </p>
      </div>

      {/* Établissements */}
      <Card>
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-base font-semibold text-slate-700 dark:text-slate-100">Thèses par établissement</h3>
          <TopSelector value={topEtab} onChange={setTopEtab} />
        </div>
        <div className="overflow-x-auto">
          <div style={{ minWidth: 560 }}>
            <ResponsiveContainer width="100%" height={Math.max(300, topEtab * 28)}>
              <BarChart data={etabData} layout="vertical" margin={{ left: 240, right: 50, top: 0, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" horizontal={false} />
                <XAxis type="number" tick={{ fontSize: 11 }} />
                <YAxis type="category" dataKey="name" width={230} tick={{ fontSize: 11 }} />
                <Tooltip {...TooltipStyle} formatter={v => [v.toLocaleString('fr-FR'), 'thèses']} />
                <Bar dataKey="nb" fill="#016d76" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </Card>

      {/* Directeurs */}
      <Card>
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-base font-semibold text-slate-700 dark:text-slate-100">Thèses par directeur</h3>
          <TopSelector value={topDir} onChange={setTopDir} />
        </div>
        <div className="overflow-x-auto">
          <div style={{ minWidth: 560 }}>
            <ResponsiveContainer width="100%" height={Math.max(300, topDir * 28)}>
              <BarChart data={dirData} layout="vertical" margin={{ left: 240, right: 50, top: 0, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" horizontal={false} />
                <XAxis type="number" tick={{ fontSize: 11 }} />
                <YAxis type="category" dataKey="name" width={230} tick={{ fontSize: 11 }} />
                <Tooltip {...TooltipStyle} formatter={v => [v, 'thèses encadrées']} />
                <Bar dataKey="nb" fill="#ec8927" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </Card>

    </div>
  )
}
