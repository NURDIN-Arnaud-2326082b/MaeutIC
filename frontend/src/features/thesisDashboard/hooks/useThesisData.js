import { useEffect, useState } from 'react'
import { assetPath } from '../utils/assetPath'

export function useThesisData() {
  const [data, setData] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    let cancelled = false

    fetch(assetPath('thesis-dashboard-data.json'))
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`)
        }
        return response.json()
      })
      .then(payload => {
        if (cancelled) return
        const nextData = Array.isArray(payload)
          ? payload
          : Array.isArray(payload?.data)
            ? payload.data
            : []
        setData(nextData)
      })
      .catch(err => {
        if (cancelled) return
        setError(err)
      })

    return () => {
      cancelled = true
    }
  }, [])

  return {
    data,
    loading: data === null && error === null,
    error,
  }
}