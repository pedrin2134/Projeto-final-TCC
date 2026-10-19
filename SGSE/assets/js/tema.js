(function () {
    const temaSalvo = localStorage.getItem('tema') || 'claro';
    document.documentElement.dataset.tema = temaSalvo;

    function atualizarBotoes(botoes) {
        const escuro = document.documentElement.dataset.tema === 'escuro';
        const acao = escuro ? 'Ativar tema claro' : 'Ativar tema escuro';
        const paginaLogin = document.body.classList.contains('login-page');
        botoes.forEach(function (botao) {
            botao.textContent = paginaLogin
                ? (escuro ? 'MODO CLARO' : 'MODO ESCURO')
                : (escuro ? '☀' : '☾');
            botao.setAttribute('aria-label', acao);
            botao.title = acao;
            botao.setAttribute('aria-pressed', escuro ? 'true' : 'false');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const botoes = document.querySelectorAll('[data-alternar-tema]');
        if (botoes.length) {
            atualizarBotoes(botoes);
            botoes.forEach(function (botao) {
                botao.addEventListener('click', function () {
                    const novoTema = document.documentElement.dataset.tema === 'escuro' ? 'claro' : 'escuro';
                    document.documentElement.dataset.tema = novoTema;
                    localStorage.setItem('tema', novoTema);
                    atualizarBotoes(botoes);
                });
            });
        }

        const botaoMenu = document.querySelector('[data-toggle-sidebar]');
        if (!botaoMenu) {
            return;
        }

        function atualizarMenu(recolhido) {
            document.body.classList.toggle('admin-sidebar-collapsed', recolhido);
            botaoMenu.textContent = recolhido ? '›' : '‹';
            botaoMenu.setAttribute('aria-label', recolhido ? 'Expandir menu' : 'Recolher menu');
            botaoMenu.setAttribute('aria-expanded', recolhido ? 'false' : 'true');
            botaoMenu.title = recolhido ? 'Expandir menu' : 'Recolher menu';
        }

        atualizarMenu(localStorage.getItem('admin-menu-recolhido') === 'true');
        botaoMenu.addEventListener('click', function () {
            const recolhido = !document.body.classList.contains('admin-sidebar-collapsed');
            localStorage.setItem('admin-menu-recolhido', recolhido ? 'true' : 'false');
            atualizarMenu(recolhido);
        });
    });
})();
