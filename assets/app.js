import './bootstrap.js';
// Désactiver Turbo Drive pour forcer des rechargements complets
import { Turbo } from '@hotwired/turbo';
if (Turbo && Turbo.session) {
    Turbo.session.drive = false;
    console.log('[app] Turbo Drive disabled — full page loads on navigation to avoid partial-state bugs.');
}
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
