/**
 * Statamic Easy Forms - Precognition Plugin
 * 
 * This file registers the Laravel Precognition plugin with Alpine.js.
 * Load this AFTER Alpine.js but BEFORE Alpine.start() or use defer.
 * 
 * Usage with CDN Alpine:
 * <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
 * <script src="/vendor/statamic-easy-forms/js/easy-forms-precognition.js"></script>
 */

import Precognition from 'laravel-precognition-alpine'

// Register with Alpine when it initializes
document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.plugin(Precognition)
    }
})

// Also try to register immediately if Alpine is already loaded but not started
if (window.Alpine && !window.Alpine.started) {
    window.Alpine.plugin(Precognition)
}
