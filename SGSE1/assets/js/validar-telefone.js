(function () {
    const campoTelefone = document.querySelector('[data-validar-telefone]');

    if (!campoTelefone) {
        return;
    }

    function formatarTelefone(digitos) {
        if (digitos.length === 0) {
            return '';
        }

        const ddd = digitos.slice(0, 2);
        const numero = digitos.slice(2);
        const tamanhoPrefixo = digitos.length > 10 ? 5 : 4;

        if (digitos.length <= 2) {
            return '(' + ddd;
        }

        return '(' + ddd + ') ' + numero.slice(0, tamanhoPrefixo)
            + (numero.length > tamanhoPrefixo ? '-' + numero.slice(tamanhoPrefixo) : '');
    }

    function posicaoDoDigito(valor, quantidade) {
        if (quantidade === 0) {
            return 0;
        }

        let vistos = 0;
        for (let indice = 0; indice < valor.length; indice += 1) {
            if (/\d/.test(valor[indice])) {
                vistos += 1;
                if (vistos === quantidade) {
                    return indice + 1;
                }
            }
        }

        return valor.length;
    }

    function aplicarMascara() {
        const valorAnterior = campoTelefone.value;
        const posicaoAnterior = campoTelefone.selectionStart || 0;
        const digitosAteCursor = valorAnterior.slice(0, posicaoAnterior).replace(/\D/g, '').length;
        const digitos = valorAnterior.replace(/\D/g, '').slice(0, 11);
        const valorFormatado = formatarTelefone(digitos);

        campoTelefone.value = valorFormatado;
        const novaPosicao = posicaoDoDigito(valorFormatado, Math.min(digitosAteCursor, digitos.length));
        campoTelefone.setSelectionRange(novaPosicao, novaPosicao);
        return digitos;
    }

    function validarTelefone() {
        const quantidadeDigitos = campoTelefone.value.replace(/\D/g, '').length;

        if (quantidadeDigitos === 0) {
            campoTelefone.setCustomValidity('');
            return true;
        }

        if (quantidadeDigitos < 10 || quantidadeDigitos > 11) {
            campoTelefone.setCustomValidity('Informe um telefone válido com DDD, contendo 10 ou 11 números.');
            return false;
        }

        campoTelefone.setCustomValidity('');
        return true;
    }

    campoTelefone.maxLength = 15;
    campoTelefone.addEventListener('input', function () {
        aplicarMascara();
        validarTelefone();
    });
    campoTelefone.form.addEventListener('submit', function (evento) {
        if (!validarTelefone()) {
            evento.preventDefault();
            campoTelefone.reportValidity();
        }
    });

    aplicarMascara();
    validarTelefone();
})();
