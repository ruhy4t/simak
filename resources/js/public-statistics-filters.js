const initializePublicStatisticsFilters = () => {
    document.querySelectorAll('[data-public-statistics-filter]').forEach((form) => {
        if (form.dataset.filterInitialized === 'true') return;
        form.dataset.filterInitialized = 'true';

        const npsnInput = form.querySelector('[data-school-npsn]');
        const statusSelect = form.querySelector('[data-school-status]');
        const statusHelp = form.querySelector('[data-status-help]');
        if (!npsnInput || !statusSelect) return;

        const synchronize = () => {
            const usesNpsn = npsnInput.value.trim().length > 0;
            statusSelect.disabled = usesNpsn;
            statusHelp?.classList.toggle('hidden', !usesNpsn);
        };

        npsnInput.addEventListener('input', synchronize);
        synchronize();
    });
};

document.addEventListener('DOMContentLoaded', initializePublicStatisticsFilters);
document.addEventListener('simak:navigated', initializePublicStatisticsFilters);
