import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { forumApi, commentApi } from '../services/apis'
import CommentSection from '../components/CommentSection'

export default function ForumPost() {
  const { id } = useParams()

  const { data: post, isLoading } = useQuery({
    queryKey: ['post', id],
    queryFn: async () => {
      const response = await forumApi.getPost(id)
      return response.data
    },
  })

  const { data: comments = [] } = useQuery({
    queryKey: ['comments', id],
    queryFn: async () => {
      const response = await commentApi.getComments(id)
      return response.data
    },
    enabled: !!id,
  })

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    )
  }

  if (!post) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen">
        <h1 className="text-2xl font-bold text-gray-900 mb-4">Post non trouvé</h1>
        <Link to="/forums" className="text-blue-600 hover:underline">
          Retour aux forums
        </Link>
      </div>
    )
  }

  return (
    <div className="flex flex-col items-center my-11">
      <div className="bg-slate-100 m-5 p-5 rounded-lg w-full max-w-5xl">
        <Link
          to={`/forums/${post.forum.title}`}
          className="text-blue-600 hover:underline mb-4 inline-block"
        >
          ← Retour aux forums
        </Link>

        <h1 className="text-2xl font-semibold mb-2">{post.name}</h1>

        <p className="text-gray-500 mb-4">
          Par {post.user ? `${post.user.firstName} ${post.user.lastName}` : 'Ancien utilisateur'}
        </p>

        <div className="bg-white p-5 rounded-lg mb-6">
          <p className="whitespace-pre-wrap">{post.description}</p>
        </div>

        <div className="flex gap-4 mb-6">
          <button className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            J'aime ({post.likesCount || 0})
          </button>
        </div>

        <CommentSection postId={id} comments={comments} />
      </div>
    </div>
  )
}
