// Guestbook live updates. Standard Laravel Echo: subscribe to the PUBLIC "chat"
// channel and prepend each broadcast MessagePosted event to the list with no
// page refresh. A public channel needs no /broadcasting/auth round-trip.

function el(html) {
    const t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function renderMessage(m) {
    const attachment = m.attachment_url
        ? `<a href="${escapeHtml(m.attachment_url)}" target="_blank" rel="noopener" class="block mt-2">
             <img src="${escapeHtml(m.attachment_url)}" alt="attachment" class="max-h-48 rounded-lg border border-slate-200" />
           </a>`
        : '';
    return el(`
      <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-2">
          <span class="font-semibold text-slate-800">${escapeHtml(m.author)}</span>
          <span class="text-xs text-slate-400">just now</span>
        </div>
        <p class="mt-1 whitespace-pre-wrap text-slate-700">${escapeHtml(m.body)}</p>
        ${attachment}
      </li>`);
}

function bumpCounter() {
    const c = document.getElementById('message-count');
    if (!c) return;
    const n = parseInt(c.textContent.replace(/[^0-9]/g, ''), 10);
    if (!Number.isNaN(n)) c.textContent = String(n + 1);
}

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('messages');
    const status = document.getElementById('live-status');

    if (!window.Echo) {
        if (status) status.textContent = 'live updates unavailable';
        return;
    }

    const channel = window.Echo.channel('chat');

    channel.subscribed?.(() => {
        if (status) {
            status.textContent = 'live';
            status.classList.add('text-emerald-600');
        }
    });

    // The event uses a custom broadcastAs() name ("MessagePosted"), so Echo needs the LEADING DOT to bind the
    // exact name. Without the dot, Echo prepends the namespace (App.Events.MessagePosted) and never matches the
    // wire event "MessagePosted" - the live update silently never fires (the frame arrives, the handler does not).
    channel.listen('.MessagePosted', (e) => {
        const m = e.message ?? e;
        if (!list) return;
        const node = renderMessage(m);
        list.prepend(node);
        bumpCounter();
    });
});
