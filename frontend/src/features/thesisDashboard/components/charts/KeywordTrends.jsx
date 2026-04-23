import { useMemo } from 'react'
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, ResponsiveContainer, Legend, LabelList } from 'recharts'

// Expanded color palette for all CNUs
const CNU_PALETTE = [
  '#016d76', '#ec8927', '#f59e0b', '#10b981', '#06b6d4',
  '#8b5cf6', '#f43f5e', '#14b8a6', '#f97316', '#84cc16',
  '#3b82f6', '#0ea5e9', '#d946ef', '#ef4444', '#eab308',
  '#22c55e', '#a855f7', '#3f6212', '#9f1239', '#1e3a8a'
]

// Deterministic color assignment per CNU
const getColorForCnu = (cnuStr) => {
  let hash = 0;
  for (let i = 0; i < cnuStr.length; i++) {
    hash = cnuStr.charCodeAt(i) + ((hash << 5) - hash);
  }
  return CNU_PALETTE[Math.abs(hash) % CNU_PALETTE.length];
}

const CustomTooltip = ({ active, payload, label, isBar }) => {
  if (active && payload && payload.length) {
    if (isBar) {
      const p = payload[0]
      return (
        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl rounded-xl p-3 text-sm min-w-[200px] z-50">
          <p className="font-bold text-slate-700 dark:text-slate-100 mb-1 leading-tight">{p.payload.fullCnu}</p>
          <div className="mt-2 text-slate-600 dark:text-slate-300">
            Mot n°1 : <span className="font-bold text-base" style={{ color: p.fill }}>"{p.payload.word}"</span>
            <br />
            <span className="text-xs text-slate-400 dark:text-slate-500">({p.value} occurrences cette année-là)</span>
          </div>
        </div>
      )
    }

    // Sort payload by value descending for the line chart tooltip
    const sortedPayload = [...payload].sort((a, b) => b.value - a.value)

    return (
      <div className="bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm border border-slate-200 dark:border-slate-800 shadow-2xl rounded-xl p-3 text-sm min-w-[250px] z-50 pointer-events-none">
        <p className="font-bold text-slate-800 dark:text-slate-100 border-b border-slate-100 dark:border-slate-800 pb-1.5 mb-1.5 sticky top-0 bg-transparent">Année {label}</p>
        <div className="flex flex-col gap-1">
          {sortedPayload.map(p => {
            if (p.value === 0) return null; // hide 0 values
            return (
              <div key={p.dataKey} className="flex flex-col bg-slate-50 dark:bg-slate-800 px-2.5 py-1 rounded-md" style={{ borderLeft: `3px solid ${p.stroke}` }}>
                <span className="font-semibold text-slate-600 dark:text-slate-300 text-[10px] uppercase tracking-wider truncate" title={p.dataKey}>{p.dataKey}</span>
                <div className="flex justify-between items-baseline mt-px">
                  <span className="font-bold text-slate-800 dark:text-slate-100 text-[13px]" style={{ color: p.stroke }}>"{p.payload[`word_${p.dataKey}`] || '-'}"</span>
                  <span className="text-[11px] text-slate-500 dark:text-slate-400 font-medium ml-2">{p.value} occ.</span>
                </div>
              </div>
            )
          })}
        </div>
      </div>
    );
  }
  return null;
};

export default function KeywordTrends({ data, selectedYear }) {
  const { chartData, lines, isBar } = useMemo(() => {
    if (!data.length) return { chartData: [], lines: [], isBar: !!selectedYear }

    if (selectedYear) {
      // ---- BAR CHART MODE : Top word per CNU for this specific year ----
      const cnuWordCounts = {}
      data.forEach(d => {
        if (!d.mots_cles || !Array.isArray(d.mots_cles) || !d.cnu_norm) return
        const c = d.cnu_norm
        if (!cnuWordCounts[c]) cnuWordCounts[c] = {}
        
        d.mots_cles.forEach(w => {
          const word = w.toLowerCase().trim()
          if (word) cnuWordCounts[c][word] = (cnuWordCounts[c][word] || 0) + 1
        })
      })

      const barData = []
      Object.entries(cnuWordCounts).forEach(([cnu, wordsMap]) => {
        let maxW = ''
        let maxC = 0
        Object.entries(wordsMap).forEach(([w, count]) => {
          if (count > maxC) {
            maxC = count
            maxW = w
          }
        })
        if (maxC > 0) {
          barData.push({ 
            cnu: cnu.substring(0, 18) + (cnu.length > 18 ? '...' : ''), 
            fullCnu: cnu, 
            word: maxW, 
            freq: maxC,
            fill: getColorForCnu(cnu) // Ensure consistent color here too
          })
        }
      })
      
      barData.sort((a,b) => b.freq - a.freq)
      
      return { chartData: barData.slice(0, 10), lines: [], isBar: true }
      
    } else {
      // ---- LINE CHART MODE : Curves for ALL CNUs tracking their #1 word each year ----
      const cnuGlobalCounts = {}
      const yearCnuWordCounts = {}
      
      data.forEach(d => {
        if (!d.annee || !d.cnu_norm) return
        const y = d.annee
        const c = d.cnu_norm
        
        cnuGlobalCounts[c] = (cnuGlobalCounts[c] || 0) + 1
        
        if (!yearCnuWordCounts[y]) yearCnuWordCounts[y] = {}
        if (!yearCnuWordCounts[y][c]) yearCnuWordCounts[y][c] = {}
        
        if (!d.mots_cles || !Array.isArray(d.mots_cles)) return
        
        d.mots_cles.forEach(w => {
          const word = w.toLowerCase().trim()
          if (!word) return
          yearCnuWordCounts[y][c][word] = (yearCnuWordCounts[y][c][word] || 0) + 1
        })
      })

      // Get ALL unique CNUs present in current dataset (no slicing)
      const allDistinctCNUs = Object.keys(cnuGlobalCounts)

      const years = Object.keys(yearCnuWordCounts).sort()
      const trends = years.map(y => {
        const row = { annee: y }
        allDistinctCNUs.forEach(c => {
          const wordsMap = yearCnuWordCounts[y][c]
          let maxWord = ''
          let maxCount = 0
          if (wordsMap) {
            Object.entries(wordsMap).forEach(([w, count]) => {
              if (count > maxCount) {
                maxCount = count
                maxWord = w
              }
            })
          }
          row[c] = maxCount || 0
          row[`word_${c}`] = maxWord
        })
        return row
      })

      return { chartData: trends, lines: allDistinctCNUs, isBar: false }
    }
  }, [data, selectedYear])

  if (!chartData.length) {
    return (
      <div className="h-[400px] flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
        <p className="text-slate-400 dark:text-slate-500 text-sm">Pas assez de données pour afficher les tendances.</p>
      </div>
    )
  }

  return (
    <div className="h-[400px] w-full mt-4 bg-transparent rounded-xl">
      <ResponsiveContainer width="100%" height="100%">
        {isBar ? (
          <BarChart data={chartData} layout="vertical" margin={{ left: -10, right: 100, top: 10, bottom: 10 }}>
            <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#e2e8f0" />
            <XAxis type="number" tick={{ fill: '#64748b', fontSize: 12 }} />
            <YAxis dataKey="cnu" type="category" width={140} tick={{ fill: '#475569', fontSize: 11, fontWeight: 500 }} />
            <RechartsTooltip content={<CustomTooltip isBar={true} />} cursor={{ fill: '#f8fafc' }} />
            {/* Using the dynamically assigned 'fill' dynamically handled by Recharts from data if specified */}
            <Bar dataKey="freq" radius={[0, 4, 4, 0]}>
              <LabelList dataKey="word" position="right" fill="#64748b" fontSize={12} fontWeight="600" formatter={(val) => `"${val}"`} />
            </Bar>
          </BarChart>
        ) : (
          <LineChart data={chartData} margin={{ left: -10, right: 20, top: 10, bottom: 10 }}>
            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
            <XAxis dataKey="annee" tick={{ fill: '#64748b', fontSize: 12 }} dy={10} />
            <YAxis tick={{ fill: '#64748b', fontSize: 12 }} dx={-10} />
            <RechartsTooltip content={<CustomTooltip isBar={false} />} />
            {/* Legend is hidden now as there are too many lines, hovering the tooltip acts as legend */}
            {lines.map((c) => (
              <Line 
                key={c} 
                type="monotone" 
                dataKey={c} 
                name={c.substring(0, 20) + (c.length > 20 ? '...' : '')}
                stroke={getColorForCnu(c)} 
                strokeWidth={2} 
                dot={{ r: 3, strokeWidth: 1, fill: '#fff' }} 
                activeDot={{ r: 6 }} 
              />
            ))}
          </LineChart>
        )}
      </ResponsiveContainer>
    </div>
  )
}
