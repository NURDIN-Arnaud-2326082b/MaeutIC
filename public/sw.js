const BUILD_VERSION = (typeof self !== 'undefined' && self.BUILD_VERSION) ? self.BUILD_VERSION : 'v1';
const CACHE_NAME = `maeutic-${BUILD_VERSION}`;
const OFFLINE_URL = '/';

// Configuration du cache
const CACHE_CONFIG = {
  maxEntries: 100, // Nombre maximum d'entrées en cache
  maxAge: 7 * 24 * 60 * 60 * 1000, // 7 jours en millisecondes
  staticMaxAge: 30 * 24 * 60 * 60 * 1000 // 30 jours pour les ressources statiques
};

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
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Mise en cache des ressources');
        return Promise.all(
          STATIC_CACHE_URLS.map((url) =>
            cache.add(url).catch((error) => {
              console.error('[Service Worker] Échec de la mise en cache de la ressource:', url, error);
              throw error;
            })
          )
        );
      })
      .catch((error) => {
        console.error('[Service Worker] Échec lors de la mise en cache des ressources:', error);
        throw error;
      })
  );
  self.skipWaiting();
});

// Activation du service worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activation');
  event.waitUntil(
    Promise.all([
      // Supprimer les anciens caches
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME) {
              console.log('[Service Worker] Suppression ancien cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Nettoyer le cache actuel
      cleanupCache()
    ])
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

// Vérifie si une ressource est statique
function isStaticResource(url) {
  return /\.(js|css|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot)$/.test(url);
}

// Nettoie le cache en fonction de la taille et de l'âge
async function cleanupCache() {
  const cache = await caches.open(CACHE_NAME);
  const requests = await cache.keys();
  const now = Date.now();

  // Supprimer les entrées expirées
  const validEntries = [];
  for (const request of requests) {
    const response = await cache.match(request);
    if (response) {
      const cachedTime = response.headers.get('sw-cache-time');
      const isStatic = isStaticResource(request.url);
      const maxAge = isStatic ? CACHE_CONFIG.staticMaxAge : CACHE_CONFIG.maxAge;

      if (cachedTime && (now - parseInt(cachedTime)) > maxAge) {
        // Entrée expirée
        await cache.delete(request);
        console.log('[Service Worker] Suppression entrée expirée:', request.url);
      } else {
        validEntries.push({ request, time: cachedTime ? parseInt(cachedTime) : 0 });
      }
    }
  }

  // Si le nombre d'entrées dépasse la limite, supprimer les plus anciennes
  if (validEntries.length > CACHE_CONFIG.maxEntries) {
    validEntries.sort((a, b) => a.time - b.time);
    const entriesToRemove = validEntries.slice(0, validEntries.length - CACHE_CONFIG.maxEntries);
    
    for (const entry of entriesToRemove) {
      await cache.delete(entry.request);
      console.log('[Service Worker] Suppression entrée (limite atteinte):', entry.request.url);
    }
  }
}

// Ajoute un timestamp à la réponse en cache
function addCacheTimestamp(response) {
  const headers = new Headers(response.headers);
  headers.set('sw-cache-time', Date.now().toString());
  
  return new Response(response.body, {
    status: response.status,
    statusText: response.statusText,
    headers: headers
  });
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
      .then(async (response) => {
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
          const cache = await caches.open(CACHE_NAME);
          const responseWithTimestamp = await addCacheTimestamp(response.clone());
          await cache.put(event.request, responseWithTimestamp);
          
          // Nettoyer le cache périodiquement (1% de chance à chaque requête)
          if (Math.random() < 0.01) {
            cleanupCache().catch(err => console.error('[Service Worker] Erreur nettoyage cache:', err));
          }
        }
        return response;
      })
      .catch(async () => {
        // Si le réseau échoue, essayer de récupérer depuis le cache
        const cachedResponse = await caches.match(event.request);
        if (cachedResponse) {
          // Vérifier si le cache n'est pas expiré
          const cachedTime = cachedResponse.headers.get('sw-cache-time');
          const isStatic = isStaticResource(event.request.url);
          const maxAge = isStatic ? CACHE_CONFIG.staticMaxAge : CACHE_CONFIG.maxAge;
          
          if (cachedTime && (Date.now() - parseInt(cachedTime)) <= maxAge) {
            return cachedResponse;
          }
          // Cache expiré, le supprimer
          const cache = await caches.open(CACHE_NAME);
          await cache.delete(event.request);
        }
        
        // Si pas en cache et page HTML, retourner la page offline
        const acceptHeader = event.request.headers.get('accept');
        if (acceptHeader && acceptHeader.includes('text/html')) {
          return caches.match(OFFLINE_URL);
        }
      })
  );
});

// Gérer les messages du client
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
