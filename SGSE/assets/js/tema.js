(function () {
    const temaSalvo = localStorage.getItem('tema') || 'claro';
    document.documentElement.dataset.tema = temaSalvo;

    function atualizarBotao(botao) {
        const escuro = document.documentElement.dataset.tema === 'escuro';
        const acao = escuro ? 'Ativar tema claro' : 'Ativar tema escuro';
        const paginaLogin = document.body.classList.contains('login-page');
        botao.textContent = paginaLogin
            ? (escuro ? 'MODO CLARO' : 'MODO ESCURO')
            : (escuro ? '☀' : '☾');
        botao.setAttribute('aria-label', acao);
        botao.title = acao;
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
