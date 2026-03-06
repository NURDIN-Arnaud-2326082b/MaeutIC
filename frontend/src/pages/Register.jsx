import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '../services/apis'

const TAGGABLE_QUESTIONS = [
  'Quels mot-clés peuvent être reliés à votre projet en cours ?',
  'Si vous deviez choisir 5 mots pour vous définir en tant que chercheur (se); quels seraient-ils?'
]

const MIN_TAGS_REQUIRED = [2, 5]

const DYNAMIC_QUESTIONS = [
  'Pourquoi cette thématique de recherche vous intéresse-t-elle ?',
  'Pourquoi avez-vous souhaité être chercheur ?',
  'Qu\'aimez vous dans la recherche ?',
  'Quels sont les problèmes de recherche auxquels vous vous intéressez ?',
  'Quelles sont les méthodologies de recherche que vous utilisez dans votre domaine d\'étude ?',
  'Qu\'est ce qui, d\'après vous, vous a amené(e) à faire de la recherche ?',
  'Comment vous définirirez vous en tant que chercheur?',
  'Pensez-vous que ce choix ait un lien avec un évènement de votre biographie ? (rencontre, auteur, environnement personnel, professionnel ....) et si oui pouvez-vous brièvement le/la décrire ?',
  'Pouvez-vous nous raconter qu\'est ce qui a motivé le choix de vos thématiques de recherche ?',
  'Comment vos expériences personnelles ont-elles influencé votre choix de carrière et vos recherches en sciences humaines et sociales ?',
  'En quelques mots, en tant que chercheur(se) qu\'est ce qui vous anime ?',
  'Si vous deviez choisir 4 auteurs qui vous ont marquée, quels seraient-ils?',
  'Quelle est la phrase ou la citation qui vous représente le mieux ?'
]

const MIN_QUESTIONS_REQUIRED = 3

export default function Register() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    email: '',
    lastName: '',
    firstName: '',
    username: '',
    genre: '',
    profileImage: null,
    plainPassword: '',
    affiliationLocation: '',
    specialization: '',
    researchTopic: '',
    taggableQuestions: [[], []],
    userQuestions: ['', '', '', '', '', '', '', '', '', '', '', '', '']
  })

  const [passwordRequirements, setPasswordRequirements] = useState({
    length: false,
    uppercase: false,
    lowercase: false,
    number: false,
    special: false
  })

  const [errors, setErrors] = useState({})
  const [tagSearches, setTagSearches] = useState(['', ''])

  const registerMutation = useMutation({
    mutationFn: (userData) => authApi.register(userData),
    onSuccess: () => {
      navigate('/login')
    },
    onError: (error) => {
      console.error('Registration error:', error)
      console.error('Response data:', error.response?.data)
      setErrors({ general: error.response?.data?.message || 'Erreur lors de l\'inscription' })
    },
  })

  // Validation du mot de passe en temps réel
  useEffect(() => {
    const password = formData.plainPassword
    setPasswordRequirements({
      length: password.length >= 6,
      uppercase: /[A-Z]/.test(password),
      lowercase: /[a-z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    })
  }, [formData.plainPassword])

  const answeredQuestionsCount = formData.userQuestions.filter(q => q.trim() !== '').length

  const handleSubmit = (e) => {
    e.preventDefault()
    setErrors({})

    // Validation du mot de passe
    const allRequirementsMet = Object.values(passwordRequirements).every(req => req)
    if (!allRequirementsMet) {
      setErrors({ password: 'Le mot de passe ne respecte pas les exigences de sécurité' })
      return
    }

    // Validation des tags (temporairement désactivée pour test)
    // for (let i = 0; i < TAGGABLE_QUESTIONS.length; i++) {
    //   if (formData.taggableQuestions[i].length < MIN_TAGS_REQUIRED[i]) {
    //     setErrors({ [`taggable_${i}`]: `Veuillez sélectionner au moins ${MIN_TAGS_REQUIRED[i]} tag(s)` })
    //     return
    //   }
    // }

    // Validation des questions dynamiques (temporairement désactivée pour test)
    // if (answeredQuestionsCount < MIN_QUESTIONS_REQUIRED) {
    //   setErrors({ questions: `Vous devez répondre à au moins ${MIN_QUESTIONS_REQUIRED} questions` })
    //   return
    // }

    const formDataToSend = new FormData()
    formDataToSend.append('email', formData.email)
    formDataToSend.append('lastName', formData.lastName)
    formDataToSend.append('firstName', formData.firstName)
    formDataToSend.append('username', formData.username)
    formDataToSend.append('genre', formData.genre)
    if (formData.profileImage) {
      formDataToSend.append('profileImage', formData.profileImage)
    }
    formDataToSend.append('plainPassword', formData.plainPassword)
    formDataToSend.append('affiliationLocation', formData.affiliationLocation)
    formDataToSend.append('specialization', formData.specialization)
    formDataToSend.append('researchTopic', formData.researchTopic)
    formDataToSend.append('taggableQuestions', JSON.stringify(formData.taggableQuestions))
    formDataToSend.append('userQuestions', JSON.stringify(formData.userQuestions))

    console.log('FormData contents:')
    for (let pair of formDataToSend.entries()) {
      console.log(pair[0] + ': ' + pair[1])
    }

    registerMutation.mutate(formDataToSend)
  }

  const addTag = (questionIndex, tag) => {
    const newTaggableQuestions = [...formData.taggableQuestions]
    if (!newTaggableQuestions[questionIndex].includes(tag)) {
      newTaggableQuestions[questionIndex].push(tag)
      setFormData({ ...formData, taggableQuestions: newTaggableQuestions })
    }
    const newTagSearches = [...tagSearches]
    newTagSearches[questionIndex] = ''
    setTagSearches(newTagSearches)
  }

  const removeTag = (questionIndex, tagToRemove) => {
    const newTaggableQuestions = [...formData.taggableQuestions]
    newTaggableQuestions[questionIndex] = newTaggableQuestions[questionIndex].filter(tag => tag !== tagToRemove)
    setFormData({ ...formData, taggableQuestions: newTaggableQuestions })
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-xl w-full mx-auto space-y-8">
        <div>
          <h1 className="mt-6 text-center text-4xl font-black text-gray-900 tracking-tight">
            Créer son compte
          </h1>
          <p className="mt-2 text-center text-gray-600">
            Rejoignez notre communauté de chercheur·euse·s !
          </p>
        </div>

        {errors.general && (
          <div className="bg-red-50 text-red-600 p-3 rounded-lg">
            {errors.general}
          </div>
        )}

        <form 
          onSubmit={handleSubmit} 
          className="mt-8 space-y-6 bg-white/50 backdrop-blur-sm p-8 rounded-xl shadow-xl border border-white/20"
          encType="multipart/form-data"
        >
          <div className="space-y-5">
            {/* Email */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Adresse e-mail <span className="text-red-500">*</span>
              </label>
              <input
                type="email"
                required
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
            </div>

            {/* Nom */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Nom <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                required
                value={formData.lastName}
                onChange={(e) => setFormData({ ...formData, lastName: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
            </div>

            {/* Prénom */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Prénom <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                required
                value={formData.firstName}
                onChange={(e) => setFormData({ ...formData, firstName: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
            </div>

            {/* Username */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Nom d'utilisateur <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                required
                value={formData.username}
                onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
            </div>

            {/* Genre */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Genre <span className="text-red-500">*</span>
              </label>
              <select
                required
                value={formData.genre}
                onChange={(e) => setFormData({ ...formData, genre: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              >
                <option value="">Sélectionnez...</option>
                <option value="homme">Homme</option>
                <option value="femme">Femme</option>
                <option value="autre">Autre</option>
                <option value="ne_pas_repondre">Ne pas répondre</option>
              </select>
            </div>

            {/* Photo de profil */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Photo de profil
              </label>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => setFormData({ ...formData, profileImage: e.target.files[0] })}
                className="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
              />
            </div>

            {/* Mot de passe */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Mot de passe <span className="text-red-500">*</span>
              </label>
              <input
                type="password"
                required
                value={formData.plainPassword}
                onChange={(e) => setFormData({ ...formData, plainPassword: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
              {errors.password && (
                <div className="text-red-600 text-sm mt-1">{errors.password}</div>
              )}
              <div id="password-requirements" className="mt-2 text-sm">
                <div className={passwordRequirements.length ? 'text-green-600' : 'text-gray-600'}>
                  {passwordRequirements.length ? '✓' : '○'} Au moins 6 caractères
                </div>
                <div className={passwordRequirements.uppercase ? 'text-green-600' : 'text-gray-600'}>
                  {passwordRequirements.uppercase ? '✓' : '○'} Une lettre majuscule
                </div>
                <div className={passwordRequirements.lowercase ? 'text-green-600' : 'text-gray-600'}>
                  {passwordRequirements.lowercase ? '✓' : '○'} Une lettre minuscule
                </div>
                <div className={passwordRequirements.number ? 'text-green-600' : 'text-gray-600'}>
                  {passwordRequirements.number ? '✓' : '○'} Un chiffre
                </div>
                <div className={passwordRequirements.special ? 'text-green-600' : 'text-gray-600'}>
                  {passwordRequirements.special ? '✓' : '○'} Un caractère spécial
                </div>
              </div>
            </div>

            {/* Lieu d'affiliation */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Lieu d'affiliation
              </label>
              <input
                type="text"
                value={formData.affiliationLocation}
                onChange={(e) => setFormData({ ...formData, affiliationLocation: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
            </div>

            {/* Spécialisation */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Spécialisation
              </label>
              <input
                type="text"
                value={formData.specialization}
                onChange={(e) => setFormData({ ...formData, specialization: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
            </div>

            {/* Sujet de recherche */}
            <div className="group">
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Sujet de recherche
              </label>
              <input
                type="text"
                value={formData.researchTopic}
                onChange={(e) => setFormData({ ...formData, researchTopic: e.target.value })}
                className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300"
              />
            </div>

            {/* Questions avec tags */}
            {TAGGABLE_QUESTIONS.map((question, index) => (
              <div key={index} className="taggable-question mt-5 group">
                <label className="block text-sm font-semibold text-gray-700 mb-1">
                  {question}
                  <span className="text-red-500">*</span>
                  <span className="text-sm text-gray-500"> (minimum {MIN_TAGS_REQUIRED[index]} tag(s))</span>
                </label>
                <div className="relative w-full">
                  <input
                    type="text"
                    value={tagSearches[index]}
                    onChange={(e) => {
                      const newSearches = [...tagSearches]
                      newSearches[index] = e.target.value
                      setTagSearches(newSearches)
                    }}
                    onKeyPress={(e) => {
                      if (e.key === 'Enter') {
                        e.preventDefault()
                        if (tagSearches[index].trim()) {
                          addTag(index, tagSearches[index].trim())
                        }
                      }
                    }}
                    className="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
                    placeholder="Rechercher un mot-clé..."
                    autoComplete="off"
                  />
                </div>
                <div className="selected-tags mt-2 flex flex-wrap gap-2">
                  {formData.taggableQuestions[index].map((tag, tagIndex) => (
                    <span
                      key={tagIndex}
                      className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
                    >
                      {tag}
                      <button
                        type="button"
                        onClick={() => removeTag(index, tag)}
                        className="ml-2 text-indigo-600 hover:text-indigo-800"
                      >
                        ×
                      </button>
                    </span>
                  ))}
                </div>
                {errors[`taggable_${index}`] && (
                  <div className="text-red-600 text-sm mt-1">{errors[`taggable_${index}`]}</div>
                )}
              </div>
            ))}

            {/* Questions dynamiques */}
            <div className="mt-10">
              <h3 className="text-xl font-bold text-gray-900 mb-6">Questions additionnelles</h3>
              <p className="text-sm text-gray-600 mb-4">
                Veuillez répondre à au moins <strong>{MIN_QUESTIONS_REQUIRED} questions</strong> de votre choix :
              </p>

              {DYNAMIC_QUESTIONS.map((question, index) => (
                <div key={index} className="mt-5 group">
                  <label className="block text-sm font-semibold text-gray-700 mb-1">
                    {question}
                  </label>
                  <textarea
                    value={formData.userQuestions[index]}
                    onChange={(e) => {
                      const newQuestions = [...formData.userQuestions]
                      newQuestions[index] = e.target.value
                      setFormData({ ...formData, userQuestions: newQuestions })
                    }}
                    rows={3}
                    className="appearance-none block w-full px-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 ease-in-out hover:border-indigo-300 question-field"
                  />
                </div>
              ))}

              {/* Compteur de questions */}
              {answeredQuestionsCount > 0 && (
                <div className={`mt-4 p-3 rounded-lg ${answeredQuestionsCount >= MIN_QUESTIONS_REQUIRED ? 'bg-green-50' : 'bg-blue-50'}`}>
                  <p className={`text-sm ${answeredQuestionsCount >= MIN_QUESTIONS_REQUIRED ? 'text-green-700' : 'text-blue-700'}`}>
                    <span className={answeredQuestionsCount >= MIN_QUESTIONS_REQUIRED ? 'text-green-700' : ''}>
                      {answeredQuestionsCount}
                    </span> sur {MIN_QUESTIONS_REQUIRED} questions répondues
                  </p>
                </div>
              )}

              {errors.questions && (
                <div className="mt-4 p-3 bg-red-50 rounded-lg">
                  <p className="text-sm text-red-700">{errors.questions}</p>
                </div>
              )}
            </div>
          </div>

          {/* Submit button */}
          <div className="mt-8">
            <button
              type="submit"
              disabled={registerMutation.isPending}
              className="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform transition-all hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
            >
              {registerMutation.isPending ? 'Création du compte...' : 'Créer mon compte !'}
            </button>
          </div>
        </form>

        <p className="mt-6 text-center text-sm text-gray-600">
          Déjà un compte ?{' '}
          <Link to="/login" className="text-blue-600 hover:underline">
            Se connecter
          </Link>
        </p>
      </div>
    </div>
  )
}
