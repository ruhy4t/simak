/**
 * Progressive AJAX navigation for simple, script-free pages.
 * Complex forms and pages with page-specific scripts keep normal navigation.
 */
(function () {
    'use strict';

    const config = window.SIMAK_NAVIGATION;
    if (!config || !Array.isArray(config.safeUrls)) return;

    const normalizePath = (value) => {
        const url = new URL(value, window.location.origin);
        const path = url.pathname.replace(/\/+$/, '');

        return path || '/';
    };
    const safePaths = new Set(config.safeUrls.map(normalizePath));
    const minimumLoadingDuration = 320;
    let activeRequest = null;
    let loadingStartedAt = 0;
    let loadingFinishTimer = null;
    let loadingResetTimer = null;

    const currentContent = () => document.getElementById('simak-page-content');
    const currentPageIsSafe = () => currentContent()?.dataset.simakNavigationSafe === 'true';
    const urlIsSafe = (url) => url.origin === window.location.origin && safePaths.has(normalizePath(url));

    const loadingBar = () => {
        let bar = document.getElementById('simak-navigation-progress');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'simak-navigation-progress';
            bar.setAttribute('aria-hidden', 'true');
            document.body.appendChild(bar);
        }

        return bar;
    };

    const setLoading = (loading) => {
        const bar = loadingBar();
        const content = currentContent();
        clearTimeout(loadingFinishTimer);
        clearTimeout(loadingResetTimer);

        if (loading) {
            loadingStartedAt = performance.now();
            document.documentElement.classList.add('simak-is-navigating');
            if (content) content.setAttribute('aria-busy', 'true');

            bar.classList.remove('is-active', 'is-complete');
            void bar.offsetWidth;
            requestAnimationFrame(() => bar.classList.add('is-active'));
            return;
        }

        document.documentElement.classList.remove('simak-is-navigating');
        if (content) content.removeAttribute('aria-busy');

        const elapsed = performance.now() - loadingStartedAt;
        const finishDelay = Math.max(0, minimumLoadingDuration - elapsed);
        loadingFinishTimer = setTimeout(() => {
            bar.classList.add('is-complete');
            loadingResetTimer = setTimeout(() => {
                bar.classList.remove('is-active', 'is-complete');
            }, 180);
        }, finishDelay);
    };

    const initializeAlpineTree = (element) => {
        if (!element || !window.Alpine) return;
        window.Alpine.initTree(element);
    };

    const replaceNavigation = (targetDocument, id) => {
        const current = document.getElementById(id);
        const replacement = targetDocument.getElementById(id);
        if (!current || !replacement) return;

        if (window.Alpine?.destroyTree) window.Alpine.destroyTree(current);
        current.innerHTML = replacement.innerHTML;
        initializeAlpineTree(current);
    };

    const closeMobileSidebar = () => {
        if (!window.Alpine) return;
        const shell = document.querySelector('[x-data*="sidebarOpen"]');
        if (!shell) return;

        const state = window.Alpine.$data(shell);
        if (state) state.sidebarOpen = false;
    };

    const swapPage = (targetDocument, finalUrl) => {
        const oldContent = currentContent();
        const targetContent = targetDocument.getElementById('simak-page-content');
        if (!oldContent || !targetContent || targetContent.dataset.simakNavigationSafe !== 'true') {
            return false;
        }

        const newContent = document.importNode(targetContent, true);
        document.dispatchEvent(new CustomEvent('simak:before-navigate', {
            detail: { root: oldContent },
        }));
        if (window.Alpine?.destroyTree) window.Alpine.destroyTree(oldContent);
        oldContent.replaceWith(newContent);

        replaceNavigation(targetDocument, 'simak-desktop-navigation');
        replaceNavigation(targetDocument, 'simak-mobile-navigation');
        initializeAlpineTree(newContent);
        closeMobileSidebar();

        document.title = targetDocument.title || document.title;
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
        document.dispatchEvent(new CustomEvent('simak:navigated', {
            detail: { url: finalUrl },
        }));

        return true;
    };

    const normalNavigation = (url) => {
        window.location.assign(url.toString());
    };

    const navigate = async (url, { updateHistory = true } = {}) => {
        if (!currentPageIsSafe() || !urlIsSafe(url)) {
            normalNavigation(url);
            return;
        }

        if (activeRequest) activeRequest.abort();
        const requestController = new AbortController();
        activeRequest = requestController;
        setLoading(true);

        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-SIMAK-Navigation': 'true',
                },
                signal: requestController.signal,
            });
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok || !contentType.includes('text/html')) {
                normalNavigation(url);
                return;
            }

            const html = await response.text();
            const targetDocument = new DOMParser().parseFromString(html, 'text/html');
            const finalUrl = new URL(response.url || url, window.location.origin);

            if (!urlIsSafe(finalUrl) || !swapPage(targetDocument, finalUrl.toString())) {
                normalNavigation(finalUrl);
                return;
            }

            if (updateHistory) {
                window.history.pushState({ simakNavigation: true }, '', finalUrl);
            }
        } catch (error) {
            if (error.name !== 'AbortError') normalNavigation(url);
        } finally {
            if (activeRequest === requestController) {
                activeRequest = null;
                setLoading(false);
            }
        }
    };

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const eventTarget = event.target instanceof Element ? event.target : null;
        const anchor = eventTarget?.closest('a[href]');
        if (!anchor || anchor.dataset.noAjax !== undefined || anchor.hasAttribute('download')) return;
        if (anchor.target && anchor.target !== '_self') return;

        const url = new URL(anchor.href, window.location.href);
        if (!urlIsSafe(url) || !currentPageIsSafe()) return;
        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return;

        event.preventDefault();
        navigate(url);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.noAjax !== undefined) return;
        if ((form.method || 'get').toLowerCase() !== 'get' || !currentPageIsSafe()) return;

        const url = new URL(form.action || window.location.href, window.location.href);
        if (!urlIsSafe(url)) return;

        const params = new URLSearchParams();
        for (const [key, value] of new FormData(form, event.submitter).entries()) {
            if (typeof value === 'string') params.append(key, value);
        }
        url.search = params.toString();

        event.preventDefault();
        navigate(url);
    });

    window.addEventListener('popstate', (event) => {
        const url = new URL(window.location.href);
        if (!event.state?.simakNavigation || !currentPageIsSafe() || !urlIsSafe(url)) {
            window.location.reload();
            return;
        }

        navigate(url, { updateHistory: false });
    });

    if (currentPageIsSafe()) {
        window.history.replaceState({
            ...(window.history.state || {}),
            simakNavigation: true,
        }, '', window.location.href);
    }
})();
