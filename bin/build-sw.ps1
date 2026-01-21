# Script PowerShell de build du Service Worker avec injection de version
# Usage: .\bin\build-sw.ps1

$ErrorActionPreference = "Stop"

$SW_FILE = "public\sw.js"

# Récupérer le hash git ou utiliser un timestamp
try {
    $BUILD_VERSION = (git rev-parse --short HEAD 2>$null)
    if (-not $BUILD_VERSION) {
        throw "Git non disponible"
    }
} catch {
    $BUILD_VERSION = [int][double]::Parse((Get-Date -UFormat %s))
}

Write-Host "🔨 Building Service Worker avec version: $BUILD_VERSION" -ForegroundColor Cyan

# Vérifier que le fichier existe
if (-not (Test-Path $SW_FILE)) {
    Write-Host "❌ Erreur: $SW_FILE n'existe pas" -ForegroundColor Red
    exit 1
}

# Lire le contenu du fichier
$content = Get-Content $SW_FILE -Raw

# Remplacer le placeholder par la version de build
$newContent = $content -replace 'BUILD_VERSION_PLACEHOLDER', $BUILD_VERSION

# Écrire le nouveau contenu
Set-Content -Path $SW_FILE -Value $newContent -NoNewline

Write-Host "✅ Service Worker buildé avec succès (version: $BUILD_VERSION)" -ForegroundColor Green
