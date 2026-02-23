import api from './api';

// ==================== AUTHORS ====================

export const getAuthors = async () => {
  const { data } = await api.get('/library/authors');
  return data;
};

export const getAuthor = async (id) => {
  const { data } = await api.get(`/library/authors/${id}`);
  return data;
};

export const createAuthor = async (formData) => {
  const { data } = await api.post('/library/authors', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return data;
};

export const updateAuthor = async (id, formData) => {
  const { data } = await api.post(`/library/authors/${id}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return data;
};

export const deleteAuthor = async (id) => {
  const { data } = await api.delete(`/library/authors/${id}`);
  return data;
};

// ==================== BOOKS ====================

export const getBooks = async () => {
  const { data } = await api.get('/library/books');
  return data;
};

export const createBook = async (formData) => {
  const { data } = await api.post('/library/books', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return data;
};

export const updateBook = async (id, formData) => {
  const { data } = await api.post(`/library/books/${id}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return data;
};

export const deleteBook = async (id) => {
  const { data } = await api.delete(`/library/books/${id}`);
  return data;
};

// ==================== ARTICLES ====================

export const getArticles = async () => {
  const { data } = await api.get('/library/articles');
  return data;
};

export const createArticle = async (articleData) => {
  const { data } = await api.post('/library/articles', articleData);
  return data;
};

export const updateArticle = async (id, articleData) => {
  const { data } = await api.put(`/library/articles/${id}`, articleData);
  return data;
};

export const deleteArticle = async (id) => {
  const { data } = await api.delete(`/library/articles/${id}`);
  return data;
};
