/**
 * SIMAK Application JavaScript Bundle
 * Includes: Alpine.js, Auto-Save, charts, and AJAX navigation.
 *
 * Authenticated pages load the single session monitor from
 * public/js/session-monitor.js via the application layout.
 */

// Import Alpine.js
import Alpine from '@alpinejs/csp';
import collapse from '@alpinejs/collapse';

// Import custom modules
import './autosave.js';
import './dashboard-charts.js';
import './public-statistics-filters.js';
import './ajax-navigation.js';
import { registerAlpineComponents } from './alpine-components.js';

// Initialize Alpine
Alpine.plugin(collapse);
registerAlpineComponents(Alpine);
window.Alpine = Alpine;
Alpine.start();
