/**
 * Form Auto-Save Module
 * Saves form data to localStorage periodically and restores on page load
 * 
 * @usage Add data-autosave="unique-key" attribute to form element
 */

(function () {
    'use strict';

    const SAVE_INTERVAL = 30000; // 30 seconds
    const STORAGE_PREFIX = 'simak_draft_';

    /**
     * Get storage key for a form
     */
    function getStorageKey(formKey) {
        return STORAGE_PREFIX + formKey;
    }

    /**
     * Serialize form data to object
     */
    function serializeForm(form) {
        const data = {};
        const formData = new FormData(form);

        for (const [key, value] of formData.entries()) {
            // Skip CSRF token and files
            if (key === '_token' || value instanceof File) {
                continue;
            }

            // Handle multiple values (checkboxes, multi-selects)
            if (data[key] !== undefined) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }

        return data;
    }

    /**
     * Restore form data from object
     */
    function restoreForm(form, data) {
        if (!data || typeof data !== 'object') return;

        Object.entries(data).forEach(([key, value]) => {
            const elements = form.querySelectorAll(`[name="${key}"]`);

            elements.forEach(element => {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    const values = Array.isArray(value) ? value : [value];
                    element.checked = values.includes(element.value);
                } else if (element.type === 'file') {
                    // Skip file inputs
                } else if (element.tagName === 'SELECT') {
                    if (element.multiple && Array.isArray(value)) {
                        Array.from(element.options).forEach(opt => {
                            opt.selected = value.includes(opt.value);
                        });
                    } else {
                        element.value = value;
                    }
                } else {
                    element.value = value;
                }
            });
        });
    }

    /**
     * Save form data to localStorage
     */
    function saveFormData(form, key) {
        try {
            const data = serializeForm(form);
            data._savedAt = Date.now();
            localStorage.setItem(getStorageKey(key), JSON.stringify(data));
            return true;
        } catch (e) {
            console.warn('AutoSave: Failed to save form data', e);
            return false;
        }
    }

    /**
     * Load form data from localStorage
     */
    function loadFormData(key) {
        try {
            const stored = localStorage.getItem(getStorageKey(key));
            if (!stored) return null;

            const data = JSON.parse(stored);

            // Check if draft is older than 24 hours
            if (data._savedAt && (Date.now() - data._savedAt) > 86400000) {
                clearFormData(key);
                return null;
            }

            return data;
        } catch (e) {
            return null;
        }
    }

    /**
     * Clear saved form data
     */
    function clearFormData(key) {
        localStorage.removeItem(getStorageKey(key));
    }

    /**
     * Update save status indicator
     */
    function updateStatusIndicator(form, status) {
        let indicator = form.querySelector('.autosave-status');

        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'autosave-status fixed bottom-4 right-4 px-4 py-2 bg-gray-800 text-white text-sm rounded-lg shadow-lg transition-opacity duration-300 z-50';
            document.body.appendChild(indicator);
        }

        if (status === 'saving') {
            indicator.innerHTML = '<span class="autosave-saving">⏳ Menyimpan draft...</span>';
            indicator.style.opacity = '1';
        } else if (status === 'saved') {
            const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            indicator.innerHTML = `✓ Draft tersimpan (${time})`;
            indicator.style.opacity = '1';

            // Hide after 3 seconds
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 3000);
        } else if (status === 'restored') {
            indicator.innerHTML = '📋 Draft sebelumnya dipulihkan';
            indicator.style.opacity = '1';

            // Add clear button
            const clearBtn = document.createElement('button');
            clearBtn.className = 'ml-2 underline hover:no-underline';
            clearBtn.textContent = 'Hapus';
            clearBtn.onclick = () => {
                const key = form.dataset.autosave;
                clearFormData(key);
                indicator.style.opacity = '0';
            };
            indicator.appendChild(clearBtn);

            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 5000);
        }
    }

    /**
     * Initialize autosave for a form
     */
    function initForm(form) {
        const key = form.dataset.autosave;
        if (!key || form.dataset.autosaveInitialized === 'true') return;
        form.dataset.autosaveInitialized = 'true';

        let saveTimer = null;

        // Try to restore saved data
        const savedData = loadFormData(key);
        if (savedData) {
            restoreForm(form, savedData);
            updateStatusIndicator(form, 'restored');
        }

        // Start periodic save
        function scheduleSave() {
            if (saveTimer) clearTimeout(saveTimer);

            saveTimer = setTimeout(() => {
                updateStatusIndicator(form, 'saving');

                setTimeout(() => {
                    if (saveFormData(form, key)) {
                        updateStatusIndicator(form, 'saved');
                    }
                    scheduleSave();
                }, 500);
            }, SAVE_INTERVAL);
        }

        // Save on input changes (debounced)
        let inputTimer = null;
        form.addEventListener('input', () => {
            if (inputTimer) clearTimeout(inputTimer);
            inputTimer = setTimeout(() => {
                saveFormData(form, key);
            }, 2000);
        });

        // Clear on successful submit
        form.addEventListener('submit', () => {
            clearFormData(key);
        });

        // Start periodic save
        scheduleSave();
    }

    /**
     * Initialize all autosave forms
     */
    function init() {
        const forms = document.querySelectorAll('[data-autosave]');
        forms.forEach(initForm);
    }

    // Expose API
    window.AutoSave = {
        save: saveFormData,
        load: loadFormData,
        clear: clearFormData,
        init: init
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    document.addEventListener('simak:navigated', init);
})();
