const DEFAULT_REASON_OPTIONS = [
  { value: 'spam', label: 'Spam' },
  { value: 'harassment', label: 'Harcèlement' },
  { value: 'inappropriate_content', label: 'Contenu inapproprié' },
  { value: 'impersonation', label: "Usurpation d'identité" },
  { value: 'other', label: 'Autre' },
]

export default function ReportModal({
  open,
  title,
  targetLabel,
  reason,
  customReason,
  details,
  submitting,
  onClose,
  onReasonChange,
  onCustomReasonChange,
  onDetailsChange,
  onSubmit,
  reasonOptions = DEFAULT_REASON_OPTIONS,
  submitLabel = 'Signaler',
}) {
  if (!open) {
    return null
  }

  const handleBackdropClick = () => {
    if (!submitting) {
      onClose()
    }
  }

  return (
    <dialog
      open
      className="fixed inset-0 z-[2000] flex items-center justify-center bg-transparent p-4 backdrop:bg-black/60"
      aria-labelledby="report-modal-title"
    >
      <button
        type="button"
        className="absolute inset-0 h-full w-full cursor-default bg-black/60"
        aria-label="Fermer la fenêtre de signalement"
        onClick={handleBackdropClick}
        disabled={submitting}
      />

      <div className="relative z-10 w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div className="flex items-start justify-between gap-4 mb-4">
          <div>
            <h2 id="report-modal-title" className="text-2xl font-semibold text-gray-900">
              {title}
            </h2>
            {targetLabel && <p className="mt-1 text-sm text-gray-500">{targetLabel}</p>}
          </div>
          <button
            type="button"
            onClick={onClose}
            disabled={submitting}
            className="rounded-full px-3 py-1 text-xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-800 disabled:opacity-40"
            aria-label="Fermer"
          >
            &times;
          </button>
        </div>

        <form onSubmit={onSubmit} className="space-y-4">
          <div>
            <label htmlFor="report-reason" className="mb-1 block text-sm font-medium text-gray-700">
              Motif
            </label>
            <select
              id="report-reason"
              value={reason}
              onChange={(event) => onReasonChange(event.target.value)}
              className="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200"
              required
            >
              <option value="">Sélectionner un motif</option>
              {reasonOptions.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>

          {reason === 'other' && (
            <div>
              <label htmlFor="report-custom-reason" className="mb-1 block text-sm font-medium text-gray-700">
                Motif personnalisé
              </label>
              <input
                id="report-custom-reason"
                type="text"
                value={customReason}
                onChange={(event) => onCustomReasonChange(event.target.value)}
                placeholder="Décrivez brièvement le problème"
                className="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200"
                required
              />
            </div>
          )}

          <div>
            <label htmlFor="report-details" className="mb-1 block text-sm font-medium text-gray-700">
              Détails supplémentaires
            </label>
            <textarea
              id="report-details"
              value={details}
              onChange={(event) => onDetailsChange(event.target.value)}
              rows={4}
              placeholder="Ajoutez du contexte si nécessaire"
              className="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200"
            />
          </div>

          <div className="flex flex-wrap justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              disabled={submitting}
              className="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50 disabled:opacity-40"
            >
              Annuler
            </button>
            <button
              type="submit"
              disabled={submitting || !reason || (reason === 'other' && !customReason.trim())}
              className="rounded-lg bg-orange-600 px-4 py-2 text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {submitting ? 'Signalement...' : submitLabel}
            </button>
          </div>
        </form>
      </div>
    </dialog>
  )
}
