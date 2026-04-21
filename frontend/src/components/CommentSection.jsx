import { useState } from 'react'
import PropTypes from 'prop-types'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { commentApi } from '../services/apis'
import { useAuthStore } from '../store'
import { createReport } from '../services/reportApi'
import ReportModal from './ReportModal'

export default function CommentSection({ postId, comments }) {
  const [newComment, setNewComment] = useState('')
  const [reportModalOpen, setReportModalOpen] = useState(false)
  const [reportCommentId, setReportCommentId] = useState(null)
  const [reportReason, setReportReason] = useState('')
  const [reportCustomReason, setReportCustomReason] = useState('')
  const [reportDetails, setReportDetails] = useState('')
  const { user } = useAuthStore()
  const queryClient = useQueryClient()

  const REPORT_REASON_LABELS = {
    spam: 'Spam',
    harassment: 'Harcèlement',
    inappropriate_content: 'Contenu inapproprié',
    impersonation: "Usurpation d'identité",
    other: 'Autre',
  }

  const createCommentMutation = useMutation({
    mutationFn: (comment) => commentApi.createComment({ postId, ...comment }),
    onSuccess: () => {
      queryClient.invalidateQueries(['comments', postId])
      setNewComment('')
    },
  })

  const reportMutation = useMutation({
    mutationFn: createReport,
    onSuccess: () => {
      setReportModalOpen(false)
      setReportCommentId(null)
      setReportReason('')
      setReportCustomReason('')
      setReportDetails('')
      alert('Signalement envoye avec succes')
    },
    onError: (error) => {
      alert(error.response?.data?.error || 'Erreur lors du signalement')
    },
  })

  const openReportModal = (commentId) => {
    setReportCommentId(commentId)
    setReportReason('')
    setReportCustomReason('')
    setReportDetails('')
    setReportModalOpen(true)
  }

  const handleReportComment = (event) => {
    event.preventDefault()

    const reasonText = reportReason === 'other'
      ? reportCustomReason.trim()
      : REPORT_REASON_LABELS[reportReason]

    if (!reasonText || reportCommentId === null) {
      return
    }

    reportMutation.mutate({
      targetType: 'comment',
      targetId: reportCommentId,
      reason: reasonText,
      details: reportDetails.trim(),
    })
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    if (!newComment.trim()) return
    
    createCommentMutation.mutate({ content: newComment })
  }

  return (
    <div className="mt-8">
      <h2 className="text-xl font-semibold mb-4">
        Commentaires ({comments.length})
      </h2>

      {/* Comment Form */}
      {user && (
        <form onSubmit={handleSubmit} className="mb-6">
          <textarea
            value={newComment}
            onChange={(e) => setNewComment(e.target.value)}
            placeholder="Ajouter un commentaire..."
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            rows="3"
          />
          <button
            type="submit"
            disabled={createCommentMutation.isPending}
            className="mt-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {createCommentMutation.isPending ? 'Envoi...' : 'Commenter'}
          </button>
        </form>
      )}

      {/* Comments List */}
      <div className="space-y-4">
        {comments.map((comment) => (
          <div key={comment.id} className="bg-white p-4 rounded-lg shadow-sm">
            <div className="flex items-start gap-3">
              <img
                src={comment.user?.profileImage || '/images/default-avatar.png'}
                alt={comment.user?.username}
                className="w-10 h-10 rounded-full object-cover"
              />
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <span className="font-semibold text-gray-900">
                    {comment.user ? `${comment.user.firstName} ${comment.user.lastName}` : 'Utilisateur supprimé'}
                  </span>
                  <span className="text-sm text-gray-500">
                    {new Date(comment.creationDate).toLocaleDateString('fr-FR')}
                  </span>
                </div>
                <p className="text-gray-700">{comment.body}</p>
                {user && comment.user?.id !== user.id && (
                  <button
                    onClick={() => openReportModal(comment.id)}
                    disabled={reportMutation.isPending}
                    className="mt-2 text-sm text-orange-700 hover:text-orange-900 disabled:opacity-50"
                  >
                    Signaler
                  </button>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>

      <ReportModal
        open={reportModalOpen}
        title="Signaler un commentaire"
        targetLabel={reportCommentId ? 'Cible: commentaire signalé' : ''}
        reason={reportReason}
        customReason={reportCustomReason}
        details={reportDetails}
        submitting={reportMutation.isPending}
        onClose={() => setReportModalOpen(false)}
        onReasonChange={setReportReason}
        onCustomReasonChange={setReportCustomReason}
        onDetailsChange={setReportDetails}
        onSubmit={handleReportComment}
      />
    </div>
  )
}

CommentSection.propTypes = {
  postId: PropTypes.number.isRequired,
  comments: PropTypes.array.isRequired,
}
