(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const dialog = document.querySelector('[data-course-editor]');
        if (!(dialog instanceof HTMLDialogElement)) {
            return;
        }

        const inputImagem = dialog.querySelector('[data-course-image]');
        const areaImagem = dialog.querySelector('[data-course-dropzone]');
        const nomeImagem = dialog.querySelector('[data-course-image-name]');

        function abrirEditor() {
            if (!dialog.open) {
                dialog.showModal();
            }
            const campoNome = dialog.querySelector('#nome');
            if (campoNome) {
                campoNome.focus({ preventScroll: true });
            }
        }

        document.querySelectorAll('[data-open-course-editor]').forEach(function (botao) {
            botao.addEventListener('click', abrirEditor);
        });

        dialog.querySelectorAll('[data-close-course-editor]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                dialog.close();
            });
        });

        dialog.addEventListener('click', function (evento) {
            if (evento.target === dialog) {
                dialog.close();
            }
        });

        if (dialog.hasAttribute('data-open-on-load')) {
            abrirEditor();
        }

        if (!(inputImagem instanceof HTMLInputElement)
            || !(areaImagem instanceof HTMLElement)
            || !(nomeImagem instanceof HTMLElement)) {
            return;
        }

        inputImagem.addEventListener('change', function () {
            const arquivo = inputImagem.files && inputImagem.files[0];
            nomeImagem.textContent = arquivo
                ? arquivo.name
                : 'Arraste a capa aqui ou clique para selecionar';
        });

        ['dragenter', 'dragover'].forEach(function (eventoNome) {
            areaImagem.addEventListener(eventoNome, function (evento) {
                evento.preventDefault();
                areaImagem.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventoNome) {
            areaImagem.addEventListener(eventoNome, function (evento) {
                evento.preventDefault();
                areaImagem.classList.remove('is-dragging');
            });
        });

        areaImagem.addEventListener('drop', function (evento) {
            const arquivos = evento.dataTransfer && evento.dataTransfer.files;
            if (!arquivos || !arquivos.length) {
                return;
            }

            const transferencia = new DataTransfer();
            transferencia.items.add(arquivos[0]);
            inputImagem.files = transferencia.files;
            inputImagem.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
})();
