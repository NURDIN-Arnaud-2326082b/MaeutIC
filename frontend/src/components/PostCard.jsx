import { Link } from 'react-router-dom'

export default function PostCard({ post }) {
  return (
    <Link
      to={`/forums/post/${post.id}`}
      className="block bg-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow"
    >
      <div className="flex justify-between items-start">
        <div className="flex-1">
          <h3 className="text-lg font-semibold text-gray-900 mb-2">{post.name}</h3>
          <p className="text-gray-600 text-sm mb-3 line-clamp-2">{post.description}</p>
          <div className="flex items-center gap-4 text-sm text-gray-500">
            <span>{post.author_name}</span>
            <span>•</span>
            <span>{post.creation_date}</span>
            {post.forum_title && (
              <>
                <span>•</span>
                <span className="text-blue-600">{post.forum_title}</span>
              </>
            )}
          </div>
        </div>
        {post.likesCount > 0 && (
          <div className="ml-4 flex items-center text-sm text-gray-500">
            <svg className="w-5 h-5 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clipRule="evenodd" />
            </svg>
            {post.likesCount}
          </div>
        )}
      </div>
    </Link>
  )
}
