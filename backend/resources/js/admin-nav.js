function setMobileNavOpen(open) {
    const nav = document.getElementById('mobile-nav');
    const backdrop = document.getElementById('mobile-nav-backdrop');
    if (!nav || !backdrop) return;

    nav.classList.toggle('translate-x-full', !open);
    backdrop.hidden = !open;
    document.body.style.overflow = open ? 'hidden' : '';
}

// Module scripts run after the DOM is already parsed (like `defer`), so
// DOMContentLoaded may already have fired by the time this executes —
// wiring listeners directly here is correct, no DOMContentLoaded wrapper needed.
document.getElementById('mobile-nav-open')?.addEventListener('click', () => setMobileNavOpen(true));
document.getElementById('mobile-nav-close')?.addEventListener('click', () => setMobileNavOpen(false));
document.getElementById('mobile-nav-backdrop')?.addEventListener('click', () => setMobileNavOpen(false));

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setMobileNavOpen(false);
});
