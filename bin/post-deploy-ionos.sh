#!/bin/bash
# Script de post-déploiement pour IONOS
# À exécuter après le déploiement via GitHub Actions

echo "=== Post-Déploiement MaeutIC React ==="
echo ""

# Vérifier l'environnement
echo "1. Vérification de l'environnement..."
if [ ! -f .env.local ]; then
    echo "⚠️  ATTENTION: .env.local n'existe pas!"
    echo "Création de .env.local avec APP_ENV=prod..."
    echo "APP_ENV=prod" > .env.local
    echo "✓ .env.local créé"
else
    echo "✓ .env.local existe"
    if grep -q "APP_ENV=prod" .env.local; then
        echo "✓ APP_ENV=prod est configuré"
    else
        echo "⚠️  APP_ENV n'est pas configuré en prod dans .env.local"
        echo "Ajout de APP_ENV=prod..."
        echo "APP_ENV=prod" >> .env.local
    fi
fi

echo ""
echo "2. Vérification du build React..."
if [ -f public/react/.vite/manifest.json ]; then
    echo "✓ Manifest React trouvé"
    cat public/react/.vite/manifest.json | head -n 20
else
    echo "✗ ERREUR: Manifest React non trouvé!"
    echo "Le build React n'a peut-être pas été déployé correctement."
    exit 1
fi

echo ""
echo "3. Installation des dépendances Composer..."
php8.3-cli ~/composer.phar install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "4. Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo ""
echo "5. Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

echo ""
echo "6. Vérification des permissions..."
chmod -R 755 var/cache var/log
chmod -R 755 public/react

echo ""
echo "=== Déploiement terminé ==="
echo ""
echo "Pour diagnostiquer des problèmes, visitez:"
echo "https://votre-domaine.com/check-deployment.php"
echo ""
echo "⚠️  N'oubliez pas de supprimer check-deployment.php après diagnostic!"
