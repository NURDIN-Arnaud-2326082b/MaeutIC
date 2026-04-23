import { useMemo, useState } from 'react'
import { ResponsiveTreeMap } from '@nivo/treemap'
import { allData } from '../../hooks/useFilteredData'

// Consistent colors for CNUs
const CNU_PALETTE = [
  '#016d76', '#ec8927', '#f59e0b', '#10b981', '#06b6d4',
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

export default function KeywordDrillDown({ filters, isDarkMode }) {
  const [drillCnu, setDrillCnu] = useState(null)

  // Pre-calculate the tree structures
  const { rootCnus, cnuWordsMap } = useMemo(() => {
    let baseData = allData
    // We respect global filters (etablissement and annee)
    if (filters?.etablissement) {
      baseData = baseData.filter(d => d.etablissement_norm === filters.etablissement)
    }
    if (filters?.annee) {
      baseData = baseData.filter(d => d.annee === filters.annee)
    }

    const cnuCounts = {}
    const cnuWords = {}

    baseData.forEach(d => {
      const c = d.cnu_norm || 'Autre'
      cnuCounts[c] = (cnuCounts[c] || 0) + 1
      
      if (!cnuWords[c]) cnuWords[c] = {}

      if (d.mots_cles && Array.isArray(d.mots_cles)) {
        d.mots_cles.forEach(w => {
          const lowerW = w.toLowerCase().trim()
          if (!lowerW) return
          cnuWords[c][lowerW] = (cnuWords[c][lowerW] || 0) + 1
        })
      }
    })

    // Prepare Root Level (List of CNUs with at least 10 theses)
    const rootCnus = Object.entries(cnuCounts)
      .filter(([, count]) => count >= 10)
      .sort((a,b) => b[1] - a[1])
      .map(([name, count]) => ({
        id: name,
        name: name,
        loc: count,
        color: getColorForCnu(name)
      }))

    // Prepare Second Level (Top 10 words per CNU)
    const cnuWordsMap = {}
    Object.entries(cnuWords).forEach(([cname, wordsMap]) => {
      const topWords = Object.entries(wordsMap)
        .sort((a,b) => b[1] - a[1])
        .slice(0, 10) // Top 10 mots-clés
        .map(([w, freq]) => ({
          id: w,
          name: w,
          loc: freq
        }))
      cnuWordsMap[cname] = topWords
    })

    return { rootCnus, cnuWordsMap }
  }, [filters?.etablissement])

  const displayTree = useMemo(() => {
    if (!drillCnu) {
      return {
        id: 'Toutes les disciplines',
        name: 'Toutes les disciplines',
        children: rootCnus
      }
    } else {
      return {
        id: drillCnu,
        name: drillCnu,
        children: cnuWordsMap[drillCnu] || []
      }
    }
  }, [drillCnu, rootCnus, cnuWordsMap])

  const handleClick = (node) => {
    if (!drillCnu) {
      // We clicked a CNU from the root
      setDrillCnu(node.data.name)
    } else {
      // We are already inside a CNU, clicking a word does nothing (or goes back)
      setDrillCnu(null)
    }
  }

  if (!rootCnus.length) {
    return (
      <div className="h-[400px] flex items-center justify-center bg-slate-50 dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl mt-4">
        <p className="text-slate-400">Aucune donnée disponible.</p>
      </div>
    )
  }

  return (
    <div className="relative w-full h-[450px] flex flex-col mt-2">
      <div className={`flex items-center px-2 z-10 ${drillCnu ? 'justify-between mb-2' : ''}`}>
        {drillCnu && (
          <span className="text-xs font-medium text-canard-600 bg-canard-50 px-3 py-1 rounded-full border border-canard-100 shadow-sm flex items-center gap-1.5 transition-all">
            <span>📍</span>
            <span className="truncate max-w-[250px]">
              {drillCnu}
            </span>
          </span>
        )}
        {drillCnu && (
          <button 
            onClick={() => setDrillCnu(null)}
            className="text-xs px-3 py-1 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-md font-semibold shadow-sm border border-slate-200 dark:border-slate-700 transition-colors"
          >
            &larr; Revenir aux disciplines
          </button>
        )}
      </div>

      <div className="flex-1 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden p-1 shadow-sm">
        <ResponsiveTreeMap
          data={displayTree}
          identity="name"
          value="loc"
          valueFormat=".0f"
          margin={{ top: 5, right: 5, bottom: 5, left: 5 }}
          labelSkipSize={16}
          label={(e) => drillCnu ? `"${e.id}" (${e.value})` : e.id}
          labelTextColor="#ffffff"
          labelPosition="top-left"
          orientLabels={false}
          enableParentLabel={false}
          nodeOpacity={1}
          borderWidth={2}
          borderColor="#ffffff"
          colors={drillCnu ? ['#fca5a5', '#fdba74', '#fcd34d', '#f87171', '#fb923c', '#fbbf24', '#f43f5e', '#f97316', '#eab308', '#ef4444'] : (node) => node.data.color || '#3b82f6'}
          // If viewing words (pastel colors), make text darker for readability
          theme={{
            labels: {
              text: {
                fontSize: 13,
                fontWeight: 600,
                fill: drillCnu ? (isDarkMode ? '#000000' : '#334155') : '#ffffff',
                textShadow: drillCnu ? 'none' : '0px 1px 2px rgba(0,0,0,0.3)'
              }
            }
          }}
          onClick={handleClick}
          animate={true}
          motionConfig="stiff"
          tooltip={({ node }) => (
            <div className="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl rounded-xl text-sm z-50">
              <strong className="text-slate-800 dark:text-slate-100 block mb-1">
                {drillCnu ? `Mot: "${node.id}"` : `Discipline: ${node.id}`}
              </strong>
              <span className="text-slate-500 font-medium">
                {node.value} {drillCnu ? 'occurrences' : 'thèses'}
              </span>
              {!drillCnu && (
                <div className="mt-2 text-xs text-canard-600 font-semibold italic">
                  Cliquez pour voir le Top 10 des mots &rarr;
                </div>
              )}
            </div>
          )}
        />
      </div>
    </div>
  )
}
