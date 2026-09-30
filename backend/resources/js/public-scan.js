// Reveals the free-text field under the "other reason" option once it's
// picked, and hides it again (clearing the value) for any other reason. The
// CSP is `script-src 'self'`, hence a bundled file instead of an inline
// handler on the radio inputs.

const radios = document.querySelectorAll('input[name="reason"]');
const otherWrap = document.getElementById('other-details-wrap');
const otherField = document.getElementById('other-details');

function syncOtherField() {
    const checked = document.querySelector('input[name="reason"]:checked');
    const isOther = checked?.value === 'other';
    otherWrap.classList.toggle('hidden', !isOther);
    otherField.required = isOther;
    if (!isOther) otherField.value = '';
}

if (otherWrap && otherField) {
    radios.forEach((r) => r.addEventListener('change', syncOtherField));
    syncOtherField();
}
