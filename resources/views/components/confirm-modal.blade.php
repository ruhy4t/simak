{{-- Custom Confirm Modal - Global Component --}}
{{-- Include this in layouts/app.blade.php --}}

<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="px-6 pt-6 pb-4">
            <div class="flex items-center gap-4">
                <div id="confirmIconContainer" class="w-12 h-12 rounded-full flex items-center justify-center">
                    <i id="confirmIcon" class="bi text-2xl"></i>
                </div>
                <h3 id="confirmTitle" class="text-lg font-bold text-gray-900" style="font-size: 18px;"></h3>
            </div>
        </div>

        <!-- Body -->
        <div class="px-6 pb-6">
            <p id="confirmMessage" class="text-gray-700 leading-relaxed whitespace-pre-line"
                style="font-size: 14px; line-height: 1.7;"></p>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button type="button" id="confirmCancel"
                class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition"
                style="font-size: 14px;">
                Batal
            </button>
            <button type="button" id="confirmOk" class="px-5 py-2.5 rounded-lg font-medium transition"
                style="font-size: 14px;">
                OK
            </button>
        </div>
    </div>
</div>

<script>
    // Custom Confirm Modal Function
    window.showConfirm = function (options) {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmModal');
            const title = document.getElementById('confirmTitle');
            const message = document.getElementById('confirmMessage');
            const iconContainer = document.getElementById('confirmIconContainer');
            const icon = document.getElementById('confirmIcon');
            const okBtn = document.getElementById('confirmOk');
            const cancelBtn = document.getElementById('confirmCancel');

            // Set content
            title.textContent = options.title || 'Konfirmasi';
            message.textContent = options.message || 'Apakah Anda yakin?';

            // Set type styling
            const type = options.type || 'warning';
            if (type === 'danger') {
                iconContainer.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-red-100';
                icon.className = 'bi bi-exclamation-triangle text-2xl text-red-600';
                okBtn.className = 'px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition';
            } else if (type === 'success') {
                iconContainer.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-green-100';
                icon.className = 'bi bi-check-circle text-2xl text-green-600';
                okBtn.className = 'px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition';
            } else {
                iconContainer.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-yellow-100';
                icon.className = 'bi bi-question-circle text-2xl text-yellow-600';
                okBtn.className = 'px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition';
            }

            // Set button text
            okBtn.textContent = options.okText || 'OK';
            cancelBtn.textContent = options.cancelText || 'Batal';

            // Show modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Handle clicks
            const handleOk = () => {
                cleanup();
                resolve(true);
            };

            const handleCancel = () => {
                cleanup();
                resolve(false);
            };

            const handleBackdrop = (e) => {
                if (e.target === modal) {
                    cleanup();
                    resolve(false);
                }
            };

            const cleanup = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                okBtn.removeEventListener('click', handleOk);
                cancelBtn.removeEventListener('click', handleCancel);
                modal.removeEventListener('click', handleBackdrop);
            };

            okBtn.addEventListener('click', handleOk);
            cancelBtn.addEventListener('click', handleCancel);
            modal.addEventListener('click', handleBackdrop);
        });
    };

    // Helper to intercept form submissions with data-confirm attribute
    document.addEventListener('DOMContentLoaded', function () {
        // Handle forms with data-confirm
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const message = this.dataset.confirm;
                const type = this.dataset.confirmType || 'warning';
                const title = this.dataset.confirmTitle || 'Konfirmasi';

                const confirmed = await showConfirm({
                    title: title,
                    message: message,
                    type: type
                });

                if (confirmed) {
                    // Remove the listener and submit
                    this.removeAttribute('data-confirm');
                    this.submit();
                }
            });
        });

        // Handle buttons with data-confirm
        document.querySelectorAll('button[data-confirm]').forEach(btn => {
            btn.addEventListener('click', async function (e) {
                e.preventDefault();
                const message = this.dataset.confirm;
                const type = this.dataset.confirmType || 'warning';
                const title = this.dataset.confirmTitle || 'Konfirmasi';

                const confirmed = await showConfirm({
                    title: title,
                    message: message,
                    type: type
                });

                if (confirmed && this.form) {
                    this.form.submit();
                }
            });
        });
    });
</script>