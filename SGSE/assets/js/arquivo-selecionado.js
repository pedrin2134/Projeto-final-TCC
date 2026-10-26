(function () {
    const fileInputs = document.querySelectorAll(
        '.signup-flow-page .document-row input[type="file"]'
    );

    fileInputs.forEach(function (input) {
        const row = input.closest('.document-row');
        const feedback = row ? row.querySelector('[data-file-feedback]') : null;
        const status = row ? row.querySelector('.status-pill') : null;
        const dropzone = input.closest('.document-upload-dropzone');
        const uploadTitle = dropzone ? dropzone.querySelector('[data-upload-title]') : null;
        if (!feedback || !status) {
            return;
        }

        const originalStatus = status.textContent;
        const originalStatusClass = Array.from(status.classList).find(function (className) {
            return className.indexOf('status-') === 0 && className !== 'status-pill';
        });

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) {
                feedback.textContent = '';
                feedback.hidden = true;
                row.classList.remove('has-selected-file');
                if (uploadTitle) {
                    uploadTitle.textContent = dropzone.dataset.defaultTitle;
                }
                status.textContent = originalStatus;
                status.classList.remove('status-anexado');
                if (originalStatusClass) {
                    status.classList.add(originalStatusClass);
                }
                return;
            }

            feedback.textContent = 'Arquivo selecionado: ' + file.name;
            feedback.hidden = false;
            row.classList.add('has-selected-file');
            if (uploadTitle) {
                uploadTitle.textContent = file.name;
            }
            status.textContent = 'Anexado';
            if (originalStatusClass) {
                status.classList.remove(originalStatusClass);
            }
            status.classList.add('status-anexado');
        });

        if (dropzone && uploadTitle) {
            dropzone.dataset.defaultTitle = uploadTitle.textContent;
            ['dragenter', 'dragover'].forEach(function (nomeEvento) {
                dropzone.addEventListener(nomeEvento, function (evento) {
                    evento.preventDefault();
                    dropzone.classList.add('is-dragging');
                });
            });
            ['dragleave', 'drop'].forEach(function (nomeEvento) {
                dropzone.addEventListener(nomeEvento, function (evento) {
                    evento.preventDefault();
                    dropzone.classList.remove('is-dragging');
                });
            });
            dropzone.addEventListener('drop', function (evento) {
                const arquivos = evento.dataTransfer && evento.dataTransfer.files;
                if (!arquivos || !arquivos.length) {
                    return;
                }

                const transferencia = new DataTransfer();
                transferencia.items.add(arquivos[0]);
                input.files = transferencia.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });
})();
