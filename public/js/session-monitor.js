/**
 * SIMAK Smart Session Monitor
 * Tracks user activity (mouse, keyboard) and keeps session alive.
 * Warns only on TRUE idle.
 */

(function () {
    'use strict';

    // Default Configuration
    let config = {
        lifetime: 120, // minutes (Server session lifetime)
        warningMinutes: 2, // When to show warning
        keepAliveInterval: 15, // Ping server every X minutes if active
        aliveUrl: '/session/alive',
        loginUrl: '/login',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content
    };

    // State
    let state = {
        lastActivity: Date.now(),
        lastPing: Date.now(),
        warningShown: false,
        checkInterval: null
    };

    let modal;

    // Merge defaults with server-provided config
    if (window.SESSION_CONFIG) {
        config = { ...config, ...window.SESSION_CONFIG };
    }

    const LIFETIME_MS = config.lifetime * 60 * 1000;
    const WARNING_MS = (config.lifetime - config.warningMinutes) * 60 * 1000;
    const KEEP_ALIVE_MS = config.keepAliveInterval * 60 * 1000;

    function init() {
        if (!config.csrfToken) {
            console.warn('Session Monitor: CSRF token not found, disabled.');
            return;
        }

        createModal();
        bindEvents();
        startMonitoring();
    }

    function bindEvents() {
        // Events that count as "Activity"
        const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

        // Throttled event handler
        let throttleTimer;
        const handler = () => {
            if (throttleTimer) return;
            throttleTimer = setTimeout(() => {
                state.lastActivity = Date.now();
                throttleTimer = null;

                // If warning is shown but user moves (and session not dead), extend automatically?
                // User requirement: "jika ada konfirmasi tidak akan logout atau ada pergerakan baru maka session bertambah"
                // So yes, movement = extend.
                if (state.warningShown) {
                    extendSession();
                }
            }, 1000); // Update max once per second
        };

        events.forEach(event => {
            document.addEventListener(event, handler, { passive: true });
        });
    }

    function startMonitoring() {
        // Check status every 1 minute
        state.checkInterval = setInterval(checkSessionStatus, 10000); // Check every 10s for accuracy
    }

    function checkSessionStatus() {
        const now = Date.now();
        const idleTime = now - state.lastActivity;
        const timeSincePing = now - state.lastPing;

        // 1. Logic Keep-Alive (Silent Ping)
        // If user is active (idle < warning) AND haven't pinged in a while
        if (idleTime < WARNING_MS && timeSincePing > KEEP_ALIVE_MS) {
            extendSession(true); // Silent extend
        }

        // 2. Logic Warning
        if (idleTime > WARNING_MS && !state.warningShown) {
            showWarning();
        }

        // 3. Logic Logout (True Idle)
        if (idleTime > LIFETIME_MS) {
            forceLogout();
        }
    }

    function createModal() {
        const modalHtml = `
            <div id="session-warning-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 transform transition-all animate-bounce-short border border-amber-100">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                            <i class="bi bi-clock-history text-amber-600 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Sesi Hampir Habis</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Tidak ada aktivitas selama ${config.lifetime - config.warningMinutes} menit. 
                                Sesi akan berakhir dalam <span id="session-countdown" class="font-bold text-amber-600">beberapa saat</span>.
                                Gerakkan mouse atau klik tombol di bawah.
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button id="btn-logout" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
                            Keluar
                        </button>
                        <button id="btn-extend" class="px-5 py-2 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-lg shadow-lg shadow-amber-500/20 transition-all hover:-translate-y-0.5">
                            Saya Masih Disini
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modal = document.getElementById('session-warning-modal');

        document.getElementById('btn-extend').addEventListener('click', () => extendSession(false));
        document.getElementById('btn-logout').addEventListener('click', forceLogout);
    }

    function showWarning() {
        if (!modal) return;
        state.warningShown = true;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideWarning() {
        if (!modal) return;
        state.warningShown = false;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function extendSession(silent = false) {
        try {
            // Update timestamps immediately to prevent double-firing
            state.lastPing = Date.now();
            state.lastActivity = Date.now();
            if (!silent) hideWarning();

            console.log(silent ? 'Session Monitor: Silent keep-alive ping...' : 'Session Monitor: Extending session...');

            const response = await fetch(config.aliveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            });

            if (!response.ok) {
                if (response.status === 401 || response.status === 419) {
                    console.warn('Session already expired on server.');
                    forceLogout();
                }
            }
        } catch (error) {
            console.error('Failed to extend session:', error);
        }
    }

    function forceLogout() {
        window.location.href = config.loginUrl;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();