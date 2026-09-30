// Keeps a visitor's conversation page current without a manual refresh: it
// polls the thread's JSON endpoint every few seconds while the tab is visible
// and redraws the bubbles with textContent (never innerHTML), so message text
// can't inject markup. The CSP is `script-src 'self'`, hence a bundled file.

const root = document.getElementById('thread-live');

if (root) {
    const list = document.getElementById('thread-list');
    const status = document.getElementById('thread-status');
    const url = root.dataset.url;
    const labels = { new: 'أُرسل — بانتظار قراءة المالك', read: 'قرأ المالك بلاغك', responded: 'ردّ المالك', closed: 'أُغلق البلاغ' };
    let signature = list.dataset.signature || '';

    function bubble(m) {
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (m.sender === 'visitor' ? 'justify-end' : 'justify-start');

        const box = document.createElement('div');
        box.className = 'max-w-[85%] rounded-2xl px-4 py-2.5 text-sm ' + (m.sender === 'visitor'
            ? 'rounded-ee-sm bg-brand-yellow text-brand-black'
            : 'rounded-es-sm bg-ink-100 text-ink-900');

        if (m.sender === 'owner') {
            const who = document.createElement('p');
            who.className = 'mb-0.5 text-[11px] font-semibold text-ink-500';
            who.textContent = 'المالك';
            box.appendChild(who);
        }
        if (m.body) {
            const body = document.createElement('p');
            body.className = 'whitespace-pre-line';
            body.textContent = m.body;
            box.appendChild(body);
        }
        if (m.image_url) {
            const link = document.createElement('a');
            link.href = m.image_url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            const img = document.createElement('img');
            img.src = m.image_url;
            img.alt = 'صورة مرفقة';
            img.className = 'mt-1.5 max-h-56 rounded-lg object-contain';
            link.appendChild(img);
            box.appendChild(link);
        }
        const time = document.createElement('p');
        time.className = 'mt-1 text-[11px] opacity-60';
        time.textContent = m.at;
        box.append(time);
        wrap.appendChild(box);

        return wrap;
    }

    async function poll() {
        if (document.hidden) return;
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            const next = data.messages.length + ':' + data.status;
            if (status && labels[data.status]) status.textContent = labels[data.status];
            if (next === signature) return;

            signature = next;
            list.replaceChildren(...data.messages.map(bubble));
        } catch (e) {
            // Offline or server restarting: try again on the next tick.
        }
    }

    setInterval(poll, 6000);
    document.addEventListener('visibilitychange', poll);
}

// Shows the chosen file's name on the attach-image button instead of the
// browser's own (English, verbose) file input label.
const imageInput = document.getElementById('reply-image');
const imageLabel = document.getElementById('reply-image-label');
const defaultImageLabel = imageLabel ? imageLabel.textContent : '';
imageInput?.addEventListener('change', () => {
    imageLabel.textContent = imageInput.files[0] ? imageInput.files[0].name : defaultImageLabel;
});
