# M@ieutIC

La plateforme facilitant l’échange entre doctorants !

## Documentation du projet

Tous les documents se trouvent dans le dossier "docs" a la racine du projet.

## Prérequis

- `PHP >= 8.1`
- [`Composer`](https://getcomposer.org/)
- `MySQL`
- [`Node.js` & `npm`](https://nodejs.org/)

---

> [!CAUTION]
> Ne stockez <ins>***JAMAIS***</ins> le fichier `.env` sur le dépôt !  
> Configurez les variables d’environnement directement sur le serveur ou avec `.env.local`.

---

## Déploiement initial en développement

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

3. **Optimiser les variables d'environnement**
```sh
composer dump-env dev
```

4. **Installer les dépendances PHP**
```sh
composer install
```

5. **Mettre à jour la base de données**
```sh
php bin/console doctrine:migrations:migrate
```

6. **Charger les fixtures**
> [!CAUTION]
> Les fixtures ne doivent jamais être chargées en production !
```sh
php bin/console doctrine:fixtures:load
```

7. **Compiler les ressources front-end**
```sh
php bin/console tailwind:build
php bin/console asset-map:compile
```

8. **Générer la version du Service Worker (pour PWA)**
> [!NOTE]
> En développement, cette étape est optionnelle. Le Service Worker utilisera le placeholder par défaut.
> Elle devient obligatoire en production pour gérer correctement les mises à jour.

**Linux/macOS :**
```sh
bash bin/build-sw.sh
```

**Windows :**
```powershell
.\bin\build-sw.ps1
```

9. **Lancer le serveur de développement**
```sh
cd public/
php -S localhost:8080
```

---

## Déploiement en production

1. **Configurer correctement le fichier `.env`**

2. **Optimiser les variables d'environnement pour prod**
```sh
composer dump-env prod
```

3. **Installer les dépendances PHP**
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

6. **Générer la version du Service Worker**
> [!IMPORTANT]
> Cette étape doit être exécutée à chaque déploiement pour versionner correctement le cache PWA.

**Linux/macOS :**
```sh
bash bin/build-sw.sh
```

**Windows :**
```powershell
.\bin\build-sw.ps1
```

Cette commande remplace le placeholder de version dans [sw.js](public/sw.js) par un hash git (ou timestamp), garantissant que les clients récupèrent la dernière version des assets après chaque déploiement.