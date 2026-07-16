/**
 * SIMAK Application JavaScript Bundle
 * Includes: Alpine.js, Idle Timeout, Auto-Save
 */

// Import Alpine.js
import Alpine from '@alpinejs/csp';
import collapse from '@alpinejs/collapse';

// Import custom modules
import './idle-timeout.js';
import './autosave.js';
import './dashboard-charts.js';
import './ajax-navigation.js';

// Initialize Alpine
Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();
