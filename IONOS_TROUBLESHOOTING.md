# Guide de Dépannage - Déploiement IONOS

## Problème: Erreur 500 après déploiement

### Diagnostic

1. **Accéder au script de diagnostic**  
   Visitez : `https://votre-domaine.com/check-deployment.php`
   
   Ce script vérifie :
   - Configuration de l'environnement (.env, .env.local)
   - Présence du build React
   - Fichiers manifest et assets
   - Permissions
   - Cache Symfony

2. **Vérifier les logs du serveur**  
   Via SSH sur IONOS :
   ```bash
   tail -f var/log/prod.log
   # ou
   tail -f var/log/dev.log
   ```

### Solutions courantes

#### 1. Le manifest React n'existe pas

**Symptôme :** Script de diagnostic indique "Manifest React non trouvé"

**Causes possibles :**
- Le build React n'a pas été exécuté dans le workflow
- Les fichiers n'ont pas été déployés correctement

**Solution :**
```bash
# Via SSH sur IONOS
cd /votre/chemin/sur/ionos

# Vérifier si le dossier public/react existe
ls -la public/react/

# Si le dossier est vide ou n'existe pas, vérifier les logs du workflow GitHub Actions
# Puis redéployer manuellement si nécessaire
```

#### 2. APP_ENV n'est pas configuré en production

**Symptôme :** L'application cherche le serveur Vite localhost:3000

**Cause :** Le fichier .env.local n'existe pas ou n'a pas APP_ENV=prod

**Solution :**
```bash
# Via SSH sur IONOS
cd /votre/chemin/sur/ionos

# Créer ou modifier .env.local
echo "APP_ENV=prod" > .env.local

# Vider le cache
php bin/console cache:clear --env=prod
```

Le workflow GitHub Actions fait maintenant cela automatiquement, mais si vous déployez manuellement, pensez-y !

#### 3. Problèmes de permissions

**Symptôme :** Erreurs d'écriture dans les logs

**Solution :**
```bash
# Via SSH sur IONOS
cd /votre/chemin/sur/ionos

# Donner les bonnes permissions
chmod -R 755 var/cache var/log
chmod -R 755 public/react

# Si nécessaire
chown -R votreuser:votregroup var/ public/
```

#### 4. Cache Symfony corrompu

**Symptôme :** Erreurs étranges après déploiement

**Solution :**
```bash
# Via SSH sur IONOS
cd /votre/chemin/sur/ionos

# Supprimer complètement le cache
rm -rf var/cache/*

# Reconstruire le cache
php bin/console cache:warmup --env=prod
```

#### 5. Le workflow GitHub Actions a échoué

**Vérifier :**
1. Aller sur GitHub → Onglet "Actions"
2. Vérifier le dernier workflow
3. Lire les logs pour identifier l'erreur

**Erreurs courantes :**
- **npm ci failed** → Problème avec package-lock.json
- **Build React failed** → Erreur dans le code React
- **SFTP connection failed** → Vérifier les secrets GitHub

### Déploiement manuel (si le workflow échoue)

```bash
# Local - Builder React
cd frontend
npm ci
npm run build
cd ..

# Transférer les fichiers via FTP/SFTP
# Puis sur le serveur IONOS :

cd /votre/chemin/sur/ionos

# Créer .env.local
echo "APP_ENV=prod" > .env.local

# Installer les dépendances
php8.3-cli ~/composer.phar install --no-dev --optimize-autoloader

# Migrations
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Permissions
chmod -R 755 var/cache var/log public/react
```

### Script de post-déploiement

Un script `bin/post-deploy-ionos.sh` est disponible pour automatiser ces étapes :

```bash
# Via SSH sur IONOS
cd /votre/chemin/sur/ionos
bash bin/post-deploy-ionos.sh
```

## Checklist de vérification post-déploiement

- [ ] `public/react/.vite/manifest.json` existe
- [ ] Les fichiers JS/CSS dans `public/react/assets/` existent
- [ ] `.env.local` existe avec `APP_ENV=prod`
- [ ] Le cache Symfony a été vidé
- [ ] Les permissions sont correctes (755)
- [ ] Les migrations ont été exécutées
- [ ] Le site se charge sans erreur 500
- [ ] Les routes API fonctionnent
- [ ] Le routing React fonctionne

## Vérifications de sécurité

⚠️ **Après un déploiement réussi, supprimer :**
```bash
rm public/check-deployment.php
```

Ce fichier ne doit pas rester en production pour des raisons de sécurité !

## Contacts

En cas de problème persistant :
- Vérifier les issues GitHub
- Contacter : maieuticprojet@proton.me
