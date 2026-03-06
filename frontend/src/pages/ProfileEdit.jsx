import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '../store'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'

export default function ProfileEdit() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { user, isAuthenticated } = useAuthStore()
  
  const [formData, setFormData] = useState({
    email: '',
    lastName: '',
    firstName: '',
    username: '',
    affiliationLocation: '',
    specialization: '',
    researchTopic: '',
  })
  
  const [userQuestions, setUserQuestions] = useState({})
  const [taggableQuestions, setTaggableQuestions] = useState([[], []])
  const [profileImage, setProfileImage] = useState(null)
  const [tagSearchQueries, setTagSearchQueries] = useState(['', ''])
  const [tagSuggestions, setTagSuggestions] = useState([[], []])
  const [showSuggestions, setShowSuggestions] = useState([false, false])

  // Redirect if not authenticated
  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login')
    }
  }, [isAuthenticated, navigate])

  // Fetch profile data
  const { data: profileData, isLoading } = useQuery({
    queryKey: ['profile-edit'],
    queryFn: async () => {
      const response = await fetch(`${API_URL}/user/edit-data`, {
        credentials: 'include'
      })
      if (!response.ok) throw new Error('Erreur de chargement')
      return response.json()
    },
    enabled: isAuthenticated,
  })

  // Initialize form data when profile data is loaded
  useEffect(() => {
    if (profileData) {
      setFormData({
        email: profileData.user.email || '',
        lastName: profileData.user.lastName || '',
        firstName: profileData.user.firstName || '',
        username: profileData.user.username || '',
        affiliationLocation: profileData.user.affiliationLocation || '',
        specialization: profileData.user.specialization || '',
        researchTopic: profileData.user.researchTopic || '',
      })
      setUserQuestions(profileData.userQuestionsAnswers || {})
      setTaggableQuestions(profileData.taggableQuestionsAnswers || [[], []])
    }
  }, [profileData])

  // Tag search with debounce
  useEffect(() => {
    const timeouts = tagSearchQueries.map((query, index) => {
      if (query.length >= 2) {
        return setTimeout(() => {
          fetch(`${BACKEND_URL}/tag/search?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(tags => {
              const newSuggestions = [...tagSuggestions]
              newSuggestions[index] = tags.filter(tag => 
                !taggableQuestions[index].some(t => t.id === tag.id)
              )
              setTagSuggestions(newSuggestions)
              const newShow = [...showSuggestions]
              newShow[index] = tags.length > 0
              setShowSuggestions(newShow)
            })
        }, 200)
      }
      return null
    })

    return () => timeouts.forEach(timeout => timeout && clearTimeout(timeout))
  }, [tagSearchQueries])

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: async (data) => {
      const formDataToSend = new FormData()
      
      // Add JSON data
      const jsonData = {
        email: formData.email,
        lastName: formData.lastName,
        firstName: formData.firstName,
        username: formData.username,
        affiliationLocation: formData.affiliationLocation,
        specialization: formData.specialization,
        researchTopic: formData.researchTopic,
        userQuestions: userQuestions,
        taggableQuestions: taggableQuestions.map(tags => tags.map(t => t.id))
      }
      formDataToSend.append('data', JSON.stringify(jsonData))
      
      // Add image if present
      if (profileImage) {
        formDataToSend.append('profileImage', profileImage)
      }

      const response = await fetch(`${API_URL}/user/update`, {
        method: 'POST',
        credentials: 'include',
        body: formDataToSend
      })

      if (!response.ok) throw new Error('Erreur de mise à jour')
      return response.json()
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries(['user'])
      queryClient.invalidateQueries(['profile'])
      navigate(`/profile/${data.user.username}`)
    },
  })

  const handleSubmit = (e) => {
    e.preventDefault()
    updateMutation.mutate()
  }

  const handleInputChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }))
  }

  const handleQuestionChange = (index, value) => {
    setUserQuestions(prev => ({ ...prev, [index]: value }))
  }

  const addTag = (questionIndex, tag) => {
    const newTaggable = [...taggableQuestions]
    if (!newTaggable[questionIndex].some(t => t.id === tag.id)) {
      newTaggable[questionIndex] = [...newTaggable[questionIndex], tag]
      setTaggableQuestions(newTaggable)
    }
    // Clear search
    const newQueries = [...tagSearchQueries]
    newQueries[questionIndex] = ''
    setTagSearchQueries(newQueries)
    const newShow = [...showSuggestions]
    newShow[questionIndex] = false
    setShowSuggestions(newShow)
  }

  const removeTag = (questionIndex, tagId) => {
    const newTaggable = [...taggableQuestions]
    newTaggable[questionIndex] = newTaggable[questionIndex].filter(t => t.id !== tagId)
    setTaggableQuestions(newTaggable)
  }

  const handleTagSearchChange = (index, value) => {
    const newQueries = [...tagSearchQueries]
    newQueries[index] = value
    setTagSearchQueries(newQueries)
  }

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    )
  }

  if (!profileData) return null

  const fieldLabels = {
    email: 'Adresse e-mail',
    lastName: 'Nom',
    firstName: 'Prénom',
    username: "Nom d'utilisateur",
    affiliationLocation: "Lieu d'affiliation",
    specialization: 'Spécialisation',
    researchTopic: 'Sujet de recherche'
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-xl w-full mx-auto space-y-8">
        <div>
          <h1 className="mt-6 text-center text-4xl font-black text-gray-900 tracking-tight">
            Éditer mon profil
          </h1>
        </div>

        {updateMutation.isSuccess && (
          <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Profil mis à jour avec succès !
          </div>
        )}

        {updateMutation.isError && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            Erreur lors de la mise à jour du profil
          </div>
        )}

        <form 
          onSubmit={handleSubmit}
          className="space-y-6 bg-white/50 backdrop-blur-sm p-8 rounded-xl shadow-xl border border-white/20"
        >
          <div className="space-y-5">
            {/* Basic fields */}
            {Object.keys(fieldLabels).map(field => (
              <div key={field} className="group">
                <label className="block text-sm font-semibold text-gray-700 mb-1">
                  {fieldLabels[field]}
                </label>
                <input
                  type={field === 'email' ? 'email' : 'text'}
                  value={formData[field]}
                  onChange={(e) => handleInputChange(field, e.target.value)}
                  className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
                  required={field === 'email' || field === 'username'}
                />
              </div>
            ))}

            {/* Profile image upload */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Photo de profil
              </label>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => setProfileImage(e.target.files[0])}
                className="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
              />
            </div>

            {/* Dynamic questions */}
            <div className="mt-10">
              <h3 className="text-xl font-bold text-gray-900 mb-6">Questions additionnelles</h3>

              {profileData.dynamicQuestions.map((question, index) => (
                <div key={index} className="mt-5 group">
                  <label className="block text-sm font-semibold text-gray-700 mb-1">
                    {question}
                  </label>
                  <textarea
                    value={userQuestions[index] || ''}
                    onChange={(e) => handleQuestionChange(index, e.target.value)}
                    className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
                    rows="3"
                  />
                </div>
              ))}

              {/* Taggable questions */}
              {profileData.taggableQuestions.map((question, index) => (
                <div key={index} className="mt-5 group">
                  <label className="block text-sm font-semibold text-gray-700 mb-1">
                    {question}
                  </label>
                  <div className="relative w-full">
                    <input
                      type="text"
                      value={tagSearchQueries[index]}
                      onChange={(e) => handleTagSearchChange(index, e.target.value)}
                      onFocus={() => handleTagSearchChange(index, tagSearchQueries[index])}
                      placeholder="Rechercher un mot-clé..."
                      className="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
                      autoComplete="off"
                    />
                    {showSuggestions[index] && tagSuggestions[index].length > 0 && (
                      <div className="absolute left-0 right-0 bg-white border border-gray-200 rounded shadow z-10 mt-1 max-h-40 overflow-y-auto">
                        {tagSuggestions[index].map(tag => (
                          <div
                            key={tag.id}
                            onClick={() => addTag(index, tag)}
                            className="px-4 py-2 hover:bg-blue-100 cursor-pointer"
                          >
                            {tag.name}
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                  <div className="selected-tags mt-2 flex flex-wrap gap-2">
                    {taggableQuestions[index]?.map(tag => (
                      <span
                        key={tag.id}
                        className="bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1 rounded-full flex items-center gap-1"
                      >
                        {tag.name}
                        <button
                          type="button"
                          onClick={() => removeTag(index, tag.id)}
                          className="text-indigo-600 font-bold hover:text-red-500"
                        >
                          &times;
                        </button>
                      </span>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="mt-8">
            <button
              type="submit"
              disabled={updateMutation.isPending}
              className="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform transition-all hover:-translate-y-0.5 disabled:opacity-50"
            >
              {updateMutation.isPending ? 'Enregistrement...' : 'Sauvegarder les modifications'}
            </button>
          </div>
        </form>

        <div className="mt-4 text-center">
          <button
            onClick={() => navigate(`/profile/${user?.username}`)}
            className="text-indigo-600 hover:underline"
          >
            Retour au profil
          </button>
        </div>
      </div>
    </div>
  )
}
