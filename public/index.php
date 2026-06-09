<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Serve favicon.png when /favicon.ico is requested (fallback for some production setups)
if (PHP_SAPI !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($reqPath === '/favicon.ico') {
        $favicon = __DIR__ . '/favicon.png';
        if (is_file($favicon)) {
            header('Content-Type: image/png');
            header('Content-Length: ' . (string) filesize($favicon));
            header('Cache-Control: public, max-age=31536000');
            readfile($favicon);
            exit;
        }
    }
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
