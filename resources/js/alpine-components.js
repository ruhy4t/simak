export function registerAlpineComponents(Alpine) {
    Alpine.data('outgoingVerification', (initialDoc = null) => ({
        activeDoc: initialDoc,
        loadedDocs: initialDoc ? { [initialDoc]: true } : {},
        catatan: '',

        toggleDocument(key) {
            this.loadedDocs[key] = true;
            this.activeDoc = this.activeDoc === key ? null : key;
        },

        updateNote(checked, label) {
            const line = `- ${label} tidak sesuai`;

            if (!checked) {
                if (!this.catatan.includes(line)) {
                    this.catatan = this.catatan ? `${this.catatan}\n${line}` : line;
                }

                return;
            }

            const escapedLabel = label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`^-\\s+${escapedLabel}.*$`, 'gm');
            this.catatan = this.catatan.replace(regex, '').trim();
        },
    }));

    Alpine.data('incomingVerification', () => ({
        activeTab: 0,
        activeDoc: null,
        loadedDocs: {},
        catatan: '',

        init() {
            this.$nextTick(() => this.generateInitialNotes());
        },

        toggleDocument(key) {
            this.loadedDocs[key] = true;
            this.activeDoc = this.activeDoc === key ? null : key;
        },

        selectStudent(index) {
            this.activeTab = index;
            this.activeDoc = null;
        },

        generateInitialNotes() {
            const missingDocs = [];

            this.$root.querySelectorAll('input[type="checkbox"][disabled]').forEach((checkbox) => {
                const label = checkbox.closest('label')?.querySelector('span.text-sm')?.textContent?.trim();

                if (label) {
                    missingDocs.push(`- ${label} tidak diunggah`);
                }
            });

            if (missingDocs.length > 0) {
                this.catatan = missingDocs.join('\n');
            }
        },

        updateNote(checked, label) {
            const message = `- ${label} tidak sesuai,`;

            if (!checked) {
                if (!this.catatan.includes(message)) {
                    this.catatan = this.catatan ? `${this.catatan}\n${message}` : message;
                }

                return;
            }

            const escapedLabel = label.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
            const regex = new RegExp(`^-\\s+${escapedLabel}.*$`, 'gm');
            this.catatan = this.catatan
                .replace(regex, '')
                .replace(/^\s*[\r\n]/gm, '')
                .trim();
        },
    }));

    Alpine.data('incomingDetail', () => ({
        activeTab: 0,
        activeDoc: null,

        selectStudent(index) {
            this.activeTab = index;
            this.activeDoc = null;
        },

        previousStudent() {
            if (this.activeTab > 0) {
                this.activeTab--;
            }
        },

        nextStudent(lastIndex) {
            if (this.activeTab < lastIndex) {
                this.activeTab++;
            }
        },
    }));

    Alpine.data('incomingStudentModal', () => ({
        showModal: false,
        modalTitle: '',
        siswaList: [],

        openStudents(title, students) {
            this.modalTitle = title;
            this.siswaList = students;
            this.showModal = true;
        },
    }));

    Alpine.data('schoolUserManagement', () => ({
        showAddModal: false,
        submitting: false,

        init() {
            this.showAddModal = this.$root.dataset.openOnError === 'true';
        },

        openAddModal() {
            this.showAddModal = true;
            this.$nextTick(() => this.$refs.username?.focus());
        },

        closeAddModal() {
            if (!this.submitting) {
                this.showAddModal = false;
            }
        },

        submitCreate(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }

            this.submitting = true;
        },

        confirmAction(event) {
            if (!window.confirm(event.currentTarget.dataset.confirmation || 'Lanjutkan tindakan ini?')) {
                event.preventDefault();
            }
        },
    }));

    Alpine.data('dinasUserManagement', () => ({
        showAddModal: false,
        submitting: false,
        role: 'Operator',
        schoolName: '',
        npsnStatus: 'idle',
        lookupSequence: 0,

        init() {
            this.showAddModal = this.$root.dataset.openOnError === 'true';
            this.role = this.$root.dataset.initialRole || 'Operator';
            this.schoolName = this.$root.dataset.initialSchoolName || '';
        },

        openAddModal() {
            this.showAddModal = true;
            this.$nextTick(() => this.$refs.username?.focus());
        },

        closeAddModal() {
            if (!this.submitting) {
                this.showAddModal = false;
            }
        },

        changeRole(event) {
            this.role = event.target.value;
            this.npsnStatus = 'idle';
        },

        async lookupNpsn(event) {
            const input = event.target;
            const npsn = input.value.replace(/\D/g, '').slice(0, 8);
            input.value = npsn;
            const sequence = ++this.lookupSequence;

            if (npsn.length !== 8) {
                this.npsnStatus = 'idle';
                return;
            }

            this.npsnStatus = 'loading';

            try {
                const response = await fetch(`${this.$root.dataset.lookupUrl}/${encodeURIComponent(npsn)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(`NPSN lookup failed with status ${response.status}`);
                }

                const data = await response.json();
                if (sequence !== this.lookupSequence) {
                    return;
                }

                if (data.found) {
                    this.schoolName = data.nama_sekolah;
                    this.npsnStatus = 'found';
                    return;
                }

                this.npsnStatus = 'not-found';
            } catch (error) {
                if (sequence === this.lookupSequence) {
                    this.npsnStatus = 'error';
                }
            }
        },

        submitCreate(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }

            this.submitting = true;
        },

        confirmAction(event) {
            if (!window.confirm(event.currentTarget.dataset.confirmation || 'Lanjutkan tindakan ini?')) {
                event.preventDefault();
            }
        },
    }));

    Alpine.data('cohortSchoolFilter', () => ({
        search: '',
        visibleCount: 0,

        init() {
            this.visibleCount = Math.max(0, this.$refs.schoolSelect.options.length - 1);
        },

        filterSchools(event) {
            const keyword = event.target.value.trim().toLocaleLowerCase('id-ID');
            let visibleCount = 0;

            Array.from(this.$refs.schoolSelect.options).forEach((option, index) => {
                if (index === 0) {
                    return;
                }

                const matches = keyword === '' || option.dataset.search.includes(keyword);
                option.hidden = !matches;
                visibleCount += matches ? 1 : 0;
            });

            this.visibleCount = visibleCount;
        },
    }));
}
