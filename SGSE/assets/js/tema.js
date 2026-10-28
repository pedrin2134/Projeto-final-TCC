(function () {
    const temaSalvo = localStorage.getItem('tema') || 'claro';
    document.documentElement.dataset.tema = temaSalvo;

    document.addEventListener('DOMContentLoaded', function () {
        const botoesTema = document.querySelectorAll('[data-alternar-tema]');

        function atualizarBotoesTema() {
            const escuro = document.documentElement.dataset.tema === 'escuro';
            botoesTema.forEach(function (botao) {
                botao.textContent = escuro ? '☀' : '☾';
                botao.setAttribute('aria-label', escuro ? 'Ativar tema claro' : 'Ativar tema escuro');
                botao.title = escuro ? 'Ativar tema claro' : 'Ativar tema escuro';
                botao.setAttribute('aria-pressed', escuro ? 'true' : 'false');
            });
        }

        atualizarBotoesTema();
        botoesTema.forEach(function (botao) {
            botao.addEventListener('click', function () {
                const novoTema = document.documentElement.dataset.tema === 'escuro' ? 'claro' : 'escuro';
                document.documentElement.dataset.tema = novoTema;
                localStorage.setItem('tema', novoTema);
                localStorage.removeItem('tema-menu');
                atualizarBotoesTema();
            });
        });

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
