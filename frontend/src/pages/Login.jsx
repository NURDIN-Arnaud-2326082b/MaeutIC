import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '../services/apis'
import { useAuthStore } from '../store'

export default function Login() {
  const navigate = useNavigate()
  const { login } = useAuthStore()
  const [formData, setFormData] = useState({
    username: '',
    password: '',
  })
  const [error, setError] = useState('')

  const loginMutation = useMutation({
    mutationFn: (credentials) => authApi.login(credentials),
    onSuccess: (response) => {
      login(response.data.user)
      navigate('/')
    },
    onError: (error) => {
      setError(error.response?.data?.message || 'Erreur de connexion')
    },
  })

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    loginMutation.mutate(formData)
  }

  return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="bg-white/60 p-8 rounded-lg shadow-lg w-full max-w-md">
        <h1 className="text-2xl font-bold mb-6 text-center">Connexion</h1>
        
        {error && (
          <div className="mb-4 p-3 rounded bg-red-100 border border-red-400 text-red-700 text-center">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} autoComplete="on">
          <div className="mb-4">
            <label htmlFor="username" className="block text-sm font-medium text-gray-700">
              Nom d'utilisateur
            </label>
            <input
              type="text"
              value={formData.username}
              onChange={(e) => setFormData({ ...formData, username: e.target.value })}
              name="username"
              id="username"
              className="mt-1 block w-full border border-gray-300 rounded-md p-2"
              autoComplete="username"
              required
              autoFocus
            />
          </div>
          
          <div className="mb-4">
            <label htmlFor="password" className="block text-sm font-medium text-gray-700">
              Mot de passe
            </label>
            <input
              type="password"
              value={formData.password}
              onChange={(e) => setFormData({ ...formData, password: e.target.value })}
              name="password"
              id="password"
              className="mt-1 block w-full border border-gray-300 rounded-md p-2"
              autoComplete="current-password"
              required
            />
          </div>
          
          <button
            className="w-full bg-blue-700 text-white py-2 rounded hover:bg-blue-800"
            type="submit"
            disabled={loginMutation.isPending}
          >
            {loginMutation.isPending ? 'Connexion...' : 'Se connecter'}
          </button>

          <div className="mt-4 text-center">
            <Link
              to="/forgot-password"
              className="text-blue-600 hover:text-blue-800 text-sm font-medium"
            >
              Mot de passe oublié ?
            </Link>
          </div>
        </form>
      </div>
    </div>
  )
}
