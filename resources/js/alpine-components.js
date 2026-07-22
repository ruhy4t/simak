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
}
