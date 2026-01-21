// Version de build - À remplacer lors du déploiement par un hash git ou timestamp
// Exemple avec hash git: git rev-parse --short HEAD
// Exemple avec timestamp: date +%s
// Script de déploiement: sed -i "s/BUILD_VERSION_PLACEHOLDER/$(git rev-parse --short HEAD)/" sw.js
const BUILD_VERSION = 'BUILD_VERSION_PLACEHOLDER';
const EFFECTIVE_BUILD_VERSION =
  BUILD_VERSION === 'BUILD_VERSION_PLACEHOLDER' ? String(Date.now()) : BUILD_VERSION;
const CACHE_NAME = `maeutic-${EFFECTIVE_BUILD_VERSION}`;
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
              // Ne pas relancer l'erreur afin de permettre l'installation
              return null;
            })
          )
        );
      })
      .catch((error) => {
        console.error('[Service Worker] Échec lors de la mise en cache des ressources:', error);
        // Ne pas relancer l'erreur pour éviter l'échec de l'installation du service worker
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
      // Nettoyer le cache actuel (stratégie déterministe)
      cleanupCache().catch(err => 
        console.error('[Service Worker] Erreur nettoyage cache lors activation:', err)
      )
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

// Parse les directives Cache-Control et vérifie si une directive spécifique est présente
function hasCacheControlDirective(cacheControlHeader, directive) {
  if (!cacheControlHeader) {
    return false;
  }
  
  // Parser les directives (séparées par des virgules)
  const directives = cacheControlHeader
    .toLowerCase()
    .split(',')
    .map(d => d.trim().split('=')[0]); // Prendre seulement le nom de la directive (avant =)
  
  return directives.includes(directive.toLowerCase());
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
        // - Pas de header Cache-Control: no-store ou no-cache
        const cacheControl = response.headers.get('Cache-Control');
        if (
          response && 
          response.status === 200 && 
          shouldCache(event.request.url) &&
          !hasCacheControlDirective(cacheControl, 'no-store') &&
          !hasCacheControlDirective(cacheControl, 'no-cache')
        ) {
          const cache = await caches.open(CACHE_NAME);
          const responseWithTimestamp = await addCacheTimestamp(response.clone());
          await cache.put(event.request, responseWithTimestamp);
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
        
        // Gérer les différents types de fallback selon le type de requête
        const acceptHeader = event.request.headers.get('accept');
        const destination = event.request.destination;
        
        // Pour les requêtes de navigation HTML, retourner la page offline
        if (
          (destination === 'document' || destination === '') &&
          acceptHeader && 
          acceptHeader.includes('text/html')
        ) {
          return caches.match(OFFLINE_URL);
        }
        
        // Pour les requêtes API/JSON, retourner une réponse d'erreur JSON
        if (acceptHeader && acceptHeader.includes('application/json')) {
          return new Response(
            JSON.stringify({ 
              error: 'offline', 
              message: 'Vous êtes hors ligne. Veuillez réessayer lorsque vous serez connecté.' 
            }),
            {
              status: 503,
              statusText: 'Service Unavailable',
              headers: { 'Content-Type': 'application/json' }
            }
          );
        }
        
        // Pour les autres types de requêtes (images, scripts, etc.), retourner une erreur générique
        return new Response('Service Unavailable', { 
          status: 503, 
          statusText: 'Service Unavailable' 
        });
      })
  );
});

// Gérer les messages du client
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  } else if (event.data && event.data.type === 'CLEANUP_CACHE') {
    // Nettoyage manuel du cache (appelé périodiquement depuis le client)
    event.waitUntil(
      cleanupCache()
        .then(() => {
          console.log('[Service Worker] Nettoyage du cache effectué');
          // Notifier le client que le nettoyage est terminé
          if (event.ports && event.ports[0]) {
            event.ports[0].postMessage({ success: true });
          }
        })
        .catch((err) => {
          console.error('[Service Worker] Erreur nettoyage cache:', err);
          if (event.ports && event.ports[0]) {
            event.ports[0].postMessage({ success: false, error: err.message });
          }
        })
    );
  }
});
