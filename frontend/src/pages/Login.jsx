import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuthStore } from '../store'

export default function Login() {
  const navigate = useNavigate()
  const { login } = useAuthStore()
  const [formData, setFormData] = useState({
    username: '',
    password: '',
  })
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setIsLoading(true)

    const result = await login(formData)
    
    setIsLoading(false)
    
    if (result.success) {
      navigate('/')
    } else {
      setError(result.error)
    }
  }

  return (
    <div className="flex items-center justify-center min-h-screen px-4 py-12">
      <div className="brand-surface w-full max-w-md p-8">
        <h1 className="brand-title mb-6 text-center text-4xl text-canard-900 uppercase">Connexion</h1>
        
        {error && (
          <div className="mb-4 rounded-xl border border-mandarine-200 bg-mandarine-50 p-3 text-center text-mandarine-700">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} autoComplete="on">
          <div className="mb-4">
            <label htmlFor="username" className="block text-sm font-medium text-slate-700">
              Nom d'utilisateur
            </label>
            <input
              type="text"
              value={formData.username}
              onChange={(e) => setFormData({ ...formData, username: e.target.value })}
              name="username"
              id="username"
              className="mt-1 block w-full rounded-xl border border-slate-200 bg-white/80 p-3 text-slate-800 shadow-sm outline-none transition focus:border-canard-400 focus:ring-2 focus:ring-canard-200"
              autoComplete="username"
              required
              autoFocus
            />
          </div>
          
          <div className="mb-4">
            <label htmlFor="password" className="block text-sm font-medium text-slate-700">
              Mot de passe
            </label>
            <input
              type="password"
              value={formData.password}
              onChange={(e) => setFormData({ ...formData, password: e.target.value })}
              name="password"
              id="password"
              className="mt-1 block w-full rounded-xl border border-slate-200 bg-white/80 p-3 text-slate-800 shadow-sm outline-none transition focus:border-canard-400 focus:ring-2 focus:ring-canard-200"
              autoComplete="current-password"
              required
            />
          </div>
          
          <button
            className="w-full rounded-xl bg-canard-600 py-3 text-white shadow-lg shadow-canard-950/10 transition hover:bg-canard-700 focus:outline-none focus:ring-2 focus:ring-canard-300"
            type="submit"
            disabled={isLoading}
          >
            {isLoading ? 'Connexion...' : 'Se connecter'}
          </button>

          <div className="mt-4 text-center">
            <Link
              to="/forgot-password"
              className="text-canard-700 hover:text-canard-900 text-sm font-medium"
            >
              Mot de passe oublié ?
            </Link>
          </div>
        </form>
      </div>
    </div>
  )
}
