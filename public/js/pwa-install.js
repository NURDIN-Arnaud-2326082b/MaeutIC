// Créer une notification toast non-bloquante pour les mises à jour
function showUpdateNotification(newWorker) {
  // Vérifier si une notification existe déjà
  if (document.getElementById('pwa-update-toast')) {
    return;
  }

  const toast = document.createElement('div');
  toast.id = 'pwa-update-toast';
  toast.innerHTML = `
    <div style="
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background-color: #1a1a1a;
      color: white;
      padding: 16px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 16px;
      max-width: 90%;
      animation: slideUp 0.3s ease-out;
    ">
      <span>Une nouvelle version est disponible 🎉</span>
      <button id="pwa-update-btn" style="
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
      ">
        Mettre à jour
      </button>
      <button id="pwa-dismiss-btn" style="
        background-color: transparent;
        color: #ccc;
        border: 1px solid #666;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
      ">
        Plus tard
      </button>
    </div>
  `;

  // Ajouter l'animation CSS
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideUp {
      from {
        transform: translateX(-50%) translateY(100px);
        opacity: 0;
      }
      to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
      }
    }
  `;
  document.head.appendChild(style);

  document.body.appendChild(toast);

  // Gérer le clic sur "Mettre à jour"
  document.getElementById('pwa-update-btn').addEventListener('click', () => {
    newWorker.postMessage({ type: 'SKIP_WAITING' });
    toast.remove();
  });

  // Gérer le clic sur "Plus tard"
  document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
    toast.remove();
  });
}

// Enregistrement du Service Worker pour la PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register(window.location.origin + '/sw.js')
      .then((registration) => {
        console.log('Service Worker enregistré avec succès:', registration.scope);
        
        // Vérifier les mises à jour
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          console.log('Nouvelle version du Service Worker détectée');
          
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              // Nouvelle version disponible - afficher notification non-bloquante
              showUpdateNotification(newWorker);
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
