/**
 * Interactive Multi-Photo Uploader for TUK AJJ Verification Evidence
 * Allows adding multiple files, previewing, removing existing and newly selected photos,
 * with a strict maximum of 3 photos per category.
 */
window.tukPhotoUploader = function (fieldName, existingPhotos = [], maxPhotos = 3) {
    return {
        fieldName: fieldName,
        maxPhotos: maxPhotos,
        existing: Array.isArray(existingPhotos) ? [...existingPhotos] : [],
        newFiles: [],

        get totalCount() {
            return this.existing.length + this.newFiles.length;
        },

        get canAdd() {
            return this.totalCount < this.maxPhotos;
        },

        removeExisting(index) {
            this.existing.splice(index, 1);
        },

        removeNew(index) {
            this.newFiles.splice(index, 1);
            this.syncInput();
        },

        onFileSelect(event) {
            const files = Array.from(event.target.files || []);
            if (!files.length) return;

            const remainingSlots = this.maxPhotos - this.totalCount;
            if (remainingSlots <= 0) {
                if (window.Notiflix && Notiflix.Notify) {
                    Notiflix.Notify.warning(`Maksimal ${this.maxPhotos} foto untuk kategori ini.`);
                }
                event.target.value = '';
                return;
            }

            const maxSizeBytes = 5 * 1024 * 1024; // 5 MB
            const validFiles = [];

            for (const file of files) {
                if (file.size > maxSizeBytes) {
                    if (window.Notiflix && Notiflix.Notify) {
                        Notiflix.Notify.failure(`Berkas "${file.name}" melebihi ukuran maksimal 5 MB.`);
                    }
                    continue;
                }
                validFiles.push(file);
            }

            if (!validFiles.length) {
                event.target.value = '';
                return;
            }

            const filesToAdd = validFiles.slice(0, remainingSlots);
            if (validFiles.length > remainingSlots) {
                if (window.Notiflix && Notiflix.Notify) {
                    Notiflix.Notify.warning(`Hanya ${remainingSlots} foto yang ditambahkan (maksimal ${this.maxPhotos} foto per kategori).`);
                }
            }

            filesToAdd.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.newFiles.push({
                        file: file,
                        preview: e.target.result,
                        name: file.name
                    });
                    this.syncInput();
                };
                reader.readAsDataURL(file);
            });

            event.target.value = '';
        },

        syncInput() {
            try {
                const dt = new DataTransfer();
                this.newFiles.forEach(item => {
                    dt.items.add(item.file);
                });
                const input = document.getElementById('input_' + this.fieldName);
                if (input) {
                    input.files = dt.files;
                }
            } catch (err) {
                console.warn('DataTransfer sync warning:', err);
            }
        }
    };
};
