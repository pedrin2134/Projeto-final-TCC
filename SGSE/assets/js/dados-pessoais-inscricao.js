(function () {
    const personalStage = document.querySelector('[data-personal-data-stage]');
    const documentsStage = document.querySelector('[data-documents-stage]');
    const showPersonalButton = document.querySelector('[data-mostrar-dados]');
    const showDocumentsButton = document.querySelector('[data-mostrar-documentos]');
    const title = document.querySelector('[data-inscricao-titulo]');
    const description = document.querySelector('[data-inscricao-descricao]');
    const personalStep = document.querySelector('[data-inscricao-etapa="dados"]');
    const documentsStep = document.querySelector('[data-inscricao-etapa="documentos"]');

    if (!personalStage || !documentsStage || !showPersonalButton
        || !showDocumentsButton || !title || !description
        || !personalStep || !documentsStep) {
        return;
    }

    function showStage(showPersonalData) {
        personalStage.hidden = !showPersonalData;
        documentsStage.hidden = showPersonalData;
        title.textContent = showPersonalData ? 'Dados pessoais.' : 'Envio de Documentação.';
        description.textContent = showPersonalData
            ? 'Confira os dados informados no cadastro.'
            : 'Anexe os arquivos necessários para a secretaria analisar sua pré-matrícula.';

        personalStep.classList.toggle('active', showPersonalData);
        personalStep.classList.toggle('complete', !showPersonalData);
        personalStep.querySelector('span').textContent = showPersonalData ? '1' : '✓';
        documentsStep.classList.toggle('active', !showPersonalData);
        documentsStep.classList.remove('complete');
        documentsStep.querySelector('span').textContent = '2';
    }

    showPersonalButton.addEventListener('click', function () {
        showStage(true);
    });

    showDocumentsButton.addEventListener('click', function () {
        showStage(false);
    });

    showStage(false);
})();
