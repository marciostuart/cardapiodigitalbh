(() => {
    const panel = document.querySelector('[data-web-push]');
    const button = panel?.querySelector('[data-web-push-toggle]');
    const status = panel?.querySelector('[data-web-push-status]');
    if (!panel || !button || !status) return;

    const storeUrl = panel.dataset.subscriptionsUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let registration;
    let subscription;

    const say = (message) => { status.textContent = message; };
    const refresh = () => {
        button.textContent = subscription ? 'Desativar alertas' : 'Ativar alertas';
        button.disabled = false;
    };
    const encodeKey = (key) => {
        const padded = key.replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(padded + '='.repeat((4 - padded.length % 4) % 4));
        return Uint8Array.from(raw, character => character.charCodeAt(0));
    };
    const request = async (url, method, body) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        if (!response.ok) throw new Error(response.status === 419 ? 'Sua sessão expirou. Atualize o painel e tente novamente.' : 'Não foi possível atualizar as notificações.');
        return response.json();
    };

    const start = async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            say('Este navegador não oferece notificações push.');
            button.hidden = true;
            return;
        }
        try {
            registration = await navigator.serviceWorker.register('/push-sw.js', { scope: '/' });
            subscription = await registration.pushManager.getSubscription();
            if (Notification.permission === 'denied' && !subscription) {
                say('As notificações estão bloqueadas nas permissões do navegador.');
                button.disabled = true;
            } else {
                refresh();
            }
        } catch (_) {
            say('Não foi possível preparar as notificações neste dispositivo.');
            button.disabled = true;
        }
    };

    button.addEventListener('click', async () => {
        button.disabled = true;
        say('Atualizando preferência…');
        try {
            if (subscription) {
                const endpoint = subscription.endpoint;
                await subscription.unsubscribe();
                await request(storeUrl, 'DELETE', { endpoint });
                subscription = null;
                say('Alertas desativados neste dispositivo.');
                refresh();
                return;
            }

            if (Notification.permission !== 'granted') {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    say('Permissão não concedida. Você pode alterar isso nas configurações do navegador.');
                    refresh();
                    return;
                }
            }

            const keyResponse = await request(panel.dataset.keyUrl, 'GET');
            if (!keyResponse.publicKey) throw new Error('Notificações ainda não foram configuradas no servidor.');
            subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: encodeKey(keyResponse.publicKey) });
            await request(storeUrl, 'POST', subscription.toJSON());
            say('Este dispositivo receberá alertas de novos pedidos.');
            refresh();
        } catch (error) {
            say(error.message || 'Não foi possível ativar as notificações.');
            subscription = await registration?.pushManager.getSubscription().catch(() => null);
            refresh();
        }
    });

    start();
})();
