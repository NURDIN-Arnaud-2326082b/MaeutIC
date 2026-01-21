const CACHE_NAME = 'maeutic-v1';
const OFFLINE_URL = '/';

// Fichiers à mettre en cache lors de l'installation
const STATIC_CACHE_URLS = [
  '/',
  '/manifest.json',
  // Ajoutez ici d'autres ressources critiques
];

// Installation du service worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installation');
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Mise en cache des ressources');
      return cache.addAll(STATIC_CACHE_URLS);
    })
  );
  self.skipWaiting();
});

// Activation du service worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activation');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Suppression ancien cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// URLs à exclure du cache (endpoints sensibles)
const EXCLUDED_PATHS = [
  '/api/',
  '/admin',
  '/profile',
  '/chat',
  '/private_message',
  '/login',
  '/logout',
  '/register'
];

// URLs autorisées pour le cache (ressources statiques uniquement)
const CACHEABLE_PATTERNS = [
  /\.(js|css|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot)$/,
  /^\/manifest\.json$/,
  /^\/$/
];

// Vérifie si une URL doit être mise en cache
function shouldCache(url) {
  const urlPath = new URL(url).pathname;
  
  // Exclure les chemins sensibles
  if (EXCLUDED_PATHS.some(path => urlPath.startsWith(path))) {
    return false;
  }
  
  // Autoriser uniquement les patterns cacheable
  return CACHEABLE_PATTERNS.some(pattern => pattern.test(urlPath));
}

// Stratégie de fetch: Network First, falling back to Cache
self.addEventListener('fetch', (event) => {
  // Ne pas intercepter les requêtes non-GET
  if (event.request.method !== 'GET') {
    return;
  }

  // Ne pas intercepter les requêtes vers des APIs externes
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Mettre en cache uniquement si:
        // - La requête a réussi (status 200)
        // - L'URL est dans les patterns autorisés
        // - Pas de header Cache-Control: no-store
        const cacheControl = response.headers.get('Cache-Control');
        if (
          response && 
          response.status === 200 && 
          shouldCache(event.request.url) &&
          (!cacheControl || !cacheControl.includes('no-store'))
        ) {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        // Si le réseau échoue, essayer de récupérer depuis le cache
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Si pas en cache et page HTML, retourner la page offline
          if (event.request.headers.get('accept').includes('text/html')) {
            return caches.match(OFFLINE_URL);
          }
        });
      })
  );
});

// Gérer les messages du client
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
