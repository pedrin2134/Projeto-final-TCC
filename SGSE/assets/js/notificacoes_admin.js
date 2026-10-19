(function () {
    const container = document.querySelector('[data-notifications]');
    if (!container) {
        return;
    }

    const toggle = container.querySelector('[data-notifications-toggle]');
    const panel = container.querySelector('[data-notifications-panel]');
    const clearButton = container.querySelector('[data-notifications-clear]');
    const list = container.querySelector('.admin-notifications-list');
    const count = container.querySelector('[data-notifications-count]');
    const emptyMessage = container.querySelector('[data-notifications-empty]');
    const storageKey = 'notificacoes-admin-limpas-' + container.dataset.userId;
    const dispensadas = new Set((localStorage.getItem(storageKey) || '').split(',').filter(Boolean));

    function atualizarTempo() {
        container.querySelectorAll('[data-notification-time]').forEach(function (elemento) {
            const timestamp = Number(elemento.dataset.notificationTime) * 1000;
            if (!timestamp) {
                return;
            }

            const minutos = Math.max(0, Math.floor((Date.now() - timestamp) / 60000));
            let texto;
            if (minutos < 1) {
                texto = 'Agora';
            } else if (minutos < 60) {
                texto = 'Há ' + minutos + (minutos === 1 ? ' minuto' : ' minutos');
            } else if (minutos < 1440) {
                const horas = Math.floor(minutos / 60);
                texto = 'Há ' + horas + (horas === 1 ? ' hora' : ' horas');
            } else {
                const dias = Math.floor(minutos / 1440);
                texto = 'Há ' + dias + (dias === 1 ? ' dia' : ' dias');
            }
            elemento.textContent = texto;
        });
    }

    function atualizarLista() {
        const itens = Array.from(container.querySelectorAll('.admin-notification-item'));
        itens.forEach(function (item) {
            item.hidden = dispensadas.has(item.dataset.notificationId);
        });

        const restantes = itens.filter(function (item) {
            return !item.hidden;
        }).length;

        if (count) {
            count.textContent = restantes > 99 ? '99+' : String(restantes);
            count.hidden = restantes === 0;
        }
        if (list) {
            list.hidden = restantes === 0;
        }
        if (emptyMessage) {
            emptyMessage.hidden = restantes > 0;
        }
        if (clearButton) {
            clearButton.hidden = restantes === 0;
        }
        atualizarTempo();
    }

    function fecharPainel() {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function () {
        const aberto = toggle.getAttribute('aria-expanded') === 'true';
        panel.hidden = aberto;
        toggle.setAttribute('aria-expanded', aberto ? 'false' : 'true');
    });

    clearButton.addEventListener('click', function () {
        container.querySelectorAll('.admin-notification-item').forEach(function (item) {
            dispensadas.add(item.dataset.notificationId);
        });
        localStorage.setItem(storageKey, Array.from(dispensadas).join(','));
        atualizarLista();
    });

    container.addEventListener('click', function (event) {
        const link = event.target.closest('.admin-notification-link');
        if (!link) {
            return;
        }
        const item = link.closest('.admin-notification-item');
        dispensadas.add(item.dataset.notificationId);
        localStorage.setItem(storageKey, Array.from(dispensadas).join(','));
    });

    document.addEventListener('click', function (event) {
        if (!container.contains(event.target)) {
            fecharPainel();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            fecharPainel();
        }
    });

    atualizarLista();
    window.setInterval(atualizarTempo, 60000);
})();
