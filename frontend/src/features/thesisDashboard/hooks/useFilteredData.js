import { useMemo } from 'react'

export function useFilteredData(sourceData = [], { annee = null, cnu = null, etablissement = null, query = '' } = {}) {
  return useMemo(() => {
    const rawData = Array.isArray(sourceData) ? sourceData : []
    const q = query.trim().toLowerCase()
    return rawData.filter(d =>
      (!annee || d.annee === annee) &&
      (!cnu || d.cnu_norm === cnu) &&
      (!etablissement || d.etablissement_norm === etablissement) &&
      (!q || q.length < 2 ||
        d.titre?.toLowerCase().includes(q)
      )
    )
  }, [sourceData, annee, cnu, etablissement, query])
}
