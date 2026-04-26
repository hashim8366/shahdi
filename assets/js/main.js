/**
 * نظام ادارة الشواهد الذكي – Main JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {

    // ── Copy-to-clipboard buttons ─────────────────────────────────────────────
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const urlInput = btn.closest('.input-group')?.querySelector('.share-url');
            const text     = urlInput ? urlInput.value : (btn.dataset.url ?? '');
            if (!text) return;

            navigator.clipboard.writeText(text).then(() => {
                const original = btn.innerHTML;
                btn.innerHTML  = '<i class="fa-solid fa-check me-1"></i>تم النسخ!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-primary', 'btn-outline-secondary');
                setTimeout(() => {
                    btn.innerHTML = original;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                if (urlInput) {
                    urlInput.select();
                    document.execCommand('copy');
                }
            });
        });
    });

    // ── Toggle password visibility ────────────────────────────────────────────
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input    = document.getElementById(targetId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type       = isPassword ? 'text' : 'password';
            btn.querySelector('i').className = isPassword
                ? 'fa-solid fa-eye-slash'
                : 'fa-solid fa-eye';
        });
    });

    // ── Confirm delete ────────────────────────────────────────────────────────
    document.querySelectorAll('.confirm-delete').forEach(link => {
        link.addEventListener('click', e => {
            if (!confirm('هل أنت متأكد من حذف هذه الشهادة؟ لا يمكن التراجع.')) {
                e.preventDefault();
            }
        });
    });

    // ── File upload drag & drop ───────────────────────────────────────────────
    const uploadArea        = document.getElementById('uploadArea');
    const fileInput         = document.getElementById('certificate_file');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const uploadPreview     = document.getElementById('uploadPreview');
    const uploadFileName    = document.getElementById('uploadFileName');
    const removeFileBtn     = document.getElementById('removeFile');

    if (uploadArea && fileInput) {

        // Click on area opens file picker
        uploadArea.addEventListener('click', e => {
            if (!e.target.closest('button')) {
                fileInput.click();
            }
        });

        // Drag events
        uploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                showPreview(e.dataTransfer.files[0]);
            }
        });

        // File input change
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                showPreview(fileInput.files[0]);
            }
        });

        // Remove file
        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', e => {
                e.stopPropagation();
                fileInput.value = '';
                uploadPlaceholder.classList.remove('d-none');
                uploadPreview.classList.add('d-none');
            });
        }

        function showPreview(file) {
            if (uploadFileName) uploadFileName.textContent = file.name;
            uploadPlaceholder.classList.add('d-none');
            uploadPreview.classList.remove('d-none');
        }
    }

    // ── Auto-dismiss flash alerts ─────────────────────────────────────────────
    document.querySelectorAll('.alert-success.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = window.bootstrap?.Alert?.getOrCreateInstance(alert);
            bsAlert?.close();
        }, 5000);
    });

});
