# 🚀 M@ieutIC

## 🖥️ Prérequis

- `PHP >= 8.1`
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download) *(recommandé)*
- `MySQL`
- [`Node.js` & `npm`](https://nodejs.org/) *(pour la compilation CSS avec Tailwind)*

---

> [!CAUTION]
> Ne stockez <ins>***JAMAIS***</ins> le fichier `.env` de production sur le dépôt distant !  
> Configurez les variables d’environnement directement sur le serveur ou avec `.env.local` (non suivi par Git).

---

## ⚡ Déploiement initial en développement

1. **Cloner le dépôt**
```sh
git clone https://github.com/NURDIN-Arnaud-2326082b/MaeutIC.git
cd MaeutIC
```

2. **Préparer l’environnement**
> [!IMPORTANT]
> Copiez le fichier `.env.exemple` en `.env` **avant** d’installer les dépendances.
```sh
cp .env.exemple .env
```
Modifiez les variables de `.env` (notamment `DATABASE_URL`) selon votre configuration.

3. **Installer les dépendances PHP**
```sh
composer install
```

4. **Mettre à jour la base de données**
```sh
php bin/console doctrine:migrations:migrate
```

5. **Charger les fixtures (environnement de dev uniquement, lors de l'initialisation)**
> [!CAUTION]
> Les fixtures ne doivent jamais être chargées en production !
```sh
php bin/console doctrine:fixtures:load
```

6. **Compiler les ressources front-end**
```sh
php bin/console tailwind:build
php bin/console asset-map:compile
```

7. **Lancer le serveur de développement**
```sh
cd public/
php -S localhost:8080
```

---

## 🚀 Déploiement en production

1. **Configurer correctement le fichier `.env`**

2. **Optimiser les variables d'environnement pour prod**
```sh
composer dump-env prod
```

3. **Installer les dépendances (production)**
```sh
composer install --no-dev --optimize-autoloader
```

4. **Mettre à jour la base de données**
```sh
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

5. **Compiler les assets front-end**
```sh
php bin/console tailwind:build
php bin/console asset-map:compile
```
