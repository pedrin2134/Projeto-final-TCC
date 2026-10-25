(function () {
    const campoCpf = document.querySelector('[data-mascarar-cpf]');

    if (!campoCpf) {
        return;
    }

    function formatarCpf(digitos) {
        let cpf = digitos.slice(0, 11);
        cpf = cpf.replace(/^(\d{3})(\d)/, '$1.$2');
        cpf = cpf.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
        return cpf.replace(/\.(\d{3})(\d)/, '.$1-$2');
    }

    function aplicarMascara() {
        const valorAnterior = campoCpf.value;
        const posicaoAnterior = campoCpf.selectionStart || 0;
        const digitosAteCursor = valorAnterior.slice(0, posicaoAnterior).replace(/\D/g, '').length;
        const digitos = valorAnterior.replace(/\D/g, '').slice(0, 11);
        const cpfFormatado = formatarCpf(digitos);
        campoCpf.value = cpfFormatado;

        if (digitosAteCursor === 0) {
            campoCpf.setSelectionRange(0, 0);
            return;
        }

        let vistos = 0;
        let novaPosicao = cpfFormatado.length;
        for (let indice = 0; indice < cpfFormatado.length; indice += 1) {
            if (/\d/.test(cpfFormatado[indice])) {
                vistos += 1;
                if (vistos === Math.min(digitosAteCursor, digitos.length)) {
                    novaPosicao = indice + 1;
                    break;
                }
            }
        }

        campoCpf.setSelectionRange(novaPosicao, novaPosicao);
    }

    campoCpf.maxLength = 14;
    campoCpf.addEventListener('input', aplicarMascara);
    aplicarMascara();
})();
