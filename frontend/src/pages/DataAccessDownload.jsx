import { useEffect, useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import apiClient, { downloadResponseBlob, extractApiErrorMessage } from '../services/api'

export default function DataAccessDownload() {
  const { requestId } = useParams()
  const [searchParams] = useSearchParams()
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [isDone, setIsDone] = useState(false)

  useEffect(() => {
    const token = searchParams.get('token')

    if (!requestId || !token) {
      setError('Lien invalide: paramètres manquants.')
      setIsLoading(false)
      return
    }

    const run = async () => {
      try {
        const fallbackFilename = `maeutic-data-export-${requestId}.json`
        const response = await apiClient.get(`/privacy/data-access-requests/${requestId}/download`, {
          params: { token },
          responseType: 'blob',
        })

        downloadResponseBlob(response, fallbackFilename)

        setIsDone(true)
      } catch (err) {
        const apiError = await extractApiErrorMessage(err?.response?.data)
        setError(apiError || 'Téléchargement impossible. Vérifie que tu es connecté(e) et que le lien est encore valide.')
      } finally {
        setIsLoading(false)
      }
    }

    run()
  }, [requestId, searchParams])

  return (
    <div className="max-w-2xl mx-auto px-4 py-12">
      <div className="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h1 className="text-2xl font-semibold text-slate-900">Export RGPD</h1>
        <p className="text-slate-600 mt-2">Téléchargement sécurisé de vos données personnelles.</p>

        {isLoading && (
          <p className="mt-6 text-slate-700">Préparation du téléchargement...</p>
        )}

        {!isLoading && isDone && (
          <div className="mt-6 p-4 rounded-lg border border-green-300 bg-green-50 text-green-800">
            Le téléchargement a démarré. Si rien ne se passe, réessayez depuis votre email ou depuis vos paramètres.
          </div>
        )}

        {!isLoading && error && (
          <div className="mt-6 p-4 rounded-lg border border-red-300 bg-red-50 text-red-800">
            {error}
          </div>
        )}

        <div className="mt-6">
          <Link to="/settings" className="text-blue-700 hover:underline">Retour aux paramètres</Link>
        </div>
      </div>
    </div>
  )
}
