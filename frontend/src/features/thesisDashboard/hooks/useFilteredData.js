import { useMemo } from 'react'
import rawData from '../data.json'

export function useFilteredData({ annee = null, cnu = null, etablissement = null, query = '' } = {}) {
  return useMemo(() => {
    const q = query.trim().toLowerCase()
    return rawData.filter(d =>
      (!annee || d.annee === annee) &&
      (!cnu || d.cnu_norm === cnu) &&
      (!etablissement || d.etablissement_norm === etablissement) &&
      (!q || q.length < 2 ||
        d.titre?.toLowerCase().includes(q)
      )
    )
  }, [annee, cnu, etablissement, query])
}

export const allData = rawData
