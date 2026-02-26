import api from './api';

export const getMapUsers = async () => {
  const { data } = await api.get('/maps/users');
  return data;
};
