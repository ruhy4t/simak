/**
 * Idle Timeout Detection Module
 * Tracks user activity and shows warning before auto-logout
 * 
 * @usage Include in app.js: import './idle-timeout.js';
 */

(function () {
    'use strict';

    // Configuration
    const IDLE_TIMEOUT = 55 * 60 * 1000; // 55 minutes (show warning before 60min server timeout)
    const WARNING_DURATION = 5 * 60 * 1000; // 5 minutes warning

    let idleTimer = null;
    let warningTimer = null;
    let isWarningShown = false;

    /**
     * Reset the idle timer
     */
    function resetIdleTimer() {
        // FIX: Jika modal sedang muncul, abaikan aktivitas mouse/keyboard biasa.
        // User harus klik tombol "Lanjutkan Sesi" secara eksplisit.
        if (isWarningShown) return;

        if (warningTimer) {
            clearTimeout(warningTimer);
            warningTimer = null;
        }

        if (idleTimer) {
            clearTimeout(idleTimer);
        }

        idleTimer = setTimeout(showWarningModal, IDLE_TIMEOUT);
    }

    // ... lines 44-118 remain unchanged ...

    /**
     * Continue session
     */
    function continueSession() {
        if (warningTimer) {
            clearInterval(warningTimer);
            warningTimer = null;
        }

        hideWarningModal();

        // Ping server to update last_activity (Session Keep-Alive)
        fetch(window.location.href, {
            method: 'HEAD',
            credentials: 'same-origin'
        }).then(() => {
            console.log('Session extended');
        }).catch(() => { });

        resetIdleTimer();
    }

    /**
     * Show the warning modal
     */
    function showWarningModal() {
        isWarningShown = true;

        // Create modal if doesn't exist
        let modal = document.getElementById('idle-timeout-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'idle-timeout-modal';
            modal.innerHTML = `
                <div class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-4">
                    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Sesi Akan Berakhir</h3>
                        <p class="text-gray-600 mb-4">Anda tidak aktif selama beberapa waktu. Sesi akan otomatis berakhir dalam <span id="idle-countdown" class="font-bold text-red-600">5:00</span></p>
                        <div class="flex gap-3 justify-center">
                            <button onclick="window.IdleTimeout.continue()" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                                Lanjutkan Sesi
                            </button>
                            <button onclick="window.IdleTimeout.logout()" class="px-6 py-2.5 border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition">
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        modal.style.display = 'block';
        startCountdown();
    }

    /**
     * Hide the warning modal
     */
    function hideWarningModal() {
        isWarningShown = false;
        const modal = document.getElementById('idle-timeout-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Start countdown timer
     */
    function startCountdown() {
        let remaining = WARNING_DURATION;
        const countdownEl = document.getElementById('idle-countdown');

        function updateCountdown() {
            remaining -= 1000;
            if (remaining <= 0) {
                window.IdleTimeout.logout();
                return;
            }

            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            if (countdownEl) {
                countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }

        updateCountdown();
        warningTimer = setInterval(updateCountdown, 1000);
    }

    /**
     * Continue session
     */
    function continueSession() {
        if (warningTimer) {
            clearInterval(warningTimer);
            warningTimer = null;
        }
        hideWarningModal();
        resetIdleTimer();
    }

    /**
     * Logout user
     */
    function logout() {
        // Find and submit the logout form
        const logoutForm = document.querySelector('form[action*="logout"]');
        if (logoutForm) {
            logoutForm.submit();
        } else {
            // Fallback: redirect to login
            window.location.href = '/login';
        }
    }

    /**
     * Initialize idle timeout tracking
     */
    function init() {
        // Only run for authenticated users
        if (!document.querySelector('[data-user-authenticated]') &&
            !document.querySelector('form[action*="logout"]')) {
            return;
        }

        // Track user activity
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, resetIdleTimer, { passive: true });
        });

        // Start the timer
        resetIdleTimer();
    }

    // Expose API
    window.IdleTimeout = {
        continue: continueSession,
        logout: logout,
        reset: resetIdleTimer
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
