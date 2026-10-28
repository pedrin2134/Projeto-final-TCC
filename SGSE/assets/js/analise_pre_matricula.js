(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const botoesAbrir = document.querySelectorAll('[data-open-review]');

        botoesAbrir.forEach(function (botao) {
            const dialog = document.getElementById(botao.dataset.openReview);
            if (!dialog) {
                return;
            }

            botao.addEventListener('click', function () {
                dialog.showModal();
            });

            dialog.querySelectorAll('[data-close-review]').forEach(function (botaoFechar) {
                botaoFechar.addEventListener('click', function () {
                    dialog.close();
                });
            });

            dialog.addEventListener('click', function (evento) {
                if (evento.target === dialog) {
                    dialog.close();
                }
            });

            const formulario = dialog.querySelector('[data-review-form]');
            if (!formulario) {
                if (dialog.hasAttribute('data-open-on-load')) {
                    dialog.showModal();
                }
                return;
            }

            const decisao = dialog.querySelector('[data-final-status]');
            const recomendacao = dialog.querySelector('[data-review-recommendation]');
            const avaliacoes = dialog.querySelectorAll('[data-document-status]');

            function atualizarRecomendacao() {
                const possuiRejeitado = Array.from(avaliacoes).some(function (selecao) {
                    return selecao.value === 'Indeferido';
                });
                const possuiAvaliacaoPendente = Array.from(avaliacoes).some(function (selecao) {
                    return selecao.value === '';
                });
                const possuiDocumentoAusente = dialog.querySelector('.missing-file') !== null;
                const texto = possuiRejeitado || possuiDocumentoAusente
                    ? 'Status recomendado: solicitar correção'
                    : (possuiAvaliacaoPendente
                        ? 'Status recomendado: aguarde a análise dos documentos'
                        : (avaliacoes.length
                            ? 'Status recomendado: aprovação'
                            : 'Status recomendado: aguarde o envio dos documentos'));

                recomendacao.textContent = texto;
                recomendacao.classList.toggle('is-correction', possuiRejeitado || possuiDocumentoAusente);
                if ((possuiRejeitado || possuiDocumentoAusente) && decisao.value === '') {
                    decisao.value = 'Pendente de Documentacao';
                }
            }

            avaliacoes.forEach(function (selecao) {
                selecao.addEventListener('change', atualizarRecomendacao);
            });

            formulario.addEventListener('submit', function (evento) {
                const documentosSemOrientacao = Array.from(avaliacoes).filter(function (selecao) {
                    if (selecao.value !== 'Indeferido') {
                        return false;
                    }
                    const id = selecao.id.replace('avaliacao_', '');
                    const justificativa = dialog.querySelector('#motivo_' + id);
                    return !justificativa || justificativa.value.trim() === '';
                });

                if (documentosSemOrientacao.length) {
                    evento.preventDefault();
                    const id = documentosSemOrientacao[0].id.replace('avaliacao_', '');
                    const justificativa = dialog.querySelector('#motivo_' + id);
                    justificativa.focus();
                    justificativa.setCustomValidity('Informe o motivo para solicitar a correção deste documento.');
                    justificativa.reportValidity();
                    justificativa.addEventListener('input', function limparErro() {
                        justificativa.setCustomValidity('');
                        justificativa.removeEventListener('input', limparErro);
                    });
                }
            });

            atualizarRecomendacao();

            if (dialog.hasAttribute('data-open-on-load')) {
                dialog.showModal();
                if (decisao) {
                    decisao.focus({ preventScroll: true });
                }
            }
        });
    });
})();
