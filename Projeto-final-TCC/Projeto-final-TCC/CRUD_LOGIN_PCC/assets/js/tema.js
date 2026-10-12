(function () {
    const temaSalvo = localStorage.getItem('tema') || 'claro';
    document.documentElement.dataset.tema = temaSalvo;

    function atualizarBotao(botao) {
        const escuro = document.documentElement.dataset.tema === 'escuro';
        botao.textContent = escuro ? '☀ Tema claro' : '☾ Tema escuro';
        botao.setAttribute('aria-pressed', escuro ? 'true' : 'false');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const botao = document.querySelector('[data-alternar-tema]');
        if (!botao) {
            return;
        }

        atualizarBotao(botao);
        botao.addEventListener('click', function () {
            const novoTema = document.documentElement.dataset.tema === 'escuro' ? 'claro' : 'escuro';
            document.documentElement.dataset.tema = novoTema;
            localStorage.setItem('tema', novoTema);
            atualizarBotao(botao);
        });
    });
})();
