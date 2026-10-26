(function () {
    const button = document.querySelector('[data-toggle-password]');
    const password = document.getElementById('senha');

    if (!button || !password) {
        return;
    }

    button.addEventListener('click', function () {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        button.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
    });
})();
