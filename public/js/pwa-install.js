// Enregistrement du Service Worker pour la PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('/sw.js')
      .then((registration) => {
        console.log('Service Worker enregistré avec succès:', registration.scope);
        
        // Vérifier les mises à jour
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          console.log('Nouvelle version du Service Worker détectée');
          
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              // Nouvelle version disponible
              if (confirm('Une nouvelle version est disponible. Voulez-vous mettre à jour ?')) {
                newWorker.postMessage({ type: 'SKIP_WAITING' });
                window.location.reload();
              }
            }
          });
        });
      })
      .catch((error) => {
        console.error('Erreur lors de l\'enregistrement du Service Worker:', error);
      });
    
    // Recharger la page lorsqu'un nouveau service worker prend le contrôle
    let refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (!refreshing) {
        refreshing = true;
        window.location.reload();
      }
    });
  });
}

// Gérer l'événement d'installation PWA
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('PWA peut être installée');
  e.preventDefault();
  deferredPrompt = e;
  
  // Vous pouvez afficher un bouton d'installation personnalisé ici
  // Exemple: showInstallButton();
});

// Détecter quand l'app est installée
window.addEventListener('appinstalled', () => {
  console.log('PWA installée avec succès !');
  deferredPrompt = null;
});

// Fonction pour déclencher l'installation (à appeler depuis un bouton)
window.installPWA = async () => {
  if (!deferredPrompt) {
    console.log('PWA déjà installée ou non disponible');
    return;
  }
  
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  console.log(`Installation ${outcome === 'accepted' ? 'acceptée' : 'refusée'}`);
  deferredPrompt = null;
};
