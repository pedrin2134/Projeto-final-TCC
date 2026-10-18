(function () {
    const fileInputs = document.querySelectorAll(
        '.signup-flow-page .document-row input[type="file"]'
    );

    fileInputs.forEach(function (input) {
        const row = input.closest('.document-row');
        const feedback = row ? row.querySelector('[data-file-feedback]') : null;
        const status = row ? row.querySelector('.status-pill') : null;
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
                status.textContent = originalStatus;
                status.classList.remove('status-anexado');
                if (originalStatusClass) {
                    status.classList.add(originalStatusClass);
                }
                return;
            }

            feedback.textContent = 'Arquivo selecionado: ' + file.name
                + ' — clique em “Avançar para Cursos” para salvar.';
            feedback.hidden = false;
            row.classList.add('has-selected-file');
            status.textContent = 'Anexado';
            if (originalStatusClass) {
                status.classList.remove(originalStatusClass);
            }
            status.classList.add('status-anexado');
        });
    });
})();
