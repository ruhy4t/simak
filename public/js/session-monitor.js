/**
 * SIMAK Session Monitor
 * Tracks session lifetime and warns user before expiration.
 * Prevents 419 Page Expired errors on long form submissions.
 */

(function () {
    'use strict';

    // Config defaults (will be overwritten by window.SESSION_CONFIG)
    let config = {
        lifetime: 60, // minutes
        warningMinutes: 2,
        aliveUrl: '/session/alive',
        loginUrl: '/login',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content
    };

    let warningTimer;
    let logoutTimer;
    let modal;

    // Merge with server-provided config
    if (window.SESSION_CONFIG) {
        config = { ...config, ...window.SESSION_CONFIG };
    }

    const lifetimeMs = config.lifetime * 60 * 1000;
    const warningMs = Math.max(0, lifetimeMs - (config.warningMinutes * 60 * 1000));

    function init() {
        if (!config.csrfToken) {
            console.warn('Session Monitor: CSRF token not found, disabled.');
            return;
        }

        createModal();
        startTimers();

        // Listen for activity to potentially reset silent timers (optional, but for security strict mode we stick to fixed server lifetime)
        // Note: Laravel session extends on Request. So only "Extend Session" button actually extends it in DB/File.
        // Client-side activity tracking alone is not enough if server session expires.
    }

    function startTimers() {
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);

        // Schedule warning
        warningTimer = setTimeout(showWarning, warningMs);

        // Schedule forced logout (slightly after actual expiry to be safe)
        logoutTimer = setTimeout(forceLogout, lifetimeMs + 2000);
    }

    function createModal() {
        // Create modal HTML
        const modalHtml = `
            <div id="session-warning-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
                
                <!-- Dialog -->
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 transform transition-all animate-bounce-short border border-amber-100">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                            <i class="bi bi-clock-history text-amber-600 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Sesi Hampir Habis</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Sesi login Anda akan berakhir dalam <span class="font-bold text-amber-600">2 menit</span>. 
                                Silakan perpanjang sesi agar data yang Anda input tidak hilang.
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

        document.getElementById('btn-extend').addEventListener('click', extendSession);
        document.getElementById('btn-logout').addEventListener('click', forceLogout);
    }

    function showWarning() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideWarning() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function extendSession() {
        try {
            const response = await fetch(config.aliveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            });

            if (response.ok) {
                hideWarning();
                startTimers(); // Reset local timers
                console.log('Session extended successfully.');
            } else {
                // If server returns 401/419, session already dead
                forceLogout();
            }
        } catch (error) {
            console.error('Failed to extend session:', error);
            // Network error? Don't force logout immediately, maybe retry or let user know
        }
    }

    function forceLogout() {
        window.location.href = config.loginUrl;
    }

    // Initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
