(function () {
    const personalStage = document.querySelector('[data-personal-data-stage]');
    const documentsStage = document.querySelector('[data-documents-stage]');
    const showPersonalButton = document.querySelector('[data-mostrar-dados]');
    const showDocumentsButton = document.querySelector('[data-mostrar-documentos]');
    const title = document.querySelector('[data-inscricao-titulo]');
    const description = document.querySelector('[data-inscricao-descricao]');
    const personalStep = document.querySelector('[data-inscricao-etapa="dados"]');
    const documentsStep = document.querySelector('[data-inscricao-etapa="documentos"]');
    const correctionRequested = documentsStage && documentsStage.dataset.correctionRequested === 'true';

    if (!personalStage || !documentsStage || !showPersonalButton
        || !showDocumentsButton || !title || !description
        || !personalStep || !documentsStep) {
        return;
    }

    function showStage(showPersonalData) {
        personalStage.hidden = !showPersonalData;
        documentsStage.hidden = showPersonalData;
        title.textContent = showPersonalData
            ? 'Dados pessoais.'
            : (correctionRequested ? 'Corrija seus documentos.' : 'Envio de Documentação.');
        description.textContent = showPersonalData
            ? 'Confira os dados informados no cadastro.'
            : (correctionRequested
                ? 'A secretaria pediu ajustes. Consulte os motivos e reenvie os arquivos corrigidos.'
                : 'Anexe os arquivos necessários para a secretaria analisar sua pré-matrícula.');

        personalStep.classList.toggle('active', showPersonalData);
        personalStep.classList.toggle('complete', !showPersonalData);
        personalStep.querySelector('span').textContent = '1';
        documentsStep.classList.toggle('active', !showPersonalData);
        documentsStep.classList.toggle('needs-correction', correctionRequested && !showPersonalData);
        documentsStep.classList.remove('complete');
        documentsStep.querySelector('span').textContent = correctionRequested ? '!' : '2';
    }

    showPersonalButton.addEventListener('click', function () {
        showStage(true);
    });

    showDocumentsButton.addEventListener('click', function () {
        showStage(false);
    });

    showStage(false);
})();
