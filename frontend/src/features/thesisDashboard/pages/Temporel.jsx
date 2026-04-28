import { useMemo } from 'react'
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
} from 'recharts'

const PALETTE = [
  '#016d76', '#ec8927', '#db2777', '#ea580c', '#16a34a',
  '#0891b2', '#4f46e5', '#dc2626', '#65a30d', '#0f766e',
  '#9333ea', '#c2410c', '#0284c7', '#be185d', '#059669', '#6d28d9',
]

function normalizeDirecteurs(value) {
  if (Array.isArray(value)) {
    return value.map(v => String(v).trim()).filter(Boolean)
  }
  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (!trimmed) return []
    return [trimmed]
  }
  return []
}

function groupCount(items, keyBuilder) {
  const map = new Map()
  for (const item of items) {
    const key = keyBuilder(item)
    map.set(key, (map.get(key) || 0) + 1)
  }
  return map
}

function SimpleTooltip({ active, payload, label }) {
  if (!active || !payload?.length) return null

  return (
    <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-xl text-sm text-slate-800 dark:text-slate-100">
      <div className="mb-1 font-semibold">{label}</div>
      {payload.map(entry => (
        <div key={entry.dataKey} style={{ color: entry.color }}>
          {entry.name} : {entry.value}
        </div>
      ))}
    </div>
  )
}


function Card({ title, description, children, height = 360 }) {
  return (
    <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
      <div className="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">{title}</div>
      {description && <div className="mb-3 text-xs text-slate-400 dark:text-slate-500">{description}</div>}
      <div style={{ height }}>{children}</div>
    </div>
  )
}

export default function Temporel({ data = [] }) {
  const processed = useMemo(() => {
    const rows = Array.isArray(data) ? data : []

    const cleaned = rows
      .map(item => ({
        ...item,
        annee: Number(item?.annee),
        cnu_norm: String(item?.cnu_norm ?? '').trim(),
        directeurs: normalizeDirecteurs(item?.directeurs),
      }))
      .filter(item => Number.isFinite(item.annee))

    // 1. Nombre d'enregistrements par année
    const byYearMap = groupCount(cleaned, item => String(item.annee))
    const byYear = Array.from(byYearMap.entries())
      .map(([annee, count]) => ({ annee: Number(annee), count }))
      .sort((a, b) => a.annee - b.annee)

    // 2. Nombre d'enregistrements par année et par discipline
    const disciplineYearMap = new Map()
    for (const item of cleaned) {
      const discipline = item.cnu_norm || 'Non renseigné'
      const key = `${discipline}|||${item.annee}`
      disciplineYearMap.set(key, (disciplineYearMap.get(key) || 0) + 1)
    }

    const disciplineYearRows = Array.from(disciplineYearMap.entries())
      .map(([key, count]) => {
        const [discipline, annee] = key.split('|||')
        return { discipline, annee: Number(annee), count }
      })
      .sort((a, b) => a.discipline.localeCompare(b.discipline) || a.annee - b.annee)

    const disciplines = [...new Set(disciplineYearRows.map(d => d.discipline))].sort()

    const disciplineLineMap = new Map()
    for (const row of disciplineYearRows) {
      if (!disciplineLineMap.has(row.annee)) {
        disciplineLineMap.set(row.annee, { annee: row.annee })
      }
      disciplineLineMap.get(row.annee)[row.discipline] = row.count
    }

    const disciplineLineData = Array.from(disciplineLineMap.values())
      .sort((a, b) => a.annee - b.annee)

    // 3. Nombre de directeurs actifs par année
    const directorRows = []
    for (const item of cleaned) {
      for (const dir of item.directeurs) {
        directorRows.push({ annee: item.annee, directeur: dir })
      }
    }

    const years = [...new Set(directorRows.map(r => r.annee))].sort((a, b) => a - b)
    const directorsByYear = years.map(year => ({
      annee: year,
      nbDirecteurs: new Set(
        directorRows.filter(r => r.annee === year).map(r => r.directeur)
      ).size,
    }))

    return {
      total: cleaned.length,
      byYear,
      disciplines,
      disciplineLineData,
      directorsByYear,
    }
  }, [data])

  if (processed.total === 0) {
    return <div className="p-8">Aucun résultats pour votre recherche</div>
  }

  return (
    <div className="flex flex-col gap-6 md:gap-8 p-4 md:p-8">
      <div className="flex items-end justify-between">
        <div>
          <h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Évolution temporelle</h2>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Analyse des thèses dans le temps par année et discipline
          </p>
        </div>
        <span className="rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
          {processed.total.toLocaleString('fr-FR')} enregistrements
        </span>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card title="Nombre de thèses par année">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={processed.byYear}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="annee" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip content={<SimpleTooltip />} />
              <Bar dataKey="count" name="Thèses" radius={[6, 6, 0, 0]} fill={PALETTE[0]} />
            </BarChart>
          </ResponsiveContainer>
        </Card>

        <Card title="Nombre de directeurs actifs par année">
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={processed.directorsByYear}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="annee" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip content={<SimpleTooltip />} />
              <Legend />
              <Line
                type="monotone"
                dataKey="nbDirecteurs"
                name="Directeurs actifs"
                stroke={PALETTE[1]}
                strokeWidth={3}
                dot={{ r: 4 }}
              />
            </LineChart>
          </ResponsiveContainer>
        </Card>
      </div>

      <Card
        title="Thèses par année et par domaine de recherche"
        description="Une courbe par discipline"
        height={440}
      >
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={processed.disciplineLineData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="annee" tick={{ fontSize: 11 }} />
            <YAxis tick={{ fontSize: 11 }} />
            <Tooltip content={<SimpleTooltip />} />
            <Legend wrapperStyle={{ fontSize: 11 }} />
            {processed.disciplines.slice(0, 12).map((disc, i) => (
              <Line
                key={disc}
                type="monotone"
                dataKey={disc}
                name={disc}
                stroke={PALETTE[i % PALETTE.length]}
                strokeWidth={2}
                dot={{ r: 2 }}
                connectNulls
              />
            ))}
          </LineChart>
        </ResponsiveContainer>
      </Card>
    </div>
  )
}
