#!/bin/bash
# Script de build du Service Worker avec injection de version

set -e

SW_FILE="public/sw.js"
BUILD_VERSION=$(git rev-parse --short HEAD 2>/dev/null || date +%s)

echo "🔨 Building Service Worker avec version: $BUILD_VERSION"

# Vérifier que le fichier existe
if [ ! -f "$SW_FILE" ]; then
    echo "❌ Erreur: $SW_FILE n'existe pas"
    exit 1
fi

# Remplacer le placeholder par la version de build
sed -i.bak "s/BUILD_VERSION_PLACEHOLDER/$BUILD_VERSION/g" "$SW_FILE"

# Supprimer le fichier de backup
rm -f "$SW_FILE.bak"

echo "✅ Service Worker buildé avec succès (version: $BUILD_VERSION)"
