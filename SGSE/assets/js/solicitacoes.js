document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-open-request]');
    if (openButton) {
        const dialog = document.getElementById(openButton.dataset.openRequest);
        if (dialog instanceof HTMLDialogElement) {
            dialog.showModal();
        }
        return;
    }

    const closeButton = event.target.closest('[data-close-request]');
    if (closeButton) {
        closeButton.closest('dialog')?.close();
        return;
    }

    const printButton = event.target.closest('[data-print-request]');
    if (printButton) {
        window.print();
        return;
    }

    const submitButton = event.target.closest('[data-submit-action]');
    if (submitButton) {
        const form = submitButton.closest('[data-request-form]');
        const fileInput = form?.querySelector('[data-request-file]');
        if (fileInput instanceof HTMLInputElement) {
            fileInput.required = submitButton.dataset.submitAction === 'concluir';
        }
        return;
    }

});

document.addEventListener('click', (event) => {
    if (event.target instanceof HTMLDialogElement && event.target.matches('[data-request-dialog]')) {
        event.target.close();
    }
});

document.querySelectorAll('[data-response-template]').forEach((quickReplyButton) => {
    quickReplyButton.addEventListener('click', () => {
        const form = quickReplyButton.closest('[data-request-form]');
        const response = form?.querySelector('textarea[name="resposta"]');
        if (!(response instanceof HTMLTextAreaElement)) {
            return;
        }

        response.value = quickReplyButton.dataset.responseTemplate === 'indeferido'
            ? 'Indeferido devido a pendência de documentação cadastral.'
            : 'Solicitação deferida. O documento oficial encontra-se emitido e anexo.';
        response.dispatchEvent(new Event('input', { bubbles: true }));
        response.focus();
        response.setSelectionRange(response.value.length, response.value.length);
    });
});

document.querySelectorAll('[data-request-file]').forEach((fileInput) => {
    if (!(fileInput instanceof HTMLInputElement)) {
        return;
    }

    const form = fileInput.closest('[data-request-form]');
    const preview = form?.querySelector('[data-request-preview]');
    const imagePreview = preview?.querySelector('[data-request-preview-image]');
    const pdfPreview = preview?.querySelector('[data-request-preview-pdf]');
    if (!(preview instanceof HTMLElement)
        || !(imagePreview instanceof HTMLImageElement)
        || !(pdfPreview instanceof HTMLIFrameElement)) {
        return;
    }

    let previewUrl = null;
    fileInput.addEventListener('change', () => {
        if (previewUrl !== null) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
        imagePreview.hidden = true;
        pdfPreview.hidden = true;
        imagePreview.removeAttribute('src');
        pdfPreview.removeAttribute('src');

        const file = fileInput.files?.[0];
        if (!file) {
            preview.querySelector('p').textContent = 'Selecione um arquivo para conferir antes de enviar.';
            return;
        }

        previewUrl = URL.createObjectURL(file);
        if (file.type === 'application/pdf') {
            pdfPreview.src = previewUrl;
            pdfPreview.hidden = false;
            preview.querySelector('p').textContent = file.name;
        } else if (file.type === 'image/jpeg' || file.type === 'image/png') {
            imagePreview.src = previewUrl;
            imagePreview.hidden = false;
            preview.querySelector('p').textContent = file.name;
        } else {
            preview.querySelector('p').textContent = `${file.name} — prévia indisponível para este formato.`;
        }
    });
});

const studentRequestForm = document.querySelector('.student-request-form');
if (studentRequestForm) {
    const description = studentRequestForm.querySelector('[data-request-description]');
    const characterCount = studentRequestForm.querySelector('[data-request-character-count]');
    const documentTip = studentRequestForm.querySelector('[data-request-document-tip]');

    const updateDocumentTip = () => {
        const selectedType = studentRequestForm.querySelector('input[name="id_tipo_solicitacao"]:checked');
        if (!(documentTip instanceof HTMLElement)) {
            return;
        }

        if (!(selectedType instanceof HTMLInputElement)) {
            documentTip.hidden = true;
            documentTip.textContent = '';
            return;
        }

        const typeCard = selectedType.closest('.student-request-type');
        const typeName = typeCard?.querySelector('.student-request-type-card > strong')?.textContent?.trim();
        const typeDetails = typeCard?.querySelector('.student-request-type-card > small')?.textContent?.trim();
        documentTip.textContent = `Pedido de ${typeName || 'documento escolar'}: ${typeDetails || 'Documento escolar oficial'}. Explique abaixo para que você precisa deste documento.`;
        documentTip.hidden = false;
    };

    studentRequestForm.querySelectorAll('input[name="id_tipo_solicitacao"]').forEach((typeInput) => {
        typeInput.addEventListener('change', updateDocumentTip);
    });

    description.addEventListener('input', () => {
        characterCount.textContent = String(description.value.length);
    });
}
