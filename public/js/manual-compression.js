/**
 * Client-side Image Compression Script
 * Compress images to under 75KB using Canvas API
 */

window.ImageCompressor = {
    // Max size in bytes (75KB)
    MAX_SIZE: 75 * 1024,

    /**
     * Compress an image file
     * @param {File} file - The file to compress
     * @param {number} quality - Initial quality (0.0 - 1.0)
     * @returns {Promise<File>} - Compressed file
     */
    compress: async function (file, quality = 0.9) {
        // Only compress images
        if (!file.type.match(/image.*/)) {
            return file;
        }

        // Check if already small enough
        if (file.size <= this.MAX_SIZE) {
            return file;
        }

        console.log(`Compressing ${file.name} (${(file.size / 1024).toFixed(2)} KB)...`);

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);

            reader.onload = (event) => {
                const img = new Image();
                img.src = event.target.result;

                img.onload = () => {
                    // Create canvas
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    // Resize if too huge (e.g. > 2000px) to help validation
                    const MAX_DIMENSION = 2000;
                    if (width > MAX_DIMENSION || height > MAX_DIMENSION) {
                        if (width > height) {
                            height *= MAX_DIMENSION / width;
                            width = MAX_DIMENSION;
                        } else {
                            width *= MAX_DIMENSION / height;
                            height = MAX_DIMENSION;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // Recursive compression function
                    const compressLoop = (currentQuality) => {
                        // Export as JPEG (better compression than PNG)
                        canvas.toBlob((blob) => {
                            if (!blob) {
                                reject(new Error('Compression failed'));
                                return;
                            }

                            console.log(`Quality ${currentQuality.toFixed(2)}: ${(blob.size / 1024).toFixed(2)} KB`);

                            if (blob.size <= this.MAX_SIZE || currentQuality <= 0.1) {
                                // Create new file from blob
                                const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                resolve(newFile);
                            } else {
                                // Try lower quality
                                compressLoop(currentQuality - 0.1);
                            }
                        }, 'image/jpeg', currentQuality);
                    };

                    compressLoop(quality);
                };

                img.onerror = (err) => reject(err);
            };

            reader.onerror = (err) => reject(err);
        });
    }
};
