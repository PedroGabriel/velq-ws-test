// Laravel Echo over the Pusher protocol, pointed at the app's OWN origin.
//
// Why no VITE_PUSHER_* env here: the Pusher app key is the per-instance id that
// Velq injects at RUNTIME (via the /bindings endpoint), AFTER the Vite build has
// already run. A key baked into the bundle at build time would be empty. So the
// server renders the live key into the page (see the layout's window.Velq block)
// and we read it here. This is standard Laravel Echo; nothing Velq-specific.
//
// The WebSocket endpoint /app/{key} is served on the app's own public listener,
// so the client connects back to window.location (same host, same TLS). The
// server-side broadcaster is what reaches the edge REST API via PUSHER_HOST.

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const cfg = window.Velq?.broadcasting;

if (cfg && cfg.key) {
    const isHttps = window.location.protocol === 'https:';

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: cfg.key,
        wsHost: window.location.hostname,
        wsPort: window.location.port || (isHttps ? 443 : 80),
        wssPort: window.location.port || (isHttps ? 443 : 80),
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
        // No cluster: this is Velq's edge, not Pusher Cloud. The default
        // cluster URL is never contacted because wsHost is set above.
        cluster: '',
        disableStats: true,
    });
}
