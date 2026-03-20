import {useState} from 'react';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useAuthStore} from '../store';
import BarcodeScanner from '../components/BarcodeScanner';
import {
    createArticle,
    createAuthor,
    createBook,
    deleteArticle,
    deleteAuthor,
    deleteBook,
    getArticles,
    getAuthors,
    getBooks,
    updateArticle,
    updateAuthor,
    updateBook,
} from '../services/libraryApi';

const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000';
const ENABLE_ISBN_SCANNER = false;

const Library = () => {
    const {user} = useAuthStore();
    const queryClient = useQueryClient();

    const [activeTab, setActiveTab] = useState('authors');
    const [searchQuery, setSearchQuery] = useState('');
    const [showAuthorModal, setShowAuthorModal] = useState(false);
    const [showBookModal, setShowBookModal] = useState(false);
    const [showArticleModal, setShowArticleModal] = useState(false);
    const [editingAuthor, setEditingAuthor] = useState(null);
    const [editingBook, setEditingBook] = useState(null);
    const [editingArticle, setEditingArticle] = useState(null);
    const [openDropdownId, setOpenDropdownId] = useState(null);
    const [showScanner, setShowScanner] = useState(false);
    const [scannedBook, setScannedBook] = useState(null);
    const [selectedArticleId, setSelectedArticleId] = useState(null);
    const [removeArticleImage, setRemoveArticleImage] = useState(false);
    const [removeArticlePdf, setRemoveArticlePdf] = useState(false);
    const [articleConcernType, setArticleConcernType] = useState('none');
    const [articleConcernId, setArticleConcernId] = useState('');

    const handleBookFound = ({title, author, image}) => {
        setScannedBook({title, author, imageUrl: image});
        setShowScanner(false);
    };

    // Authors
    const {data: authors = []} = useQuery({
        queryKey: ['authors'],
        queryFn: getAuthors,
    });

    const createAuthorMutation = useMutation({
        mutationFn: createAuthor,
        onSuccess: () => {
            queryClient.invalidateQueries(['authors']);
            setShowAuthorModal(false);
            setEditingAuthor(null);
        },
    });

    const updateAuthorMutation = useMutation({
        mutationFn: ({id, formData}) => updateAuthor(id, formData),
        onSuccess: () => {
            queryClient.invalidateQueries(['authors']);
            setShowAuthorModal(false);
            setEditingAuthor(null);
        },
    });

    const deleteAuthorMutation = useMutation({
        mutationFn: deleteAuthor,
        onSuccess: () => {
            queryClient.invalidateQueries(['authors']);
        },
    });

    // Books
    const {data: books = []} = useQuery({
        queryKey: ['books'],
        queryFn: getBooks,
    });

    const createBookMutation = useMutation({
        mutationFn: createBook,
        onSuccess: () => {
            queryClient.invalidateQueries(['books']);
            setShowBookModal(false);
            setEditingBook(null);
        },
    });

    const updateBookMutation = useMutation({
        mutationFn: ({id, formData}) => updateBook(id, formData),
        onSuccess: () => {
            queryClient.invalidateQueries(['books']);
            setShowBookModal(false);
            setEditingBook(null);
        },
    });

    const deleteBookMutation = useMutation({
        mutationFn: deleteBook,
        onSuccess: () => {
            queryClient.invalidateQueries(['books']);
        },
    });

    // Articles
    const {data: articles = []} = useQuery({
        queryKey: ['articles'],
        queryFn: getArticles,
    });

    const selectedArticle = selectedArticleId
        ? articles.find((article) => article.id === selectedArticleId)
        : null;

    const createArticleMutation = useMutation({
        mutationFn: createArticle,
        onSuccess: () => {
            queryClient.invalidateQueries(['articles']);
            setShowArticleModal(false);
            setEditingArticle(null);
            setRemoveArticleImage(false);
            setRemoveArticlePdf(false);
            setArticleConcernType('none');
            setArticleConcernId('');
        },
    });

    const updateArticleMutation = useMutation({
        mutationFn: ({id, formData}) => updateArticle(id, formData),
        onSuccess: () => {
            queryClient.invalidateQueries(['articles']);
            setShowArticleModal(false);
            setEditingArticle(null);
            setRemoveArticleImage(false);
            setRemoveArticlePdf(false);
            setArticleConcernType('none');
            setArticleConcernId('');
        },
    });

    const deleteArticleMutation = useMutation({
        mutationFn: deleteArticle,
        onSuccess: () => {
            queryClient.invalidateQueries(['articles']);
        },
    });

    const handleAuthorSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        if (editingAuthor) {
            updateAuthorMutation.mutate({id: editingAuthor.id, formData});
        } else {
            createAuthorMutation.mutate(formData);
        }
    };

    const handleBookSubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        // If no file selected but we have a Google Books image URL, fetch & append it
        const imageFile = formData.get('image');
        const imageUrl = formData.get('imageUrl');
        if ((!imageFile || imageFile.size === 0) && imageUrl) {
            try {
                const blob = await fetch(imageUrl).then((r) => r.blob());
                const ext = blob.type.includes('png') ? 'png' : 'jpg';
                formData.set('image', new File([blob], `cover.${ext}`, {type: blob.type}));
            } catch {
                // If fetch fails, proceed without image
            }
        }
        formData.delete('imageUrl');

        if (editingBook) {
            updateBookMutation.mutate({id: editingBook.id, formData});
        } else {
            createBookMutation.mutate(formData);
        }
    };

    const handleArticleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        if (!formData.get('link')) {
            formData.set('link', '');
        }

        formData.set('removeImage', String(removeArticleImage));
        formData.set('removePdf', String(removeArticlePdf));
        formData.set('concernType', articleConcernType);
        formData.set('concernId', articleConcernId || '0');

        if (editingArticle) {
            updateArticleMutation.mutate({id: editingArticle.id, formData});
        } else {
            createArticleMutation.mutate(formData);
        }
    };

    const handleDeleteAuthor = (id) => {
        if (confirm('Voulez-vous vraiment supprimer cet auteur ?')) {
            deleteAuthorMutation.mutate(id);
        }
    };

    const handleDeleteBook = (id) => {
        if (confirm('Voulez-vous vraiment supprimer ce livre ?')) {
            deleteBookMutation.mutate(id);
        }
    };

    const handleDeleteArticle = (id) => {
        if (confirm('Voulez-vous vraiment supprimer cet article ?')) {
            deleteArticleMutation.mutate(id);
        }
    };

    const canEdit = (item) => {
        return user && (item.userId === user.id || user.userType === 1);
    };

    const resolveAssetUrl = (assetPath) => {
        if (!assetPath) return null;
        if (assetPath.startsWith('http://') || assetPath.startsWith('https://')) {
            return assetPath;
        }

        const normalized = assetPath.startsWith('/') ? assetPath : `/${assetPath}`;
        return `${BACKEND_URL}${normalized}`;
    };

    const isExternalArticle = (article) => {
        if (typeof article?.isExternal === 'boolean') {
            return article.isExternal;
        }

        return /^https?:\/\//i.test(article?.link || '');
    };

    const toggleDropdown = (id) => {
        setOpenDropdownId(openDropdownId === id ? null : id);
    };

    // Glossary helpers
    const [glossaryFilter, setGlossaryFilter] = useState({authors: null, books: null, articles: null});

    const removeAccents = (str) => {
        if (!str) return "";
        return String(str).normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    const getFirstLetter = (str) => {
        if (!str) return '#';
        const cleanStr = removeAccents(str);
        const firstChar = cleanStr.charAt(0).toUpperCase();
        return /[A-Z]/.test(firstChar) ? firstChar : '#';
    }

    const getAvailableLetters = (items, keyFn) => {
        const letters = new Set(items.map((item) => getFirstLetter(keyFn(item))));
        return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z']
            .filter((l) => letters.has(l));
    };

    const filterByLetter = (items, keyFn, tab) =>
        glossaryFilter[tab]
            ? items.filter((item) => getFirstLetter(keyFn(item)) === glossaryFilter[tab])
            : items;

    const filterBySearch = (items, fields) => {
        if (!searchQuery.trim()) return items;
        const q = removeAccents(searchQuery.toLowerCase());

        return items.filter((item) =>
            fields.some((f) => {
                if (!item[f]) return false;

                if (Array.isArray(item[f])) {
                    const combinedNames = item[f].map(val => val.name || '').join(' ');
                    return removeAccents(combinedNames.toLowerCase()).includes(q);
                }

                const val = String(item[f]).toLowerCase();
                return removeAccents(val).includes(q);
            })
        );
    };

    const groupByLetter = (items, keyFn) => {
        return items.reduce((acc, item) => {
            const letter = getFirstLetter(keyFn(item));
            if (!acc[letter]) acc[letter] = [];
            acc[letter].push(item);
            return acc;
        }, {});
    };

    const GlossaryNav = ({items, keyFn, tab}) => {
        const available = getAvailableLetters(items, keyFn);
        const active = glossaryFilter[tab];
        return (
            <div className="flex flex-wrap gap-1 mb-4 items-center">
                <button
                    onClick={() => setGlossaryFilter((f) => ({...f, [tab]: null}))}
                    className={`px-2 py-1 rounded text-sm font-semibold transition ${
                        !active ? 'bg-blue-600 text-white' : 'bg-white/70 text-gray-600 hover:bg-blue-100'
                    }`}
                >
                    Tout
                </button>
                {'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').map((letter) => (
                    <button
                        key={letter}
                        disabled={!available.includes(letter)}
                        onClick={() => setGlossaryFilter((f) => ({...f, [tab]: letter}))}
                        className={`px-2 py-1 rounded text-sm font-semibold transition ${
                            active === letter
                                ? 'bg-blue-600 text-white'
                                : available.includes(letter)
                                    ? 'bg-white/70 text-gray-700 hover:bg-blue-100'
                                    : 'text-gray-300 cursor-default'
                        }`}
                    >
                        {letter}
                    </button>
                ))}
            </div>
        );
    };

    return (
        <div className="flex-1 container mx-auto py-6">
            {/* Tab Navigation */}
            <div className="flex justify-center space-x-4 mb-6">
                <button
                    onClick={() => {
                        setActiveTab('authors');
                        setSearchQuery('');
                    }}
                    className={`flex-grow px-4 py-2 rounded-lg font-medium focus:outline-none focus:ring-2 ${
                        activeTab === 'authors'
                            ? 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
                            : 'bg-white/45 text-gray-700 backdrop-blur-sm shadow-lg hover:bg-gray-200/45 focus:ring-gray-400'
                    }`}
                >
                    Auteurs
                </button>
                <button
                    onClick={() => {
                        setActiveTab('articles');
                        setSearchQuery('');
                    }}
                    className={`flex-grow px-4 py-2 rounded-lg font-medium focus:outline-none focus:ring-2 ${
                        activeTab === 'articles'
                            ? 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
                            : 'bg-white/45 text-gray-700 backdrop-blur-sm shadow-lg hover:bg-gray-200/45 focus:ring-gray-400'
                    }`}
                >
                    Articles
                </button>
                <button
                    onClick={() => {
                        setActiveTab('books');
                        setSearchQuery('');
                    }}
                    className={`flex-grow px-4 py-2 rounded-lg font-medium focus:outline-none focus:ring-2 ${
                        activeTab === 'books'
                            ? 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
                            : 'bg-white/45 text-gray-700 backdrop-blur-sm shadow-lg hover:bg-gray-200/45 focus:ring-gray-400'
                    }`}
                >
                    Livres
                </button>
            </div>

            {/* Authors Tab */}
            {activeTab === 'authors' && (
                <div className="bg-white/45 backdrop-blur-sm shadow-xl flex flex-wrap p-4 rounded-lg">
                    {user && (
                        <div className="w-full flex justify-end mb-4">
                            <button
                                onClick={() => {
                                    setEditingAuthor(null);
                                    setShowAuthorModal(true);
                                }}
                                className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                            >
                                Ajouter un auteur
                            </button>
                        </div>
                    )}

                    <div className="w-full">
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Rechercher un auteur..."
                            className="w-full px-4 py-2 mb-3 border rounded-lg bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        />
                        <GlossaryNav items={authors} keyFn={(a) => a.name} tab="authors"/>
                    </div>

                    <div className="flex flex-wrap w-full">
                        {(() => {
                            const searched = filterBySearch(authors, ['name', 'nationality']);
                            const filtered = filterByLetter(searched, (a) => a.name, 'authors');
                            const grouped = groupByLetter(filtered, (a) => a.name);
                            return Object.keys(grouped).sort().map((letter) => (
                                <div key={letter} className="w-full">
                                    <div id={`author-letter-${letter}`} className="w-full px-4 pt-4 pb-1">
                                        <span
                                            className="text-2xl font-bold text-blue-700 border-b-2 border-blue-400 pr-2">{letter}</span>
                                    </div>
                                    <div className="flex flex-wrap">
                                        {grouped[letter].map((author) => (
                                            <div
                                                key={author.id}
                                                className="bg-white hover:bg-blue-50 rounded-lg overflow-hidden relative w-44 h-72 m-4 p-3"
                                            >
                                                <a href={author.link} target="_blank" rel="noopener noreferrer">
                                                    <img
                                                        // src={`${BACKEND_URL}${author.image}`}
                                                        src={resolveAssetUrl(author.image)}
                                                        alt={author.name}
                                                        className="w-full h-40 object-contain rounded-lg"
                                                    />
                                                </a>

                                                <div className="pt-3 px-1 flex flex-col">
                                                    <h3
                                                        className="text-base font-semibold text-gray-800 line-clamp-2 leading-tight"
                                                        title={author.name}
                                                    >
                                                        {author.name}
                                                    </h3>
                                                    {(author.birthYear || author.deathYear) && (
                                                        <p className="text-sm text-gray-600 mt-1">
                                                            {author.birthYear || '?'} - {author.deathYear || '...'}
                                                        </p>
                                                    )}
                                                </div>

                                                {author.nationality && (
                                                    <img
                                                        src={`https://flagicons.lipis.dev/flags/4x3/${author.nationality}.svg`}
                                                        alt={author.nationality}
                                                        className="absolute bottom-2 right-2 w-8 h-7 rounded-lg"
                                                    />
                                                )}
                                                {canEdit(author) && (
                                                    <div className="absolute top-2 right-2">
                                                        <button
                                                            onClick={() => toggleDropdown(`author-${author.id}`)}
                                                            className="focus:outline-none px-2 py-1"
                                                        >
                                                            &#9776;
                                                        </button>
                                                        {openDropdownId === `author-${author.id}` && (
                                                            <div
                                                                className="absolute right-0 mt-2 bg-white rounded shadow-lg z-50">
                                                                <button
                                                                    onClick={() => {
                                                                        setEditingAuthor(author);
                                                                        setShowAuthorModal(true);
                                                                        setOpenDropdownId(null);
                                                                    }}
                                                                    className="block px-2 py-1 w-full text-gray-700 hover:bg-gray-100"
                                                                >
                                                                    Modifier
                                                                </button>
                                                                <button
                                                                    onClick={() => {
                                                                        handleDeleteAuthor(author.id);
                                                                        setOpenDropdownId(null);
                                                                    }}
                                                                    className="block px-2 py-1 w-full text-red-600 hover:bg-gray-100"
                                                                >
                                                                    Supprimer
                                                                </button>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ));
                        })()}
                    </div>
                </div>
            )}

            {/* Articles Tab */}
            {activeTab === 'articles' && (
                <div className="bg-white/45 backdrop-blur-sm shadow-xl p-6 rounded-lg">
                    {user && (
                        <div className="w-full flex justify-end mb-4">
                            <button
                                onClick={() => {
                                    setEditingArticle(null);
                                    setRemoveArticleImage(false);
                                    setRemoveArticlePdf(false);
                                    setArticleConcernType('none');
                                    setArticleConcernId('');
                                    setShowArticleModal(true);
                                }}
                                className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                            >
                                Ajouter un article
                            </button>
                        </div>
                    )}

                    <input
                        type="text"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        placeholder="Rechercher un article ou un auteur..."
                        className="w-full px-4 py-2 mb-3 border rounded-lg bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    />
                    <GlossaryNav items={articles} keyFn={(a) => a.title} tab="articles"/>

                    {selectedArticle ? (
                        <div className="bg-white rounded-lg p-5 border border-gray-200">
                            <button
                                type="button"
                                onClick={() => setSelectedArticleId(null)}
                                className="mb-4 text-sm text-blue-600 hover:text-blue-700"
                            >
                                ← Retour à la liste des articles
                            </button>

                            <h2 className="text-2xl font-bold text-gray-900">{selectedArticle.title}</h2>

                            {selectedArticle.imageUrl && (
                                <img
                                    src={resolveAssetUrl(selectedArticle.imageUrl)}
                                    alt={selectedArticle.title}
                                    className="mt-4 w-full max-h-96 object-cover rounded-lg border border-gray-200"
                                />
                            )}

                            <div className="mt-4 whitespace-pre-wrap text-gray-700 leading-relaxed">
                                {selectedArticle.content || 'Aucun contenu textuel disponible pour cet article.'}
                            </div>

                            {selectedArticle.pdfUrl && (
                                <a
                                    href={resolveAssetUrl(selectedArticle.pdfUrl)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-5 inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100"
                                >
                                    Consulter le PDF joint
                                </a>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="flex flex-row w-full mb-2">
                                <h3 className="w-2/5 font-semibold">Titre</h3>
                                <h3 className="w-1/4 font-semibold">Auteur</h3>
                                <h3 className="w-1/4 font-semibold">Livre</h3>
                                {user && <h3 className="w-24"></h3>}
                            </div>

                            {filterByLetter(filterBySearch(articles, ['title', 'author', 'concernLabel', 'relatedBookTitle', 'relatedAuthorName']), (a) => a.title, 'articles').map((article) => (
                                <div
                                    key={article.id}
                                    className="relative bg-white hover:bg-blue-50 flex flex-row rounded-lg w-full my-1 p-3 items-center"
                                >
                                    {isExternalArticle(article) ? (
                                        <a
                                            href={article.link}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title={article.title}
                                            className="w-2/5 text-blue-600 hover:underline truncate pr-4"
                                        >
                                            {article.title}
                                        </a>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => setSelectedArticleId(article.id)}
                                            title={article.title}
                                            className="w-2/5 text-left text-blue-600 hover:underline truncate pr-4"
                                        >
                                            {article.title}
                                        </button>
                                    )}
                                    <p className="w-1/4 truncate pr-3">{article.author}</p>
                                    <p className="w-1/4 truncate pr-3 text-gray-700">{article.relatedBookTitle || '-'}</p>
                                    {canEdit(article) && (
                                        <div className="absolute top-2 right-2">
                                            <button
                                                onClick={() => toggleDropdown(`article-${article.id}`)}
                                                className="focus:outline-none px-2 py-1"
                                            >
                                                &#9776;
                                            </button>
                                            {openDropdownId === `article-${article.id}` && (
                                                <div className="absolute right-0 mt-2 bg-white rounded shadow-lg z-50">
                                                    <button
                                                        onClick={() => {
                                                            setEditingArticle(article);
                                                            setRemoveArticleImage(false);
                                                            setRemoveArticlePdf(false);
                                                            setArticleConcernType(article.concernType || (article.relatedBookId ? 'book' : article.relatedAuthorId ? 'author' : 'none'));
                                                            setArticleConcernId(String(article.concernId || article.relatedBookId || article.relatedAuthorId || ''));
                                                            setShowArticleModal(true);
                                                            setOpenDropdownId(null);
                                                        }}
                                                        className="block px-2 py-1 w-full text-gray-700 hover:bg-gray-100"
                                                    >
                                                        Modifier
                                                    </button>
                                                    <button
                                                        onClick={() => {
                                                            handleDeleteArticle(article.id);
                                                            setOpenDropdownId(null);
                                                        }}
                                                        className="block px-2 py-1 w-full text-red-600 hover:bg-gray-100"
                                                    >
                                                        Supprimer
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </>
                    )}
                </div>
            )}

            {/* Books Tab */}
            {activeTab === 'books' && (
                <div className="bg-white/45 backdrop-blur-sm shadow-xl flex flex-wrap p-4 rounded-lg">
                    {user && (
                        <div className="w-full flex justify-end mb-4">
                            <button
                                onClick={() => {
                                    setEditingBook(null);
                                    setScannedBook(null);
                                    setShowBookModal(true);
                                }}
                                className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                            >
                                Ajouter un livre
                            </button>
                        </div>
                    )}

                    <div className="w-full">
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Rechercher un livre ou un auteur..."
                            className="w-full px-4 py-2 mb-3 border rounded-lg bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        />
                        <GlossaryNav items={books} keyFn={(b) => b.title} tab="books"/>
                    </div>

                    <div className="flex flex-wrap w-full">
                        {(() => {
                            const searched = filterBySearch(books, ['title', 'authors']);
                            const filtered = filterByLetter(searched, (b) => b.title, 'books');
                            const grouped = groupByLetter(filtered, (b) => b.title);
                            return Object.keys(grouped).sort().map((letter) => (
                                <div key={letter} className="w-full">
                                    <div className="w-full px-4 pt-4 pb-1">
                                        <span
                                            className="text-2xl font-bold text-blue-700 border-b-2 border-blue-400 pr-2">{letter}</span>
                                    </div>
                                    <div className="flex flex-wrap">
                                        {grouped[letter].map((book) => (
                                            <div
                                                key={book.id}
                                                className="bg-white hover:bg-blue-50 rounded-lg overflow-hidden relative w-44 h-72 m-4 p-3"
                                            >
                                                {book.isbn ? (
                                                    <a href={`https://isbnsearch.org/isbn/${book.isbn}`}
                                                       target="_blank"
                                                       rel="noopener noreferrer">
                                                        <img
                                                            // src={`${BACKEND_URL}${book.image}`}
                                                            src={resolveAssetUrl(book.image)}
                                                            alt={book.title}
                                                            className="w-full h-40 object-cover rounded-lg"
                                                        />
                                                    </a>
                                                ) : (
                                                    <div className="pointer-events-none">
                                                        <img
                                                            // src={`${BACKEND_URL}${book.image}`}
                                                            src={resolveAssetUrl(book.image)}
                                                            alt={book.title}
                                                            className="w-full h-40 object-cover rounded-lg"
                                                        />
                                                    </div>
                                                )}
                                                <div className="pt-3 px-1 flex flex-col">
                                                    <h3
                                                        className="text-base font-semibold text-gray-800 line-clamp-2 leading-tight"
                                                        title={book.title}
                                                    >
                                                        {book.title}
                                                    </h3>
                                                    <p className="text-sm text-gray-600 truncate mt-1">
                                                        {book.authors?.map(a => a.name).join(', ') || 'Auteur inconnu'}
                                                    </p>
                                                </div>
                                                {canEdit(book) && (
                                                    <div className="absolute top-2 right-2">
                                                        <button
                                                            onClick={() => toggleDropdown(`book-${book.id}`)}
                                                            className="focus:outline-none px-2 py-1"
                                                        >
                                                            &#9776;
                                                        </button>
                                                        {openDropdownId === `book-${book.id}` && (
                                                            <div
                                                                className="absolute right-0 mt-2 bg-white rounded shadow-lg z-50">
                                                                <button
                                                                    onClick={() => {
                                                                        setEditingBook(book);
                                                                        setShowBookModal(true);
                                                                        setOpenDropdownId(null);
                                                                    }}
                                                                    className="block px-2 py-1 w-full text-gray-700 hover:bg-gray-100"
                                                                >
                                                                    Modifier
                                                                </button>
                                                                <button
                                                                    onClick={() => {
                                                                        handleDeleteBook(book.id);
                                                                        setOpenDropdownId(null);
                                                                    }}
                                                                    className="block px-2 py-1 w-full text-red-600 hover:bg-gray-100"
                                                                >
                                                                    Supprimer
                                                                </button>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ));
                        })()}
                    </div>
                </div>
            )}

            {/* Author Modal */}
            {showAuthorModal && (
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-[12000] overflow-y-auto px-4 pt-24 pb-6">
                    <div className="bg-white rounded-lg p-6 w-full max-w-md mt-4 mb-6">
                        <h2 className="text-xl font-bold mb-4">
                            {editingAuthor ? 'Modifier l\'auteur' : 'Ajouter un auteur'}
                        </h2>
                        <form onSubmit={handleAuthorSubmit}>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Nom</label>
                                <input
                                    type="text"
                                    name="name"
                                    defaultValue={editingAuthor?.name || ''}
                                    className="w-full px-3 py-2 border rounded"
                                    required
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Année de naissance</label>
                                <input
                                    type="number"
                                    name="birthYear"
                                    defaultValue={editingAuthor?.birthYear || ''}
                                    className="w-full px-3 py-2 border rounded"
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Année de décès</label>
                                <input
                                    type="number"
                                    name="deathYear"
                                    defaultValue={editingAuthor?.deathYear || ''}
                                    className="w-full px-3 py-2 border rounded"
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Nationalité</label>
                                <input
                                    type="text"
                                    name="nationality"
                                    defaultValue={editingAuthor?.nationality || ''}
                                    className="w-full px-3 py-2 border rounded"
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Lien</label>
                                <input
                                    type="url"
                                    name="link"
                                    defaultValue={editingAuthor?.link || ''}
                                    className="w-full px-3 py-2 border rounded"
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Image</label>
                                <input
                                    type="file"
                                    name="image"
                                    accept="image/*"
                                    className="w-full px-3 py-2 border rounded"
                                />
                            </div>
                            <div className="flex justify-end space-x-2">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowAuthorModal(false);
                                        setEditingAuthor(null);
                                    }}
                                    className="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                >
                                    {editingAuthor ? 'Modifier' : 'Créer'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Book Modal */}
            {showBookModal && (
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-[12000] overflow-y-auto px-4 pt-24 pb-6">
                    <div className="bg-white rounded-lg p-6 w-full max-w-md mt-4 mb-6">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">
                                {editingBook ? 'Modifier le livre' : 'Ajouter un livre'}
                            </h2>
                            {!editingBook && ENABLE_ISBN_SCANNER && (
                                <button
                                    type="button"
                                    onClick={() => setShowScanner(true)}
                                    className="flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition"
                                >
                                    📷 Scanner ISBN
                                </button>
                            )}
                        </div>

                        {/* key forces form remount with new defaultValues when scan result arrives */}
                        <form
                            key={scannedBook ? `scan-${scannedBook.title}` : `edit-${editingBook?.id}`}
                            onSubmit={handleBookSubmit}
                        >
                            {/* Hidden field carrying Google Books image URL */}
                            <input type="hidden" name="imageUrl" value={scannedBook?.imageUrl || ''}/>

                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Titre</label>
                                <input
                                    type="text"
                                    name="title"
                                    defaultValue={scannedBook?.title || editingBook?.title || ''}
                                    className="w-full px-3 py-2 border rounded"
                                    required
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Auteurs</label>
                                <select
                                    name="author_ids[]"
                                    multiple
                                    defaultValue={editingBook?.authors?.map(a => a.id) || []}
                                    className="w-full px-3 py-2 border rounded min-h-[100px]"
                                    required
                                >
                                    {authors.map((author) => (
                                        <option key={author.id} value={author.id}>
                                            {author.name}
                                        </option>
                                    ))}
                                </select>
                                <p className="text-xs text-gray-500 mt-1">Maintenez Ctrl (Windows) ou Cmd (Mac) pour
                                    sélectionner plusieurs auteurs.</p>
                                {/*<input*/}
                                {/*    type="text"*/}
                                {/*    name="author"*/}
                                {/*    defaultValue={scannedBook?.author || editingBook?.author || ''}*/}
                                {/*    className="w-full px-3 py-2 border rounded"*/}
                                {/*    required*/}
                                {/*/>*/}
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">ISBN</label>
                                <input
                                    type="text"
                                    name="isbn"
                                    defaultValue={editingBook?.isbn || ''}
                                    className="w-full px-3 py-2 border rounded"
                                    required
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Image de couverture</label>
                                {scannedBook?.imageUrl && (
                                    <div className="mb-2 flex items-center gap-3">
                                        <img
                                            src={scannedBook.imageUrl}
                                            alt="Couverture"
                                            className="w-16 h-20 object-cover rounded border"
                                        />
                                        <span
                                            className="text-xs text-green-700">✔ Image récupérée via Google Books</span>
                                    </div>
                                )}
                                <input
                                    type="file"
                                    name="image"
                                    accept="image/*"
                                    className="w-full px-3 py-2 border rounded"
                                />
                                {scannedBook?.imageUrl && (
                                    <p className="text-xs text-gray-400 mt-1">
                                        Laissez vide pour utiliser l'image Google Books, ou choisissez une autre image.
                                    </p>
                                )}
                            </div>
                            <div className="flex justify-end space-x-2">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowBookModal(false);
                                        setEditingBook(null);
                                        setScannedBook(null);
                                    }}
                                    className="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                >
                                    {editingBook ? 'Modifier' : 'Créer'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Article Modal */}
            {showArticleModal && (
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-[12000] overflow-y-auto px-4 pt-24 pb-6">
                    <div className="bg-white rounded-lg p-6 w-full max-w-md mt-4 mb-6">
                        <h2 className="text-xl font-bold mb-4">
                            {editingArticle ? 'Modifier l\'article' : 'Ajouter un article'}
                        </h2>
                        <form onSubmit={handleArticleSubmit}>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Titre</label>
                                <input
                                    type="text"
                                    name="title"
                                    defaultValue={editingArticle?.title || ''}
                                    className="w-full px-3 py-2 border rounded"
                                    required
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Concerne</label>
                                <div className="grid grid-cols-1 gap-2">
                                    <select
                                        value={articleConcernType}
                                        onChange={(e) => {
                                            setArticleConcernType(e.target.value);
                                            setArticleConcernId('');
                                        }}
                                        className="w-full px-3 py-2 border rounded bg-white"
                                    >
                                        <option value="none">-- Aucun --</option>
                                        <option value="book">Livre</option>
                                        <option value="author">Auteur</option>
                                    </select>

                                    {articleConcernType !== 'none' && (
                                        <select
                                            value={articleConcernId}
                                            onChange={(e) => setArticleConcernId(e.target.value)}
                                            className="w-full px-3 py-2 border rounded bg-white"
                                        >
                                            <option value="">-- Choisir --</option>
                                            {articleConcernType === 'book' && books.map((book) => (
                                                <option key={book.id} value={book.id}>
                                                    {book.title}
                                                </option>
                                            ))}
                                            {articleConcernType === 'author' && authors.map((author) => (
                                                <option key={author.id} value={author.id}>
                                                    {author.name}
                                                </option>
                                            ))}
                                        </select>
                                    )}
                                </div>
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Lien</label>
                                <input
                                    type="url"
                                    name="link"
                                    defaultValue={editingArticle?.link || ''}
                                    className="w-full px-3 py-2 border rounded"
                                    placeholder="https://... (laisser vide pour article interne)"
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Contenu textuel</label>
                                <textarea
                                    name="content"
                                    defaultValue={editingArticle?.content || ''}
                                    className="w-full px-3 py-2 border rounded min-h-32"
                                    placeholder="Texte de l'article (utilisé pour les articles internes)"
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Image</label>
                                <div className="rounded-xl border-2 border-dashed border-blue-200 bg-blue-50/40 p-4">
                                    {editingArticle?.imageUrl && !removeArticleImage && (
                                        <div className="mb-3">
                                            <img
                                                src={resolveAssetUrl(editingArticle.imageUrl)}
                                                alt={editingArticle.title}
                                                className="w-full max-h-64 object-cover rounded-lg border border-gray-200"
                                            />
                                        </div>
                                    )}

                                    <input
                                        type="file"
                                        name="image"
                                        accept="image/png,image/jpeg,image/webp,image/gif"
                                        className="block w-full text-sm text-gray-700 file:mr-4 file:rounded-full file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700"
                                    />
                                    <p className="mt-2 text-xs text-gray-500">Formats image, max 5 MB.</p>

                                    {editingArticle?.imageUrl && (
                                        <label className="inline-flex items-center mt-2 gap-2 text-sm text-gray-700">
                                            <input
                                                type="checkbox"
                                                checked={removeArticleImage}
                                                onChange={(e) => setRemoveArticleImage(e.target.checked)}
                                            />
                                            Supprimer l'image actuelle
                                        </label>
                                    )}
                                </div>
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">PDF</label>
                                <div className="rounded-xl border-2 border-dashed border-red-200 bg-red-50/40 p-4">
                                    {editingArticle?.pdfUrl && !removeArticlePdf && (
                                        <a
                                            href={resolveAssetUrl(editingArticle.pdfUrl)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="mb-3 inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-100"
                                        >
                                            Voir le PDF actuel
                                        </a>
                                    )}

                                    <input
                                        type="file"
                                        name="pdf"
                                        accept="application/pdf"
                                        className="block w-full text-sm text-gray-700 file:mr-4 file:rounded-full file:border-0 file:bg-red-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-red-700"
                                    />
                                    <p className="mt-2 text-xs text-gray-500">Format PDF, max 10 MB.</p>

                                    {editingArticle?.pdfUrl && (
                                        <label className="inline-flex items-center mt-2 gap-2 text-sm text-gray-700">
                                            <input
                                                type="checkbox"
                                                checked={removeArticlePdf}
                                                onChange={(e) => setRemoveArticlePdf(e.target.checked)}
                                            />
                                            Supprimer le PDF actuel
                                        </label>
                                    )}
                                </div>
                            </div>
                            <div className="flex justify-end space-x-2">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowArticleModal(false);
                                        setEditingArticle(null);
                                        setRemoveArticleImage(false);
                                        setRemoveArticlePdf(false);
                                        setArticleConcernType('none');
                                        setArticleConcernId('');
                                    }}
                                    className="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                >
                                    {editingArticle ? 'Modifier' : 'Créer'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Barcode Scanner overlay */}
            {showScanner && (
                <BarcodeScanner
                    onBookFound={handleBookFound}
                    onClose={() => setShowScanner(false)}
                />
            )}
        </div>
    );
};

export default Library;