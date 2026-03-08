# MaeutIC - Migration React

## Structure du projet

```
MaeutIC/
├── frontend/              # Application React
│   ├── src/
│   │   ├── components/   # Composants réutilisables
│   │   ├── pages/        # Pages de l'application
│   │   ├── services/     # API clients
│   │   ├── store/        # State management (Zustand)
│   │   ├── App.jsx
│   │   └── main.jsx
│   ├── package.json
│   └── vite.config.js
├── src/                   # Backend Symfony
│   └── Controller/
│       └── Api/          # API Controllers
└── templates/
    └── react/
        └── index.html.twig
```

## Installation

### 1. Installer les dépendances Frontend

```bash
cd frontend
npm install
```

### 2. Lancer le serveur de développement Vite

```bash
npm run dev
```

Le serveur Vite démarre sur `http://localhost:3000`

### 3. Lancer le serveur Symfony

```bash
php bin/console server:run
# ou
symfony serve
```

Le serveur Symfony démarre sur `http://localhost:8000`

## Développement

### Mode Développement

1. Vite dev server sur port 3000
2. Symfony sur port 8000
3. Les requêtes API sont proxifiées de React vers Symfony

### Build Production

```bash
cd frontend
npm run build
```

Les fichiers compilés sont dans `public/react/`

## API Endpoints

### Authentication
- `POST /api/login` - Connexion
- `POST /api/logout` - Déconnexion
- `POST /api/register` - Inscription
- `GET /api/check-email` - Vérifier email
- `GET /api/check-username` - Vérifier username

### Forums
- `GET /api/forums` - Liste des forums
- `GET /api/forums/{category}` - Posts par catégorie
- `GET /api/forums/post/{id}` - Détails d'un post
- `POST /api/forums/post` - Créer un post
- `PUT /api/forums/post/{id}` - Modifier un post
- `DELETE /api/forums/post/{id}` - Supprimer un post

### Comments
- `GET /api/post/{postId}/comments` - Commentaires d'un post
- `POST /api/post/{postId}/comment` - Créer un commentaire
- `PUT /api/comment/{id}` - Modifier un commentaire
- `DELETE /api/comment/{id}` - Supprimer un commentaire

## Migration Status

### ✅ Complété
- Configuration React + Vite
- Structure de base de l'application
- Routing React Router
- State management (Zustand)
- API client (Axios)
- Layout (Navbar, Footer)
- Page d'accueil
- Pages Forums (Forums, ForumPost)
- Pages Authentication (Login, Register)
- API Controllers (Forums, Comments, Auth)

### 🚧 En cours
- Chat
- Library
- Maps
- Profile
- Admin Interface
- Private Messages

## Technologies

- **Frontend**: React 18, Vite, React Router, Zustand, TanStack Query, Axios
- **Backend**: Symfony 6+, Doctrine ORM
- **Styling**: Tailwind CSS
- **PWA**: Service Worker (déjà configuré)
