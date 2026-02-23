import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuthStore } from '../store';
import {
  getAuthors,
  createAuthor,
  updateAuthor,
  deleteAuthor,
  getBooks,
  createBook,
  updateBook,
  deleteBook,
  getArticles,
  createArticle,
  updateArticle,
  deleteArticle,
} from '../services/libraryApi';

const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000';

const Library = () => {
  const { user } = useAuthStore();
  const queryClient = useQueryClient();
  
  const [activeTab, setActiveTab] = useState('authors');
  const [showAuthorModal, setShowAuthorModal] = useState(false);
  const [showBookModal, setShowBookModal] = useState(false);
  const [showArticleModal, setShowArticleModal] = useState(false);
  const [editingAuthor, setEditingAuthor] = useState(null);
  const [editingBook, setEditingBook] = useState(null);
  const [editingArticle, setEditingArticle] = useState(null);
  const [openDropdownId, setOpenDropdownId] = useState(null);

  // Authors
  const { data: authors = [] } = useQuery({
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
    mutationFn: ({ id, formData }) => updateAuthor(id, formData),
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
  const { data: books = [] } = useQuery({
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
    mutationFn: ({ id, formData }) => updateBook(id, formData),
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
  const { data: articles = [] } = useQuery({
    queryKey: ['articles'],
    queryFn: getArticles,
  });

  const createArticleMutation = useMutation({
    mutationFn: createArticle,
    onSuccess: () => {
      queryClient.invalidateQueries(['articles']);
      setShowArticleModal(false);
      setEditingArticle(null);
    },
  });

  const updateArticleMutation = useMutation({
    mutationFn: ({ id, data }) => updateArticle(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['articles']);
      setShowArticleModal(false);
      setEditingArticle(null);
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
      updateAuthorMutation.mutate({ id: editingAuthor.id, formData });
    } else {
      createAuthorMutation.mutate(formData);
    }
  };

  const handleBookSubmit = (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    if (editingBook) {
      updateBookMutation.mutate({ id: editingBook.id, formData });
    } else {
      createBookMutation.mutate(formData);
    }
  };

  const handleArticleSubmit = (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
      title: formData.get('title'),
      author: formData.get('author'),
      link: formData.get('link'),
    };
    
    if (editingArticle) {
      updateArticleMutation.mutate({ id: editingArticle.id, data });
    } else {
      createArticleMutation.mutate(data);
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

  const toggleDropdown = (id) => {
    setOpenDropdownId(openDropdownId === id ? null : id);
  };

  return (
    <div className="flex-1 container mx-auto py-6">
      {/* Tab Navigation */}
      <div className="flex justify-center space-x-4 mb-6">
        <button
          onClick={() => setActiveTab('authors')}
          className={`flex-grow px-4 py-2 rounded-lg font-medium focus:outline-none focus:ring-2 ${
            activeTab === 'authors'
              ? 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
              : 'bg-white/45 text-gray-700 backdrop-blur-sm shadow-lg hover:bg-gray-200/45 focus:ring-gray-400'
          }`}
        >
          Auteurs
        </button>
        <button
          onClick={() => setActiveTab('articles')}
          className={`flex-grow px-4 py-2 rounded-lg font-medium focus:outline-none focus:ring-2 ${
            activeTab === 'articles'
              ? 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
              : 'bg-white/45 text-gray-700 backdrop-blur-sm shadow-lg hover:bg-gray-200/45 focus:ring-gray-400'
          }`}
        >
          Articles
        </button>
        <button
          onClick={() => setActiveTab('books')}
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
          
          <div className="flex flex-wrap w-full">
            {authors.map((author) => (
              <div
                key={author.id}
                className="bg-white hover:bg-blue-50 rounded-lg overflow-hidden relative w-44 h-72 m-4 p-3"
              >
                <a href={author.link} target="_blank" rel="noopener noreferrer">
                  <img
                    src={`${BACKEND_URL}${author.image}`}
                    alt={author.name}
                    className="w-full h-40 object-cover rounded-lg"
                  />
                </a>
                <div className="p-4">
                  <h3 className="text-lg font-semibold text-gray-800">{author.name}</h3>
                  <p className="text-sm text-gray-600">
                    {author.birthYear} - {author.deathYear || '...'}
                  </p>
                </div>
                {author.nationality && (
                  <img
                    src={`${BACKEND_URL}/images/flags/${author.nationality}.png`}
                    alt={author.nationality}
                    className="absolute bottom-2 right-2 w-8 h-7"
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
                      <div className="absolute right-0 mt-2 bg-white rounded shadow-lg z-50">
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
      )}

      {/* Articles Tab */}
      {activeTab === 'articles' && (
        <div className="bg-white/45 backdrop-blur-sm shadow-xl p-6 rounded-lg">
          {user && (
            <div className="w-full flex justify-end mb-4">
              <button
                onClick={() => {
                  setEditingArticle(null);
                  setShowArticleModal(true);
                }}
                className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
              >
                Ajouter un article
              </button>
            </div>
          )}
          
          <div className="flex flex-row w-full mb-2">
            <h3 className="w-1/2 font-semibold">Titre</h3>
            <h3 className="flex-1 font-semibold">Auteur</h3>
            {user && <h3 className="w-24"></h3>}
          </div>
          
          {articles.map((article) => (
            <div
              key={article.id}
              className="relative bg-white hover:bg-blue-50 flex flex-row rounded-lg w-full my-1 p-3 items-center"
            >
              <a
                href={article.link}
                target="_blank"
                rel="noopener noreferrer"
                className="w-1/2 text-blue-600 hover:underline"
              >
                {article.title}
              </a>
              <p className="flex-1">{article.author}</p>
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
                  setShowBookModal(true);
                }}
                className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
              >
                Ajouter un livre
              </button>
            </div>
          )}
          
          <div className="flex flex-wrap w-full">
            {books.map((book) => (
              <div
                key={book.id}
                className="bg-white hover:bg-blue-50 rounded-lg overflow-hidden relative w-44 h-72 m-4 p-3"
              >
                <a href={book.link} target="_blank" rel="noopener noreferrer">
                  <img
                    src={`${BACKEND_URL}${book.image}`}
                    alt={book.title}
                    className="w-full h-40 object-cover rounded-lg"
                  />
                </a>
                <div className="p-4">
                  <h3 className="text-lg font-semibold text-gray-800">{book.title}</h3>
                  <p className="text-sm text-gray-600">{book.author}</p>
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
                      <div className="absolute right-0 mt-2 bg-white rounded shadow-lg z-50">
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
      )}

      {/* Author Modal */}
      {showAuthorModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md">
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
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 className="text-xl font-bold mb-4">
              {editingBook ? 'Modifier le livre' : 'Ajouter un livre'}
            </h2>
            <form onSubmit={handleBookSubmit}>
              <div className="mb-4">
                <label className="block text-gray-700 mb-2">Titre</label>
                <input
                  type="text"
                  name="title"
                  defaultValue={editingBook?.title || ''}
                  className="w-full px-3 py-2 border rounded"
                  required
                />
              </div>
              <div className="mb-4">
                <label className="block text-gray-700 mb-2">Auteur</label>
                <input
                  type="text"
                  name="author"
                  defaultValue={editingBook?.author || ''}
                  className="w-full px-3 py-2 border rounded"
                  required
                />
              </div>
              <div className="mb-4">
                <label className="block text-gray-700 mb-2">Lien</label>
                <input
                  type="url"
                  name="link"
                  defaultValue={editingBook?.link || ''}
                  className="w-full px-3 py-2 border rounded"
                  required
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
                    setShowBookModal(false);
                    setEditingBook(null);
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
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md">
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
                <label className="block text-gray-700 mb-2">Auteur</label>
                <input
                  type="text"
                  name="author"
                  defaultValue={editingArticle?.author || ''}
                  className="w-full px-3 py-2 border rounded"
                  required
                />
              </div>
              <div className="mb-4">
                <label className="block text-gray-700 mb-2">Lien</label>
                <input
                  type="url"
                  name="link"
                  defaultValue={editingArticle?.link || ''}
                  className="w-full px-3 py-2 border rounded"
                  required
                />
              </div>
              <div className="flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => {
                    setShowArticleModal(false);
                    setEditingArticle(null);
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
    </div>
  );
};

export default Library;
