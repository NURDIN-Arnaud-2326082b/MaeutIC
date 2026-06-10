import {useEffect, useState} from 'react'
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query'
import {
    autoActionReport,
    banUser,
    changeUserRole,
    createTag,
    deleteTag,
    getBannedUsers,
    getDataAccessRequestData,
    getDataAccessRequests,
    getFullProfileForEdit,
    getReports,
    getSensitivePosts,
    getTags,
    processDataAccessRequest,
    processReport,
    searchUsers,
    unbanUser,
    updateAnyUserProfile,
    updateTag
} from '../services/adminApi'
import {useAuthStore} from '../store'
import {Link, useNavigate} from 'react-router-dom'
import api from '../services/api'

// --- COMPOSANT MODALE : ÉDITION DE PROFIL UTILISATEUR PAR L'ADMIN ---
function UserEditModal({userId, onClose}) {
    const queryClient = useQueryClient()

    const [formData, setFormData] = useState({
        email: '', lastName: '', firstName: '', username: '',
        affiliationLocation: '', specialization: '', researchTopic: ''
    })
    const [mandatoryQuestionsAnswers, setMandatoryQuestionsAnswers] = useState(['', '', ''])
    const [userQuestions, setUserQuestions] = useState({})
    const [taggableQuestions, setTaggableQuestions] = useState([[], []])
    const [profileImage, setProfileImage] = useState(null)

    const [tagSearchQueries, setTagSearchQueries] = useState(['', ''])
    const [tagSuggestions, setTagSuggestions] = useState([[], []])
    const [showSuggestions, setShowSuggestions] = useState([false, false])

    const {data: profileData, isLoading} = useQuery({
        queryKey: ['admin-user-edit', userId],
        queryFn: () => getFullProfileForEdit(userId),
        enabled: !!userId
    })

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
            setMandatoryQuestionsAnswers(profileData.mandatoryQuestionsAnswers || ['', '', ''])
            setUserQuestions(profileData.userQuestionsAnswers || {})
            setTaggableQuestions(profileData.taggableQuestionsAnswers || [[], []])
        }
    }, [profileData])

    useEffect(() => {
        const timeouts = tagSearchQueries.map((query, index) => {
            if (query.length >= 2) {
                return setTimeout(() => {
                    api.get(`/tag/search?q=${encodeURIComponent(query)}`)
                        .then(response => {
                            const tags = response.data
                            const newSuggestions = [...tagSuggestions]
                            newSuggestions[index] = tags.filter(tag => !taggableQuestions[index].some(t => t.id === tag.id))
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
    }, [tagSearchQueries, taggableQuestions])

    const updateMutation = useMutation({
        mutationFn: async () => {
            const formDataToSend = new FormData()
            const jsonData = {
                email: formData.email, lastName: formData.lastName, firstName: formData.firstName,
                username: formData.username, affiliationLocation: formData.affiliationLocation,
                specialization: formData.specialization, researchTopic: formData.researchTopic,
                mandatoryQuestions: mandatoryQuestionsAnswers, userQuestions: userQuestions,
                taggableQuestions: taggableQuestions.map(tags => tags.map(t => t.id))
            }
            formDataToSend.append('data', JSON.stringify(jsonData))
            if (profileImage) {
                formDataToSend.append('profileImage', profileImage)
            }
            return updateAnyUserProfile(userId, formDataToSend)
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['admin-search-users'])
            queryClient.invalidateQueries(['admin-banned-users'])
            alert('Profil mis à jour avec succès')
            onClose()
        },
        onError: (error) => alert(error.response?.data?.error || 'Erreur lors de la mise à jour')
    })

    const handleSubmit = (e) => {
        e.preventDefault()
        updateMutation.mutate()
    }

    const handleInputChange = (field, value) => setFormData(prev => ({...prev, [field]: value}))
    const handleMandatoryQuestionChange = (index, value) => {
        const newAnswers = [...mandatoryQuestionsAnswers];
        newAnswers[index] = value;
        setMandatoryQuestionsAnswers(newAnswers)
    }
    const handleQuestionChange = (index, value) => setUserQuestions(prev => ({...prev, [index]: value}))
    const handleTagSearchChange = (index, value) => {
        const newQueries = [...tagSearchQueries];
        newQueries[index] = value;
        setTagSearchQueries(newQueries)
    }
    const addTag = (questionIndex, tag) => {
        const newTaggable = [...taggableQuestions]
        if (!newTaggable[questionIndex].some(t => t.id === tag.id)) {
            newTaggable[questionIndex] = [...newTaggable[questionIndex], tag]
            setTaggableQuestions(newTaggable)
        }
        const newQueries = [...tagSearchQueries];
        newQueries[questionIndex] = '';
        setTagSearchQueries(newQueries)
        const newShow = [...showSuggestions];
        newShow[questionIndex] = false;
        setShowSuggestions(newShow)
    }
    const removeTag = (questionIndex, tagId) => {
        const newTaggable = [...taggableQuestions];
        newTaggable[questionIndex] = newTaggable[questionIndex].filter(t => t.id !== tagId);
        setTaggableQuestions(newTaggable)
    }

    const renderTaggableQuestion = (index, label) => (
        <div className="mt-5 group relative">
            <label className="block text-sm font-semibold text-gray-700 mb-1">{label}</label>
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
                <div
                    className="absolute left-0 right-0 bg-white border border-gray-200 rounded shadow z-10 mt-1 max-h-40 overflow-y-auto">
                    {tagSuggestions[index].map(tag => (
                        <div key={tag.id} onClick={() => addTag(index, tag)}
                             className="px-4 py-2 hover:bg-blue-100 cursor-pointer">{tag.name}</div>
                    ))}
                </div>
            )}
            <div className="selected-tags mt-2 flex flex-wrap gap-2">
                {taggableQuestions[index]?.map(tag => (
                    <span key={tag.id}
                          className="bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1 rounded-full flex items-center gap-1">
            {tag.name}
                        <button type="button" onClick={() => removeTag(index, tag.id)}
                                className="text-indigo-600 font-bold hover:text-red-500">&times;</button>
          </span>
                ))}
            </div>
        </div>
    )

    if (isLoading) return <div className="p-8 text-center text-gray-500">Chargement du profil...</div>

    return (
        <div className="fixed inset-0 bg-black/60 z-40 overflow-y-auto pt-28 pb-10 px-4 flex justify-center items-start"
             onClick={onClose}>
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-4xl flex flex-col mb-auto"
                 onClick={(e) => e.stopPropagation()}>
                <div className="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h2 className="text-xl font-bold text-gray-800">Édition du profil :
                        @{profileData?.user?.username}</h2>
                    <button onClick={onClose}
                            className="text-gray-500 hover:text-red-600 text-3xl leading-none">&times;</button>
                </div>

                <div className="p-6 bg-gray-50/50">
                    <form id="admin-edit-form" onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label className="block text-sm font-semibold text-gray-700 mb-1">Prénom</label><input
                                type="text" value={formData.firstName}
                                onChange={(e) => handleInputChange('firstName', e.target.value)}
                                className="w-full px-3 py-2 border rounded" required/></div>
                            <div><label className="block text-sm font-semibold text-gray-700 mb-1">Nom</label><input
                                type="text" value={formData.lastName}
                                onChange={(e) => handleInputChange('lastName', e.target.value)}
                                className="w-full px-3 py-2 border rounded" required/></div>
                            <div><label className="block text-sm font-semibold text-gray-700 mb-1">Pseudo</label><input
                                type="text" value={formData.username}
                                onChange={(e) => handleInputChange('username', e.target.value)}
                                className="w-full px-3 py-2 border rounded" required/></div>
                            <div><label className="block text-sm font-semibold text-gray-700 mb-1">Email</label><input
                                type="email" value={formData.email}
                                onChange={(e) => handleInputChange('email', e.target.value)}
                                className="w-full px-3 py-2 border rounded" required/></div>
                            <div className="md:col-span-2"><label
                                className="block text-sm font-semibold text-gray-700 mb-1">Lieu
                                d'affiliation</label><input type="text" value={formData.affiliationLocation}
                                                            onChange={(e) => handleInputChange('affiliationLocation', e.target.value)}
                                                            className="w-full px-3 py-2 border rounded"/></div>
                            <div className="md:col-span-2"><label
                                className="block text-sm font-semibold text-gray-700 mb-1">Spécialisation</label><input
                                type="text" value={formData.specialization}
                                onChange={(e) => handleInputChange('specialization', e.target.value)}
                                className="w-full px-3 py-2 border rounded"/></div>
                        </div>

                        <div className="border-t pt-4">
                            <label className="block text-sm font-semibold text-gray-700 mb-1">Remplacer la photo de
                                profil</label>
                            <input type="file" accept="image/*" onChange={(e) => setProfileImage(e.target.files[0])}
                                   className="text-sm"/>
                        </div>

                        <div className="border-t pt-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-4">Profil de chercheur (Questions
                                Obligatoires)</h3>
                            <div className="space-y-4">
                                {['Quelles sont les méthodologies de recherche que vous utilisez ?', 'Si vous deviez choisir 4 auteurs qui vous ont marquée ?', 'Quelle est la phrase ou la citation qui vous représente le mieux ?'].map((question, index) => (
                                    <div key={index}>
                                        <label
                                            className="block text-sm font-semibold text-gray-700 mb-1">{question}</label>
                                        <textarea value={mandatoryQuestionsAnswers[index] || ''}
                                                  onChange={(e) => handleMandatoryQuestionChange(index, e.target.value)}
                                                  className="w-full px-3 py-2 border rounded" rows="2"/>
                                    </div>
                                ))}
                                {renderTaggableQuestion(0, 'Quels mot-clés peuvent être reliés à votre projet en cours ?')}
                            </div>
                        </div>

                        <div className="border-t pt-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-4">Questions Additionnelles
                                (Optionnelles)</h3>
                            {renderTaggableQuestion(1, 'Si vous deviez choisir 5 mots pour vous définir en tant que chercheur(se) ?')}
                            <div className="space-y-4 mt-4">
                                {['Pourquoi cette thématique de recherche vous intéresse-t-elle ?', 'Pourquoi avez-vous souhaité être chercheur ?', 'Qu\'aimez-vous dans la recherche ?', 'Quels sont les problèmes auxquels vous vous intéressez ?'].map((q, i) => (
                                    <div key={i}>
                                        <label className="block text-sm font-semibold text-gray-700 mb-1">{q}</label>
                                        <textarea value={userQuestions[i] || ''}
                                                  onChange={(e) => handleQuestionChange(i, e.target.value)}
                                                  className="w-full px-3 py-2 border rounded" rows="2"/>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </form>
                </div>

                <div className="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                    <button type="button" onClick={onClose}
                            className="px-5 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 font-medium">
                        Annuler
                    </button>
                    <button type="submit" form="admin-edit-form" disabled={updateMutation.isPending}
                            className="px-5 py-2 text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50 font-medium">
                        {updateMutation.isPending ? 'Sauvegarde...' : 'Sauvegarder les modifications'}
                    </button>
                </div>
            </div>
        </div>
    )
}

// --- COMPOSANT PANEL D'ADMINISTRATION PRINCIPAL ---
export default function AdminInterface() {
    const {user} = useAuthStore()
    const navigate = useNavigate()
    const queryClient = useQueryClient()
    const [activeTab, setActiveTab] = useState('tags')
    const [searchQuery, setSearchQuery] = useState('')
    const [showCreateModal, setShowCreateModal] = useState(false)
    const [createTagName, setCreateTagName] = useState('')
    const [editingTagId, setEditingTagId] = useState(null)
    const [editTagName, setEditTagName] = useState('')
    const [userSearchQuery, setUserSearchQuery] = useState('')
    const [reportsStatusFilter, setReportsStatusFilter] = useState('')
    const [dataAccessStatusFilter, setDataAccessStatusFilter] = useState('')
    const [dataExportPreview, setDataExportPreview] = useState(null)
    const [editingUserId, setEditingUserId] = useState(null)

    useEffect(() => {
        if (user && user.userType !== 1) navigate('/')
    }, [user, navigate])

    // OPTIMISATION : Les requêtes ne s'exécutent que si l'onglet correspondant est actif
    const {data: tagsData, isLoading} = useQuery({
        queryKey: ['admin-tags', searchQuery],
        queryFn: () => getTags(searchQuery),
        enabled: user?.userType === 1 && activeTab === 'tags'
    })
    const {data: bannedData, isLoading: bannedLoading} = useQuery({
        queryKey: ['admin-banned-users'],
        queryFn: getBannedUsers,
        enabled: user?.userType === 1 && activeTab === 'users'
    })
    const {
        data: searchUsersData,
        isLoading: searchUsersLoading
    } = useQuery({
        queryKey: ['admin-search-users', userSearchQuery],
        queryFn: () => searchUsers(userSearchQuery),
        enabled: user?.userType === 1 && activeTab === 'users' && userSearchQuery.trim().length > 0
    })
    const {data: reportsData, isLoading: reportsLoading} = useQuery({
        queryKey: ['admin-reports', reportsStatusFilter],
        queryFn: () => getReports(reportsStatusFilter),
        enabled: user?.userType === 1 && activeTab === 'reports'
    })
    const {data: sensitivePostsData, isLoading: sensitivePostsLoading} = useQuery({
        queryKey: ['admin-sensitive-posts'],
        queryFn: () => getSensitivePosts(),
        enabled: user?.userType === 1 && activeTab === 'sensitive'
    })
    const {
        data: dataAccessRequestsData,
        isLoading: dataAccessRequestsLoading
    } = useQuery({
        queryKey: ['admin-data-access-requests', dataAccessStatusFilter],
        queryFn: () => getDataAccessRequests(dataAccessStatusFilter),
        enabled: user?.userType === 1 && activeTab === 'gdpr'
    })

    // --- Mutations ---
    const createMutation = useMutation({
        mutationFn: createTag, onSuccess: () => {
            queryClient.invalidateQueries(['admin-tags']);
            setShowCreateModal(false);
            setCreateTagName('')
        }, onError: (error) => alert(error.response?.data?.error || 'Erreur lors de la création')
    })
    const updateMutation = useMutation({
        mutationFn: ({id, name}) => updateTag(id, name), onSuccess: () => {
            queryClient.invalidateQueries(['admin-tags']);
            setEditingTagId(null);
            setEditTagName('')
        }, onError: (error) => alert(error.response?.data?.error || 'Erreur lors de la modification')
    })
    const deleteMutation = useMutation({
        mutationFn: deleteTag,
        onSuccess: () => queryClient.invalidateQueries(['admin-tags']),
        onError: (error) => alert(error.response?.data?.error || 'Erreur lors de la suppression')
    })
    const banUserMutation = useMutation({
        mutationFn: banUser, onSuccess: () => {
            queryClient.invalidateQueries(['admin-banned-users']);
            alert('Utilisateur banni avec succès')
        }, onError: (error) => alert(error.response?.data?.error || 'Erreur')
    })
    const unbanUserMutation = useMutation({
        mutationFn: unbanUser, onSuccess: () => {
            queryClient.invalidateQueries(['admin-banned-users']);
            alert('Utilisateur débanni avec succès')
        }, onError: (error) => alert(error.response?.data?.error || 'Erreur')
    })
    const processReportMutation = useMutation({
        mutationFn: ({
                         id,
                         status,
                         adminNote
                     }) => processReport(id, status, adminNote),
        onSuccess: () => {
            queryClient.invalidateQueries(['admin-reports']);
            alert('Signalement mis à jour')
        },
        onError: (error) => alert(error.response?.data?.error || 'Erreur')
    })
    const autoActionMutation = useMutation({
        mutationFn: ({
                         id,
                         action,
                         adminNote
                     }) => autoActionReport(id, action, adminNote),
        onSuccess: () => {
            queryClient.invalidateQueries(['admin-reports']);
            queryClient.invalidateQueries(['admin-banned-users']);
            alert('Action automatique appliquée')
        },
        onError: (error) => alert(error.response?.data?.error || 'Erreur')
    })
    const processDataAccessMutation = useMutation({
        mutationFn: ({
                         id,
                         status,
                         adminNote
                     }) => processDataAccessRequest(id, status, adminNote),
        onSuccess: () => {
            queryClient.invalidateQueries(['admin-data-access-requests']);
            alert('Demande RGPD mise à jour')
        },
        onError: (error) => alert(error.response?.data?.error || 'Erreur')
    })
    const changeRoleMutation = useMutation({
        mutationFn: ({id, userType}) => changeUserRole(id, userType),
        onSuccess: () => {
            queryClient.invalidateQueries(['admin-search-users']);
            alert('Rôle mis à jour avec succès')
        },
        onError: (error) => alert(error.response?.data?.error || 'Erreur')
    })

    // --- Handlers ---
    const handleCreateSubmit = (e) => {
        e.preventDefault();
        if (createTagName.trim()) createMutation.mutate(createTagName.trim())
    }
    const handleEditSubmit = (e, id) => {
        e.preventDefault();
        if (editTagName.trim()) updateMutation.mutate({id, name: editTagName.trim()})
    }
    const handleDelete = (id) => {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce tag ?')) deleteMutation.mutate(id)
    }
    const openEditForm = (id, name) => {
        setEditingTagId(id);
        setEditTagName(name)
    }
    const closeEditForm = () => {
        setEditingTagId(null);
        setEditTagName('')
    }
    const handleBan = (userId) => {
        if (confirm('Êtes-vous sûr de vouloir bannir cet utilisateur ?')) banUserMutation.mutate(userId)
    }
    const handleUnban = (userId) => {
        if (confirm('Êtes-vous sûr de vouloir débannir cet utilisateur ?')) unbanUserMutation.mutate(userId)
    }
    const handleProcessReport = (reportId, status) => {
        const adminNote = globalThis.prompt('Note admin (optionnel)') || '';
        processReportMutation.mutate({id: reportId, status, adminNote})
    }

    const handleAutoAction = (report, action) => {
        const text = action === 'delete_target' ? 'Confirmer la suppression du contenu ?' : 'Confirmer le bannissement de l\'auteur ?'
        if (!globalThis.confirm(text)) return
        const adminNote = globalThis.prompt('Note admin (optionnel)') || ''
        autoActionMutation.mutate({id: report.id, action, adminNote})
    }

    const handleProcessDataAccessRequest = (requestId, status) => {
        const adminNote = globalThis.prompt('Note admin (optionnel)') || ''
        processDataAccessMutation.mutate({id: requestId, status, adminNote})
    }

    const handlePreviewDataExport = async (requestId) => {
        try {
            const payload = await getDataAccessRequestData(requestId);
            setDataExportPreview(payload)
        } catch (error) {
            alert(error.response?.data?.error || 'Impossible de récupérer les données JSON')
        }
    }

    const handleRoleChange = (userId, currentUserType) => {
        const newRole = currentUserType === 1 ? 0 : 1
        const msg = newRole === 1 ? 'Promouvoir cet utilisateur en Administrateur ?' : 'Retirer les droits d\'administration ?'
        if (confirm(msg)) changeRoleMutation.mutate({id: userId, userType: newRole})
    }

    const tags = tagsData?.tags || []
    const bannedUsers = bannedData?.bannedUsers || []
    const reports = reportsData?.reports || []
    const sensitivePosts = sensitivePostsData?.posts || []
    const dataAccessRequests = dataAccessRequestsData?.requests || []

    const getReportedPostPath = (targetSummary) => {
        if (!targetSummary?.postId || !targetSummary?.forumCategory) return null
        const encodedCategory = encodeURIComponent(targetSummary.forumCategory)
        if (targetSummary.forumSpecial === 'methodology') return `/methodology-forums/${encodedCategory}/${targetSummary.postId}`
        if (targetSummary.forumSpecial === 'detente') return `/detente-forums/${encodedCategory}/${targetSummary.postId}`
        if (targetSummary.forumSpecial === 'administratif') return `/administratif-forums/${encodedCategory}/${targetSummary.postId}`
        return `/forums/${encodedCategory}/${targetSummary.postId}`
    }

    const getSensitivePostPath = (post) => post?.targetSummary ? getReportedPostPath(post.targetSummary) : null

    if (!user || user.userType !== 1) return null

    return (
        // 1. On applique le fond (si nécessaire) et le padding vertical (py-8) comme sur Settings
        <div className="flex-1 relative bg-blue-50 py-8">
            {editingUserId && <UserEditModal userId={editingUserId} onClose={() => setEditingUserId(null)}/>}

            {/* 2. On contraint la largeur (max-w-5xl) et on centre (mx-auto) le panneau principal */}
            <div className="max-w-5xl mx-auto bg-white/45 text-gray-700 backdrop-blur-sm shadow-xl p-6 rounded-lg">
                <h1 className="text-2xl font-bold mb-4">Panneau d'Administration</h1>

                {/* Tabs */}
                <div className="flex border-b border-gray-300 mb-6 overflow-x-auto whitespace-nowrap">
                    {['tags', 'users', 'reports', 'sensitive', 'gdpr'].map(tab => (
                        <button key={tab} onClick={() => setActiveTab(tab)}
                                className={`px-4 py-2 font-medium transition-colors ${activeTab === tab ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-gray-800'}`}>
                            {tab === 'tags' ? 'Gestion des Tags' : tab === 'users' ? 'Utilisateurs' : tab === 'reports' ? `Signalements (${reports.length})` : tab === 'sensitive' ? `Contenu sensible (${sensitivePosts.length})` : `Données RGPD (${dataAccessRequests.length})`}
                        </button>
                    ))}
                </div>

                {/* Tags Tab */}
                {activeTab === 'tags' && (
                    <div className="w-full">
                        <div className="flex flex-row items-center justify-between mb-4">
                            <input type="text" placeholder="Rechercher un tag" value={searchQuery}
                                   onChange={(e) => setSearchQuery(e.target.value)}
                                   className="w-full p-2 border border-gray-300 rounded max-w-md"/>
                            <button onClick={() => setShowCreateModal(true)}
                                    className="ml-4 text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 whitespace-nowrap">Ajouter
                                un nouveau tag
                            </button>
                        </div>
                        <div className="flex flex-col w-auto mb-4">
                            <div className="flex flex-row justify-between text-sm w-full px-4"><p>Nom</p><p>Action</p>
                            </div>
                            {isLoading ? <div className="text-center py-4">Chargement...</div> : tags.length > 0 ? (
                                tags.map((tag) => (
                                    <div key={tag.id}
                                         className="bg-white hover:bg-blue-50 rounded-lg mb-1 flex flex-col justify-between">
                                        <div className="flex flex-row justify-between">
                                            <p className="px-4 py-2">{tag.name}</p>
                                            <div className="px-4 py-2">
                                                <button onClick={() => openEditForm(tag.id, tag.name)}
                                                        className="text-blue-500 hover:underline mr-2">Modifier
                                                </button>
                                                <button onClick={() => handleDelete(tag.id)}
                                                        className="text-red-500 hover:underline">Supprimer
                                                </button>
                                            </div>
                                        </div>
                                        {editingTagId === tag.id && (
                                            <form onSubmit={(e) => handleEditSubmit(e, tag.id)} className="p-2">
                                                <input type="text" value={editTagName}
                                                       onChange={(e) => setEditTagName(e.target.value)}
                                                       className="w-full p-2 border border-gray-300 rounded mb-4"
                                                       autoFocus/>
                                                <div className="flex flex-row items-center">
                                                    <button type="submit" disabled={updateMutation.isPending}
                                                            className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-4 disabled:opacity-50">Enregistrer
                                                    </button>
                                                    <button type="button" onClick={closeEditForm}
                                                            className="text-gray-700 font-medium">Annuler
                                                    </button>
                                                </div>
                                            </form>
                                        )}
                                    </div>
                                ))
                            ) : <div className="text-center py-4">Aucun tag trouvé.</div>}
                        </div>
                    </div>
                )}

                {/* Users Tab */}
                {activeTab === 'users' && (
                    <div className="w-full">
                        <div className="mb-8 p-6 bg-white rounded-lg border border-gray-200 shadow-sm">
                            <h3 className="text-lg font-semibold mb-4 text-gray-800">Rechercher un utilisateur</h3>
                            <input type="text" placeholder="Rechercher par nom, email, ou pseudo..."
                                   value={userSearchQuery} onChange={(e) => setUserSearchQuery(e.target.value)}
                                   className="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400 focus:outline-none bg-gray-50 text-gray-900 mb-4"/>

                            {userSearchQuery.trim().length > 0 && (
                                <div className="mt-4">
                                    {searchUsersLoading ? <div className="text-gray-500 italic">Recherche en
                                        cours...</div> : (searchUsersData?.users || []).length === 0 ?
                                        <div className="text-gray-500">Aucun utilisateur trouvé.</div> : (
                                            <div className="space-y-3">
                                                {(searchUsersData?.users || []).map((searchUser) => {
                                                    const isBanned = bannedUsers.some((b) => b.id === searchUser.id)
                                                    const isMe = searchUser.id === user.id
                                                    return (
                                                        <div key={searchUser.id}
                                                             className={`flex items-center justify-between p-4 rounded-lg border ${isBanned ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'}`}>
                                                            <div className="flex items-center gap-4 flex-1">
                                                                <img
                                                                    src={searchUser.profileImage || '/images/default-profile.png'}
                                                                    alt="avatar"
                                                                    className="w-12 h-12 rounded-full object-cover shadow-sm"/>
                                                                <div>
                                                                    <div
                                                                        className="font-semibold text-gray-900 text-lg flex items-center gap-2">
                                                                        {searchUser.firstName} {searchUser.lastName}
                                                                        {isBanned && <span
                                                                            className="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded-full font-bold">BANNI</span>}
                                                                    </div>
                                                                    <div
                                                                        className="text-sm text-gray-600">@{searchUser.username} • {searchUser.email}</div>
                                                                </div>
                                                            </div>
                                                            <div className="flex gap-2">
                                                                <button onClick={() => setEditingUserId(searchUser.id)}
                                                                        className="px-3 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded font-medium text-sm transition-colors">✏️
                                                                    Éditer
                                                                </button>
                                                                <button
                                                                    onClick={() => handleRoleChange(searchUser.id, searchUser.userType)}
                                                                    disabled={changeRoleMutation.isPending || isMe}
                                                                    className={`px-3 py-2 rounded font-medium text-sm transition-colors ${isMe ? 'opacity-50 cursor-not-allowed ' : ''}${searchUser.userType === 1 ? 'bg-purple-100 text-purple-700 hover:bg-purple-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`}>
                                                                    {searchUser.userType === 1 ? '👑 Admin' : '👤 Membre'}
                                                                </button>
                                                                {!isBanned ? (
                                                                    <button onClick={() => handleBan(searchUser.id)}
                                                                            disabled={banUserMutation.isPending || isMe}
                                                                            className={`px-4 py-2 text-white rounded font-medium transition-colors ${isMe ? 'bg-gray-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700 disabled:opacity-50'}`}>Bannir</button>
                                                                ) : (
                                                                    <button onClick={() => handleUnban(searchUser.id)}
                                                                            disabled={unbanUserMutation.isPending || isMe}
                                                                            className={`px-4 py-2 text-white rounded font-medium transition-colors ${isMe ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 disabled:opacity-50'}`}>Débannir</button>
                                                                )}
                                                            </div>
                                                        </div>
                                                    )
                                                })}
                                            </div>
                                        )}
                                </div>
                            )}
                        </div>

                        <div className="bg-white rounded-lg border border-red-200 shadow-sm overflow-hidden">
                            <div className="bg-red-50 p-4 border-b border-red-200"><h3
                                className="text-lg font-semibold text-red-800">Comptes restreints
                                ({bannedUsers.length})</h3></div>
                            <div className="p-4">
                                {bannedLoading ?
                                    <div className="text-center py-4">Chargement...</div> : bannedUsers.length === 0 ?
                                        <div className="text-center py-4 text-gray-500 italic">Aucun utilisateur n'est
                                            actuellement banni.</div> : (
                                            <div className="space-y-3">
                                                {bannedUsers.map((bannedUser) => (
                                                    <div key={bannedUser.id}
                                                         className="flex items-center justify-between p-3 hover:bg-gray-50 rounded border border-transparent hover:border-gray-200 transition-colors">
                                                        <div className="flex items-center gap-3 flex-1">
                                                            <img
                                                                src={bannedUser.profileImage || '/images/default-profile.png'}
                                                                alt="avatar"
                                                                className="w-10 h-10 rounded-full object-cover"/>
                                                            <div className="flex-1">
                                                                <div
                                                                    className="font-medium text-gray-900">{bannedUser.firstName} {bannedUser.lastName}
                                                                    <span
                                                                        className="text-sm text-gray-500 font-normal">(@{bannedUser.username})</span>
                                                                </div>
                                                                <div
                                                                    className="text-xs text-gray-500">{bannedUser.email}</div>
                                                            </div>
                                                        </div>
                                                        <button onClick={() => setEditingUserId(bannedUser.id)}
                                                                className="px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200 transition-colors">Éditer
                                                        </button>
                                                        <button onClick={() => handleUnban(bannedUser.id)}
                                                                disabled={unbanUserMutation.isPending}
                                                                className="ml-2 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-100 rounded hover:bg-red-200 disabled:opacity-50 transition-colors">Lever
                                                            la sanction
                                                        </button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Reports Tab */}
                {activeTab === 'reports' && (
                    <div className="w-full">
                        <div className="flex items-center gap-3 mb-4">
                            <label htmlFor="reports-status-filter" className="text-sm font-medium text-gray-700">Filtrer
                                par statut :</label>
                            <select id="reports-status-filter" value={reportsStatusFilter}
                                    onChange={(e) => setReportsStatusFilter(e.target.value)}
                                    className="p-2 border border-gray-300 rounded">
                                <option value="">Tous</option>
                                <option value="pending">En attente</option>
                                <option value="reviewed">Traités</option>
                                <option value="rejected">Rejetés</option>
                            </select>
                        </div>
                        {reportsLoading ? <div className="text-center py-4">Chargement...</div> : reports.length === 0 ?
                            <div className="text-center py-4 text-gray-600">Aucun signalement.</div> : (
                                <div className="space-y-3">
                                    {reports.map((report) => (
                                        <div key={report.id} className="bg-white border rounded p-4">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex-1">
                                                    <div
                                                        className="text-sm text-gray-500 mb-1">#{report.id} • {report.targetType} #{report.targetId} • {new Date(report.createdAt).toLocaleString('fr-FR')}</div>
                                                    <div className="font-semibold text-gray-900">{report.reason}</div>
                                                    {report.details && <div
                                                        className="text-sm text-gray-700 mt-1">{report.details}</div>}
                                                    <div className="text-sm text-gray-600 mt-2">Signalé par
                                                        @{report.reporter?.username || 'inconnu'}</div>
                                                    <div className="text-sm text-gray-600 mt-1">
                                                        Cible :{' '}
                                                        {report.targetType === 'post' && report.targetSummary?.exists && getReportedPostPath(report.targetSummary) ? (
                                                            <Link to={getReportedPostPath(report.targetSummary)}
                                                                  className="text-blue-700 hover:text-blue-900 underline">{report.targetSummary?.label || 'Inconnue'}</Link>
                                                        ) : report.targetType === 'message' ? (
                                                            <span
                                                                className="block mt-1 whitespace-pre-wrap break-words text-gray-700">{report.targetSummary?.label || 'Inconnue'}</span>
                                                        ) : (report.targetSummary?.label || 'Inconnue')}
                                                        {report.targetSummary?.author ? ` (auteur: @${report.targetSummary.author})` : ''}
                                                    </div>
                                                    {report.adminNote &&
                                                        <div className="text-sm text-indigo-700 mt-2">Note admin
                                                            : {report.adminNote}</div>}
                                                </div>
                                                <div className="flex flex-col items-end gap-2 min-w-0 sm:min-w-[160px]">
                                                    <span
                                                        className={`px-2 py-1 rounded text-xs font-medium ${report.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : report.status === 'reviewed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'}`}>{report.status}</span>
                                                    {report.status === 'pending' && (
                                                        <>
                                                            <button
                                                                onClick={() => handleProcessReport(report.id, 'reviewed')}
                                                                disabled={processReportMutation.isPending}
                                                                className="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 disabled:opacity-50">Marquer
                                                                traité
                                                            </button>
                                                            <button
                                                                onClick={() => handleProcessReport(report.id, 'rejected')}
                                                                disabled={processReportMutation.isPending}
                                                                className="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 disabled:opacity-50">Rejeter
                                                            </button>
                                                            {['post', 'comment', 'message', 'article', 'resource'].includes(report.targetType) && (
                                                                <button
                                                                    onClick={() => handleAutoAction(report, 'delete_target')}
                                                                    disabled={autoActionMutation.isPending}
                                                                    className="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 disabled:opacity-50">Supprimer</button>
                                                            )}
                                                            <button
                                                                onClick={() => handleAutoAction(report, 'ban_author')}
                                                                disabled={autoActionMutation.isPending}
                                                                className="px-3 py-1 bg-amber-600 text-white rounded text-sm hover:bg-amber-700 disabled:opacity-50">Bannir
                                                                auteur
                                                            </button>
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                    </div>
                )}

                {/* Sensitive Content Tab */}
                {activeTab === 'sensitive' && (
                    <div className="w-full">
                        {sensitivePostsLoading ?
                            <div className="text-center py-4">Chargement...</div> : sensitivePosts.length === 0 ?
                                <div className="text-center py-4 text-gray-600">Aucun contenu sensible
                                    détecté.</div> : (
                                    <div className="space-y-3">
                                        {sensitivePosts.map((post) => {
                                            const postPath = getSensitivePostPath(post)
                                            return (
                                                <div key={post.id} className="bg-white border rounded p-4">
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div className="flex-1">
                                                            <div
                                                                className="text-sm text-gray-500 mb-1">#{post.id} • {post.forumSpecial || 'forum'} • {new Date(post.createdAt).toLocaleString('fr-FR')}</div>
                                                            <div className="font-semibold text-gray-900">{postPath ?
                                                                <Link to={postPath}
                                                                      className="text-blue-700 hover:text-blue-900 underline">{post.name}</Link> : post.name}</div>
                                                            <div
                                                                className="text-sm text-gray-700 mt-1 whitespace-pre-wrap break-words">{post.description}</div>
                                                            <div className="text-sm text-gray-600 mt-2">Auteur :
                                                                @{post.author || 'inconnu'}</div>
                                                            {post.sensitiveContentWarnings?.length > 0 && (
                                                                <div className="mt-3 space-y-1">
                                                                    {post.sensitiveContentWarnings.map((w, i) => <div
                                                                        key={i}
                                                                        className="rounded bg-yellow-50 border border-yellow-200 px-3 py-2 text-sm text-yellow-800">{w.message}</div>)}
                                                                </div>
                                                            )}
                                                        </div>
                                                        <div
                                                            className="flex flex-col items-end gap-2 min-w-0 sm:min-w-[160px]">
                                                            <span
                                                                className="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">signalé</span>
                                                            {postPath && <Link to={postPath}
                                                                               className="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Voir
                                                                le post</Link>}
                                                        </div>
                                                    </div>
                                                </div>
                                            )
                                        })}
                                    </div>
                                )}
                    </div>
                )}

                {/* GDPR Data Access Tab */}
                {activeTab === 'gdpr' && (
                    <div className="w-full">
                        <div className="flex items-center gap-3 mb-4">
                            <label htmlFor="gdpr-status-filter" className="text-sm font-medium text-gray-700">Filtrer
                                par statut :</label>
                            <select id="gdpr-status-filter" value={dataAccessStatusFilter}
                                    onChange={(e) => setDataAccessStatusFilter(e.target.value)}
                                    className="p-2 border border-gray-300 rounded">
                                <option value="">Tous</option>
                                <option value="pending">En attente</option>
                                <option value="processed">Traités</option>
                                <option value="rejected">Rejetés</option>
                            </select>
                        </div>
                        {dataAccessRequestsLoading ?
                            <div className="text-center py-4">Chargement...</div> : dataAccessRequests.length === 0 ?
                                <div className="text-center py-4 text-gray-600">Aucune demande RGPD.</div> : (
                                    <div className="space-y-3">
                                        {dataAccessRequests.map((req) => (
                                            <div key={req.id} className="bg-white border rounded p-4">
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="flex-1">
                                                        <div className="text-sm text-gray-500 mb-1">Demande
                                                            #{req.id} • {new Date(req.createdAt).toLocaleString('fr-FR')}</div>
                                                        <div
                                                            className="font-semibold text-gray-900">@{req.requester?.username || 'inconnu'} ({req.requester?.email || 'email inconnu'})
                                                        </div>
                                                        <div
                                                            className="text-sm text-gray-700 mt-1">{req.requester?.firstName} {req.requester?.lastName}</div>
                                                        {req.adminNote &&
                                                            <div className="text-sm text-indigo-700 mt-2">Note admin
                                                                : {req.adminNote}</div>}
                                                    </div>
                                                    <div
                                                        className="flex flex-col items-end gap-2 min-w-0 sm:min-w-[200px]">
                                                        <span
                                                            className={`px-2 py-1 rounded text-xs font-medium ${req.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : req.status === 'processed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'}`}>{req.status}</span>
                                                        <button onClick={() => handlePreviewDataExport(req.id)}
                                                                className="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Voir
                                                            JSON
                                                        </button>
                                                        {req.status === 'pending' && (
                                                            <>
                                                                <button
                                                                    onClick={() => handleProcessDataAccessRequest(req.id, 'processed')}
                                                                    disabled={processDataAccessMutation.isPending}
                                                                    className="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 disabled:opacity-50">Marquer
                                                                    traité
                                                                </button>
                                                                <button
                                                                    onClick={() => handleProcessDataAccessRequest(req.id, 'rejected')}
                                                                    disabled={processDataAccessMutation.isPending}
                                                                    className="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 disabled:opacity-50">Rejeter
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                        {dataExportPreview && (
                            <div className="mt-6 rounded border border-gray-300 bg-white p-4">
                                <div className="flex items-center justify-between mb-3">
                                    <h3 className="font-semibold text-gray-900">Aperçu JSON - Demande
                                        #{dataExportPreview.request?.id}</h3>
                                    <button onClick={() => setDataExportPreview(null)}
                                            className="text-sm text-gray-600 hover:text-gray-900">Fermer
                                    </button>
                                </div>
                                <pre
                                    className="text-xs bg-gray-50 border rounded p-3 overflow-auto max-h-[420px]">{JSON.stringify(dataExportPreview, null, 2)}</pre>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Create Modal */}
            {showCreateModal && (
                <div
                    className="fixed inset-0 bg-black/50 z-40 overflow-y-auto pt-28 pb-10 px-4 flex justify-center items-start"
                    onClick={() => setShowCreateModal(false)}>
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-auto mb-auto"
                         onClick={(e) => e.stopPropagation()}>
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">Ajouter un nouveau tag</h2>
                            <button onClick={() => setShowCreateModal(false)}
                                    className="text-gray-500 text-2xl">&times;</button>
                        </div>
                        <form onSubmit={handleCreateSubmit}>
                            <input type="text" value={createTagName} onChange={(e) => setCreateTagName(e.target.value)}
                                   placeholder="Nom du tag" className="w-full p-2 border border-gray-300 rounded my-2"
                                   autoFocus required/>
                            <div className="flex flex-row items-center mt-4">
                                <button type="submit" disabled={createMutation.isPending}
                                        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mr-4 disabled:opacity-50">{createMutation.isPending ? 'Ajout...' : 'Ajouter'}</button>
                                <button type="button" onClick={() => setShowCreateModal(false)}
                                        className="text-gray-700 font-medium">Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    )

}