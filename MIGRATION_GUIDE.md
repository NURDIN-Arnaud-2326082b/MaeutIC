# 🚀 MaeutIC - Migration React - Guide de Démarrage

## ✅ Ce qui a été migré

### Frontend React (Complet)
- ✅ Configuration Vite + React 18
- ✅ Structure de routing (React Router v6)
- ✅ State Management (Zustand)
- ✅ API Client (Axios + TanStack Query)
- ✅ Layout (Navbar + Footer)
- ✅ Page d'accueil
- ✅ **Forums** (complètement fonctionnel)
  - Liste des forums par catégorie
  - Affichage des posts
  - Recherche avancée avec filtres
  - Lecteur de musique (Clair de Lune)
  - Commentaires
  - Likes
- ✅ Pages d'authentification (Login, Register)
- 🚧 Pages en attente (placeholders créés) :
  - Chat
  - Bibliothèque
  - Carte des membres
  - Profil utilisateur
  - Administration
  - Messages privés

### Backend Symfony API (Complet)
- ✅ AuthApiController (login, register, vérification email/username)
- ✅ ForumApiController (CRUD posts, recherche)
- ✅ CommentApiController (CRUD commentaires)
- ✅ ReactController (point d'entrée SPA)

## 📦 Installation

### 1. Installer les dépendances npm

```bash
cd frontend
npm install
```

Ou utiliser le script Windows :
```bash
.\install-frontend.bat
```

### 2. Démarrer l'environnement de développement

#### Option A: Script automatique (Windows)
```bash
.\start-dev.bat
```

#### Option B: Manuellement

**Terminal 1 - Vite Dev Server:**
```bash
cd frontend
npm run dev
```

**Terminal 2 - Symfony Server:**
```bash
symfony serve
# ou
php -S localhost:8000 -t public
```

## 🌐 URLs

- **React Dev Server**: http://localhost:3000
- **Symfony API**: http://localhost:8000
- **Application**: http://localhost:3000 (en dev)

## 🏗️ Structure du Projet

```
MaeutIC/
├── frontend/                    # Application React
│   ├── src/
│   │   ├── components/         # Composants réutilisables
│   │   │   ├── Layout.jsx
│   │   │   ├── Navbar.jsx
│   │   │   ├── Footer.jsx
│   │   │   ├── SearchBar.jsx
│   │   │   ├── PostCard.jsx
│   │   │   ├── CommentSection.jsx
│   │   │   └── MusicPlayer.jsx
│   │   ├── pages/              # Pages
│   │   │   ├── Home.jsx
│   │   │   ├── Forums.jsx
│   │   │   ├── ForumPost.jsx
│   │   │   ├── Login.jsx
│   │   │   ├── Register.jsx
│   │   │   └── ...
│   │   ├── services/           # API clients
│   │   │   ├── api.js         # Axios instance
│   │   │   └── apis.js        # API endpoints
│   │   ├── store/              # State management
│   │   │   └── index.js       # Zustand stores
│   │   ├── App.jsx
│   │   ├── main.jsx
│   │   └── index.css
│   ├── package.json
│   ├── vite.config.js
│   └── tailwind.config.js
├── src/Controller/Api/          # API Controllers Symfony
│   ├── AuthApiController.php
│   ├── ForumApiController.php
│   └── CommentApiController.php
├── templates/react/
│   └── index.html.twig         # Point d'entrée React
└── public/react/               # Build de production
```

## 🔧 Développement

### Ajouter une nouvelle page

1. Créer le composant dans `frontend/src/pages/`:
```jsx
// frontend/src/pages/MaNouvellePage.jsx
export default function MaNouvellePage() {
  return <div>Ma nouvelle page</div>
}
```

2. Ajouter la route dans `App.jsx`:
```jsx
<Route path="/ma-nouvelle-page" element={<MaNouvellePage />} />
```

### Ajouter un nouvel endpoint API

1. Dans le controller Symfony approprié:
```php
#[Route('/api/mon-endpoint', name: 'api_mon_endpoint', methods: ['GET'])]
public function monEndpoint(): JsonResponse
{
    return $this->json(['data' => '...']);
}
```

2. Dans `frontend/src/services/apis.js`:
```javascript
export const monApi = {
  getData: () => apiClient.get('/mon-endpoint'),
}
```

3. Utiliser dans un composant:
```jsx
import { useQuery } from '@tanstack/react-query'
import { monApi } from '../services/apis'

function MonComposant() {
  const { data } = useQuery({
    queryKey: ['monEndpoint'],
    queryFn: async () => {
      const response = await monApi.getData()
      return response.data
    },
  })
  
  return <div>{data}</div>
}
```

## 🚢 Build de Production

```bash
cd frontend
npm run build
```

Les fichiers sont générés dans `public/react/`

En production, Symfony servira automatiquement les fichiers buildés.

## 🐛 Dépannage

### Le serveur Vite ne démarre pas
- Vérifiez que Node.js est installé : `node --version`
- Vérifiez que le port 3000 est libre
- Supprimez `node_modules` et réinstallez : `npm install`

### Les APIs ne fonctionnent pas
- Vérifiez que Symfony tourne sur le port 8000
- Vérifiez les CORS si nécessaire
- Regardez la console navigateur (F12) pour les erreurs

### Build échoue
- Vérifiez qu'il n'y a pas d'erreurs ESLint/TypeScript
- Essayez de supprimer `node_modules/.vite` et `dist/`

## 📝 Prochaines Étapes

1. **Compléter les pages manquantes**:
   - Chat (temps réel avec WebSockets?)
   - Bibliothèque (avec système de tags)
   - Carte des membres (intégration Leaflet/Google Maps)
   - Profil utilisateur
   - Admin interface

2. **Améliorations**:
   - Ajouter des tests (Vitest, React Testing Library)
   - Améliorer le SEO (React Helmet)
   - Optimiser les performances
   - Ajouter un système de notifications en temps réel
   - Améliorer l'accessibilité (a11y)

3. **Déploiement**:
   - Configurer CI/CD
   - Optimiser les builds
   - Configurer le cache

## 💡 Conseils

- Utilisez React DevTools pour débugger
- Utilisez TanStack Query DevTools pour voir les requêtes
- Tailwind CSS pour le styling
- Gardez les composants petits et réutilisables
- Utilisez TypeScript pour plus de sécurité (optionnel)

## 📚 Documentation

- [React](https://react.dev/)
- [Vite](https://vitejs.dev/)
- [React Router](https://reactrouter.com/)
- [TanStack Query](https://tanstack.com/query)
- [Zustand](https://github.com/pmndrs/zustand)
- [Tailwind CSS](https://tailwindcss.com/)

---

🎉 **Félicitations! Votre application est maintenant en React!**
