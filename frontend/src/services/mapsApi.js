import api from './api';

export const getMapUsers = async () => {
  const { data } = await api.get('/maps/users');
  return data;
};

export const searchTags = async (query) => {
  const { data } = await api.get('/maps/tags/search', {
    params: { q: query }
  });
  return data;
};

export const searchUsers = async (query, options = {}) => {
  const { data } = await api.get('/maps/search-users', {
    params: {
      q: query,
      friends: options.friends ?? true,
      recommendations: options.recommendations ?? true,
      page: options.page ?? 1,
      limit: options.limit ?? 20
    }
  });
  return data;
};

export const filterByTags = async (tagIds, options = {}) => {
  const params = new URLSearchParams();
  tagIds.forEach(id => params.append('tags[]', id));
  params.append('friends', options.friends ?? true);
  params.append('recommendations', options.recommendations ?? true);
  params.append('page', options.page ?? 1);
  params.append('limit', options.limit ?? 20);
  
  const { data } = await api.get(`/maps/filter-by-tags?${params.toString()}`);
  return data;
};
