<?php
/**
 * Script de diagnostic pour le déploiement React
 * Accéder via : https://votre-domaine.com/check-deployment.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Déploiement MaeutIC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Diagnostic Déploiement React - MaeutIC</h1>
    
    <?php
    // Vérifier l'environnement
    echo "<h2>1. Configuration de l'environnement</h2>";
    $envFile = __DIR__ . '/../.env';
    $envLocalFile = __DIR__ . '/../.env.local';
    
    echo "<p><strong>Fichier .env :</strong> ";
    if (file_exists($envFile)) {
        echo "<span class='success'>✓ Existe</span>";
        $envContent = file_get_contents($envFile);
        if (preg_match('/APP_ENV=(\w+)/', $envContent, $matches)) {
            echo " - APP_ENV=" . $matches[1];
        }
    } else {
        echo "<span class='error'>✗ N'existe pas</span>";
    }
    echo "</p>";
    
    echo "<p><strong>Fichier .env.local :</strong> ";
    if (file_exists($envLocalFile)) {
        echo "<span class='success'>✓ Existe</span>";
        $envLocalContent = file_get_contents($envLocalFile);
        if (preg_match('/APP_ENV=(\w+)/', $envLocalContent, $matches)) {
            echo " - APP_ENV=" . $matches[1];
        }
    } else {
        echo "<span class='info'>✗ N'existe pas (normal)</span>";
    }
    echo "</p>";
    
    // Vérifier PHP
    echo "<h2>2. Configuration PHP</h2>";
    echo "<p>Version PHP : <strong>" . PHP_VERSION . "</strong></p>";
    
    // Vérifier le manifest React
    echo "<h2>3. Frontend React</h2>";
    $manifestPath = __DIR__ . '/react/.vite/manifest.json';
    
    echo "<p><strong>Répertoire public/react :</strong> ";
    if (is_dir(__DIR__ . '/react')) {
        echo "<span class='success'>✓ Existe</span></p>";
        
        echo "<p><strong>Fichier manifest.json :</strong> ";
        if (file_exists($manifestPath)) {
            echo "<span class='success'>✓ Existe</span></p>";
            
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if ($manifest && isset($manifest['src/main.jsx'])) {
                echo "<p class='success'>✓ Manifest valide</p>";
                echo "<pre>" . htmlspecialchars(json_encode($manifest, JSON_PRETTY_PRINT)) . "</pre>";
                
                // Vérifier les fichiers assets
                $mainFile = __DIR__ . '/react/' . $manifest['src/main.jsx']['file'];
                echo "<p><strong>Fichier JS principal :</strong> ";
                if (file_exists($mainFile)) {
                    echo "<span class='success'>✓ Existe (" . round(filesize($mainFile)/1024, 2) . " KB)</span></p>";
                } else {
                    echo "<span class='error'>✗ N'existe pas : " . htmlspecialchars($manifest['src/main.jsx']['file']) . "</span></p>";
                }
                
                if (isset($manifest['src/main.jsx']['css'][0])) {
                    $cssFile = __DIR__ . '/react/' . $manifest['src/main.jsx']['css'][0];
                    echo "<p><strong>Fichier CSS principal :</strong> ";
                    if (file_exists($cssFile)) {
                        echo "<span class='success'>✓ Existe (" . round(filesize($cssFile)/1024, 2) . " KB)</span></p>";
                    } else {
                        echo "<span class='error'>✗ N'existe pas : " . htmlspecialchars($manifest['src/main.jsx']['css'][0]) . "</span></p>";
                    }
                }
            } else {
                echo "<p class='error'>✗ Manifest invalide ou incomplet</p>";
                echo "<pre>" . htmlspecialchars(file_get_contents($manifestPath)) . "</pre>";
            }
        } else {
            echo "<span class='error'>✗ N'existe pas</span></p>";
            echo "<p class='error'><strong>Le build React n'a pas été déployé !</strong></p>";
        }
        
        // Lister les fichiers dans public/react
        echo "<h3>Contenu de public/react :</h3>";
        echo "<pre>";
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/react', RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            $path = str_replace(__DIR__ . '/react/', '', $file->getPathname());
            echo htmlspecialchars($path);
            if ($file->isFile()) {
                echo " (" . round($file->getSize()/1024, 2) . " KB)";
            }
            echo "\n";
        }
        echo "</pre>";
    } else {
        echo "<span class='error'>✗ N'existe pas</span></p>";
        echo "<p class='error'><strong>Le dossier React n'existe pas !</strong></p>";
    }
    
    // Vérifier les permissions
    echo "<h2>4. Permissions</h2>";
    echo "<p>Permissions public/react : ";
    if (is_dir(__DIR__ . '/react')) {
        echo decoct(fileperms(__DIR__ . '/react') & 0777);
    } else {
        echo "N/A";
    }
    echo "</p>";
    
    // Vérifier var/cache
    echo "<h2>5. Cache Symfony</h2>";
    $cacheDir = __DIR__ . '/../var/cache';
    echo "<p><strong>Répertoire var/cache :</strong> ";
    if (is_dir($cacheDir)) {
        echo "<span class='success'>✓ Existe</span>";
        if (is_writable($cacheDir)) {
            echo " <span class='success'>(Writable)</span>";
        } else {
            echo " <span class='error'>(Non writable !)</span>";
        }
    } else {
        echo "<span class='error'>✗ N'existe pas</span>";
    }
    echo "</p>";
    
    // Informations système
    echo "<h2>6. Informations système</h2>";
    echo "<p>Document root : <code>" . htmlspecialchars(__DIR__) . "</code></p>";
    echo "<p>Server : " . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
    
    ?>
    
    <hr>
    <p><small>Ce fichier doit être supprimé en production pour des raisons de sécurité.</small></p>
</body>
</html>
